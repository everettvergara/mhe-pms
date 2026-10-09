<?php

namespace App\Services\FscWebImport;

use App\Enums\UserStatus;
use App\Models\EagleEyeImportMap;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;

class FscDistrictUserImportService
{
    public const STATUS_WILL_IMPORT = 'will_import';

    public const STATUS_ALREADY_EXISTS = 'already_exists';

    public const STATUS_EMAIL_CONFLICT = 'email_conflict';

    public const STATUS_NO_MATCHING_SITES = 'no_matching_sites';

    public const STATUS_USERNAME_TOO_LONG = 'username_too_long';

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public function fetchDistricts(PDO $pdo): array
    {
        $statement = $pdo->query(<<<'SQL'
            SELECT id, code, name
            FROM tb_fin_mf_district
            WHERE is_active = 1
            ORDER BY code
        SQL);

        $rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $districtCodes
     * @param  array<string, mixed>  $connection
     * @return array<string, mixed>
     */
    public function preview(PDO $pdo, array $districtCodes, array $connection): array
    {
        $districtCodes = $this->normalizeDistrictCodes($districtCodes);
        $this->assertDistrictsExist($pdo, $districtCodes);

        $classified = $this->classify($pdo, $districtCodes);
        $importable = array_values(array_filter(
            $classified,
            fn (array $row): bool => $row['status'] === self::STATUS_WILL_IMPORT,
        ));

        $metrics = [
            'users_matched' => count($classified),
            'users_will_import' => count($importable),
            'users_already_exist' => $this->countStatus($classified, self::STATUS_ALREADY_EXISTS),
            'users_email_conflict' => $this->countStatus($classified, self::STATUS_EMAIL_CONFLICT),
            'users_no_matching_sites' => $this->countStatus($classified, self::STATUS_NO_MATCHING_SITES),
            'users_username_too_long' => $this->countStatus($classified, self::STATUS_USERNAME_TOO_LONG),
            'users_outside_district' => count(array_filter(
                $classified,
                fn (array $row): bool => $row['outside_site_codes'] !== [],
            )),
        ];

        $token = FscDistrictImportToken::issue([
            'districts' => $districtCodes,
            'snapshot_hash' => $this->snapshotHash($importable),
            'connection' => $this->connectionForToken($connection),
        ]);

        return [
            'districts' => $districtCodes,
            'users' => $classified,
            'metrics' => $metrics,
            'preview_token' => $token,
        ];
    }

    /**
     * @return array<string, int|list<int>>
     */
    public function import(PDO $pdo, string $token, bool $deactivate, ?int $operatorId = null): array
    {
        $payload = FscDistrictImportToken::parse($token);
        $districtCodes = $this->normalizeDistrictCodes($payload['districts'] ?? []);
        $this->assertDistrictsExist($pdo, $districtCodes);

        $classified = $this->classify($pdo, $districtCodes);
        $importable = array_values(array_filter(
            $classified,
            fn (array $row): bool => $row['status'] === self::STATUS_WILL_IMPORT,
        ));

        if ($this->snapshotHash($importable) !== ($payload['snapshot_hash'] ?? '')) {
            throw new InvalidArgumentException('Eagle Eye users or sites changed since preview. Run preview again.');
        }

        if ($importable === []) {
            throw new InvalidArgumentException('No users to import.');
        }

        if ($deactivate && ! config('fsc_web_import.allow_source_deactivate')) {
            throw new InvalidArgumentException('Deactivating users in fsc_web is disabled. Set FSC_WEB_IMPORT_ALLOW_SOURCE_DEACTIVATE=true first.');
        }

        $roleId = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->firstOrFail()->id;
        $createdLegacyIds = [];

        DB::transaction(function () use ($importable, $roleId, $operatorId, &$createdLegacyIds): void {
            foreach ($importable as $row) {
                if ($this->usernameExists($row['code'])) {
                    throw new InvalidArgumentException('A user appeared in mhe-pms after preview. Run preview again.');
                }

                $user = User::query()->create([
                    'username' => $row['code'],
                    'name' => $row['name'],
                    'email' => $this->importEmail($row['code']),
                    'password' => $row['code'],
                    'role_id' => $roleId,
                    'supplier_id' => null,
                    'status' => UserStatus::Active,
                    'is_super_admin' => false,
                    'created_by' => $operatorId,
                    'updated_by' => $operatorId,
                ]);

                $user->sites()->sync($row['site_ids']);

                EagleEyeImportMap::query()->updateOrCreate(
                    [
                        'entity_type' => 'user',
                        'legacy_id' => $row['legacy_id'],
                    ],
                    [
                        'local_id' => $user->id,
                        'legacy_code' => $row['code'],
                    ],
                );

                $this->activityLogService->log(
                    $operatorId ? User::query()->find($operatorId) : null,
                    'Users',
                    'import',
                    $user->id,
                    sprintf(
                        'Imported %s from fsc_web as FAST Administrator with %d site(s).',
                        $row['code'],
                        count($row['site_ids']),
                    ),
                );

                $createdLegacyIds[] = $row['legacy_id'];
            }
        });

        $deactivateResult = [
            'deactivated' => 0,
            'deactivate_failed_ids' => [],
        ];

        if ($deactivate) {
            $deactivateResult = $this->deactivateInEagleEye($pdo, $createdLegacyIds);
        }

        return [
            'users_created' => count($createdLegacyIds),
            'deactivated' => $deactivateResult['deactivated'],
            'deactivate_failed_ids' => $deactivateResult['deactivate_failed_ids'],
        ];
    }

    /**
     * @param  list<int>  $legacyIds
     * @return array{deactivated: int, deactivate_failed_ids: list<int>}
     */
    public function deactivateInEagleEye(PDO $pdo, array $legacyIds): array
    {
        $deactivated = 0;
        $failed = [];
        $statement = $pdo->prepare('UPDATE tb_sys_mf_user SET is_active = 0 WHERE id = ? AND is_active = 1');

        foreach ($legacyIds as $legacyId) {
            try {
                $statement->execute([$legacyId]);
                $deactivated += $statement->rowCount();
            } catch (\Throwable) {
                $failed[] = $legacyId;
            }
        }

        return [
            'deactivated' => $deactivated,
            'deactivate_failed_ids' => $failed,
        ];
    }

    /**
     * @param  list<string>  $districtCodes
     * @return list<string>
     */
    public function normalizeDistrictCodes(array $districtCodes): array
    {
        $codes = [];

        foreach ($districtCodes as $code) {
            $code = trim((string) $code);

            if ($code !== '') {
                $codes[$code] = $code;
            }
        }

        $codes = array_values($codes);
        sort($codes);

        if ($codes === []) {
            throw new InvalidArgumentException('Select at least one district.');
        }

        return $codes;
    }

    /**
     * @param  list<string>  $districtCodes
     */
    protected function assertDistrictsExist(PDO $pdo, array $districtCodes): void
    {
        $placeholders = $this->placeholders(count($districtCodes));
        $statement = $pdo->prepare("SELECT code FROM tb_fin_mf_district WHERE code IN ({$placeholders})");
        $statement->execute($districtCodes);
        $found = array_map(strval(...), $statement->fetchAll(PDO::FETCH_COLUMN));
        $missing = array_values(array_diff($districtCodes, $found));

        if ($missing !== []) {
            throw new InvalidArgumentException('Unknown fsc_web district code: '.implode(', ', $missing));
        }
    }

    /**
     * @param  list<string>  $districtCodes
     * @return list<array<string, mixed>>
     */
    protected function classify(PDO $pdo, array $districtCodes): array
    {
        $selected = array_fill_keys($districtCodes, true);
        $grouped = [];

        foreach ($this->fetchUserSites($pdo, $districtCodes) as $row) {
            $legacyId = (int) $row['id'];

            if (! isset($grouped[$legacyId])) {
                $grouped[$legacyId] = [
                    'legacy_id' => $legacyId,
                    'code' => (string) $row['code'],
                    'name' => (string) $row['name'],
                    'in_sites' => [],
                    'outside_site_codes' => [],
                ];
            }

            $siteCode = trim((string) $row['site_code']);

            if ($siteCode === '') {
                continue;
            }

            if (isset($selected[(string) $row['district_code']])) {
                $grouped[$legacyId]['in_sites'][$siteCode] = $siteCode;
            } else {
                $grouped[$legacyId]['outside_site_codes'][$siteCode] = $siteCode;
            }
        }

        $siteIdsByCode = Site::query()->pluck('id', 'site_code')->all();
        $classified = [];

        foreach ($grouped as $user) {
            $inCodes = array_values($user['in_sites']);
            sort($inCodes);
            $outside = array_values($user['outside_site_codes']);
            sort($outside);

            $mappedIds = [];
            $unmapped = [];

            foreach ($inCodes as $siteCode) {
                if (isset($siteIdsByCode[$siteCode])) {
                    $mappedIds[] = (int) $siteIdsByCode[$siteCode];
                } else {
                    $unmapped[] = $siteCode;
                }
            }

            $status = $this->statusFor($user['code'], $mappedIds);

            $classified[] = [
                'legacy_id' => $user['legacy_id'],
                'code' => $user['code'],
                'name' => $user['name'],
                'status' => $status,
                'site_codes' => $inCodes,
                'site_ids' => $mappedIds,
                'unmapped_site_codes' => $unmapped,
                'outside_site_codes' => $outside,
            ];
        }

        usort($classified, fn (array $left, array $right): int => strcmp($left['code'], $right['code']));

        return $classified;
    }

    /**
     * @param  list<int>  $mappedSiteIds
     */
    protected function statusFor(string $username, array $mappedSiteIds): string
    {
        if (strlen($username) > 50) {
            return self::STATUS_USERNAME_TOO_LONG;
        }

        if ($this->usernameExists($username)) {
            return self::STATUS_ALREADY_EXISTS;
        }

        if ($this->emailConflicts($username)) {
            return self::STATUS_EMAIL_CONFLICT;
        }

        if ($mappedSiteIds === []) {
            return self::STATUS_NO_MATCHING_SITES;
        }

        return self::STATUS_WILL_IMPORT;
    }

    protected function usernameExists(string $username): bool
    {
        return User::withTrashed()
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
            ->exists();
    }

    protected function emailConflicts(string $username): bool
    {
        return User::withTrashed()
            ->where('email', $this->importEmail($username))
            ->whereRaw('LOWER(username) != ?', [mb_strtolower($username)])
            ->exists();
    }

    protected function importEmail(string $username): string
    {
        return mb_strtolower($username).'@fsc-import.local';
    }

    /**
     * @param  list<string>  $districtCodes
     * @return list<array<string, mixed>>
     */
    protected function fetchUserSites(PDO $pdo, array $districtCodes): array
    {
        $placeholders = $this->placeholders(count($districtCodes));
        $accessType = (string) config('fsc_web_import.mhe_transaction_access_type');

        $statement = $pdo->prepare(<<<SQL
            SELECT u.id, u.code, u.name, d.code AS district_code, s.code AS site_code
            FROM tb_sys_mf_user u
            JOIN tb_sys_mf_user_site us ON us.user_id = u.id
            JOIN tb_fin_mf_site s ON s.id = us.site_id
            JOIN tb_fin_mf_district d ON d.id = s.district_id
            WHERE u.is_active = 1
              AND EXISTS (
                SELECT 1
                FROM tb_sys_mf_user_site us2
                JOIN tb_fin_mf_site s2 ON s2.id = us2.site_id
                JOIN tb_fin_mf_district d2 ON d2.id = s2.district_id
                WHERE us2.user_id = u.id
                  AND d2.code IN ({$placeholders})
              )
              AND EXISTS (
                SELECT 1
                FROM tb_sys_mf_user_access_type uat
                JOIN tb_sys_mf_access_type at ON at.id = uat.access_type_id
                WHERE uat.user_id = u.id
                  AND at.code = ?
              )
            ORDER BY u.code, d.code, s.code
        SQL);

        $statement->execute([...$districtCodes, $accessType]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function countStatus(array $rows, string $status): int
    {
        return count(array_filter($rows, fn (array $row): bool => $row['status'] === $status));
    }

    /**
     * @param  list<array<string, mixed>>  $importable
     */
    protected function snapshotHash(array $importable): string
    {
        $lines = array_map(function (array $row): string {
            $codes = $row['site_codes'];
            sort($codes);

            return $row['legacy_id'].':'.implode(',', $codes);
        }, $importable);

        sort($lines);

        return hash('sha256', implode('|', $lines));
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return array<string, mixed>
     */
    protected function connectionForToken(array $connection): array
    {
        return [
            'host' => $connection['host'] ?? null,
            'port' => $connection['port'] ?? 3306,
            'database' => $connection['database'] ?? null,
            'username' => $connection['username'] ?? null,
            'password' => $connection['password'] ?? '',
        ];
    }

    protected function placeholders(int $count): string
    {
        return implode(',', array_fill(0, $count, '?'));
    }
}
