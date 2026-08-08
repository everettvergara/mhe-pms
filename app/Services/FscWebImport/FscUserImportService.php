<?php

namespace App\Services\FscWebImport;

use App\Enums\UserStatus;
use App\Models\EagleEyeImportMap;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PDO;

class FscUserImportService
{
    /**
     * @return array<string, int>
     */
    public function import(PDO $pdo, ?FscWebImportProgressReporter $progress = null): array
    {
        $role = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->firstOrFail();
        $rows = $this->fetchMheUsers($pdo);
        $legacyUserSites = $this->fetchUserSitesByLegacyUserId($pdo);

        $counts = [
            'users' => 0,
            'users_skipped' => 0,
            'site_assignments' => 0,
            'sites_unmapped' => 0,
            'users_without_sites' => 0,
        ];

        foreach ($rows as $index => $row) {
            $legacyId = (int) $row['id'];
            $username = (string) $row['code'];
            $email = (string) $row['email'];

            $existingByEmail = User::query()
                ->where('email', $email)
                ->where('username', '!=', $username)
                ->exists();

            if ($existingByEmail) {
                $counts['users_skipped']++;

                continue;
            }

            $now = now();
            $status = ((int) $row['is_active']) === 1 ? UserStatus::Active : UserStatus::Inactive;
            $userPayload = [
                'name' => (string) $row['name'],
                'email' => $email,
                'contact_number' => $row['mobile_no'] ?: null,
                'role_id' => $role->id,
                'status' => $status->value,
                'is_super_admin' => ((int) $row['has_fa']) === 1,
                'supplier_id' => null,
                'password' => (string) $row['password'],
                'updated_at' => $now,
            ];

            $userId = User::query()->where('username', $username)->value('id');

            if ($userId) {
                DB::table('users')->where('id', $userId)->update($userPayload);
            } else {
                DB::table('users')->insert(array_merge($userPayload, [
                    'username' => $username,
                    'created_at' => $now,
                ]));
                $userId = User::query()->where('username', $username)->value('id');
            }

            $user = User::query()->findOrFail($userId);

            $user->suppliers()->sync([]);
            $resolvedSites = $this->resolveLocalSiteIds($legacyUserSites[$legacyId] ?? []);
            $user->sites()->sync($resolvedSites['local_site_ids']);
            $counts['site_assignments'] += count($resolvedSites['local_site_ids']);
            $counts['sites_unmapped'] += count($resolvedSites['unmapped_codes']);

            if ($resolvedSites['local_site_ids'] === [] && ! ((int) $row['has_fa'] === 1)) {
                // EE user with MHE access but no tb_sys_mf_user_site rows: import user only.
                $counts['users_without_sites']++;
            }

            EagleEyeImportMap::query()->updateOrCreate(
                [
                    'entity_type' => 'user',
                    'legacy_id' => $legacyId,
                ],
                [
                    'local_id' => $user->id,
                    'legacy_code' => $username,
                ],
            );

            $counts['users']++;

            $progress?->tick(
                $index + 1,
                sprintf('Importing user %d of %d…', $index + 1, count($rows)),
                count($rows),
            );
        }

        return $counts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchMheUsersForImport(PDO $pdo): array
    {
        return $this->fetchMheUsers($pdo);
    }

    /**
     * Dry-run stats without writing to mhe-pms.
     *
     * @return array<string, int|list<string>>
     */
    public function preview(PDO $pdo): array
    {
        $rows = $this->fetchMheUsers($pdo);
        $legacyUserSites = $this->fetchUserSitesByLegacyUserId($pdo);

        $stats = [
            'users_total' => count($rows),
            'users_would_import' => 0,
            'users_skipped_email_conflict' => 0,
            'site_assignments' => 0,
            'sites_unmapped' => 0,
            'users_without_sites' => 0,
            'unmapped_site_codes' => [],
        ];

        /** @var array<string, true> $unmappedCodes */
        $unmappedCodes = [];

        foreach ($rows as $row) {
            $legacyId = (int) $row['id'];
            $username = (string) $row['code'];
            $email = (string) $row['email'];

            $emailConflict = User::query()
                ->where('email', $email)
                ->where('username', '!=', $username)
                ->exists();

            if ($emailConflict) {
                $stats['users_skipped_email_conflict']++;

                continue;
            }

            $resolvedSites = $this->resolveLocalSiteIds($legacyUserSites[$legacyId] ?? []);
            $stats['users_would_import']++;
            $stats['site_assignments'] += count($resolvedSites['local_site_ids']);
            $stats['sites_unmapped'] += count($resolvedSites['unmapped_codes']);

            if ($resolvedSites['local_site_ids'] === [] && ! ((int) $row['has_fa'] === 1)) {
                $stats['users_without_sites']++;
            }

            foreach ($resolvedSites['unmapped_codes'] as $code) {
                $unmappedCodes[$code] = true;
            }
        }

        $stats['unmapped_site_codes'] = array_keys($unmappedCodes);
        sort($stats['unmapped_site_codes']);

        return $stats;
    }

    /**
     * @return array<int, list<array{ee_site_id: int, site_code: string}>>
     */
    protected function fetchUserSitesByLegacyUserId(PDO $pdo): array
    {
        $statement = $pdo->query(<<<'SQL'
            SELECT
                us.user_id,
                s.id AS ee_site_id,
                s.code AS site_code
            FROM tb_sys_mf_user_site us
            INNER JOIN tb_fin_mf_site s ON s.id = us.site_id
            ORDER BY us.user_id, s.code
        SQL);

        /** @var array<int, list<array{ee_site_id: int, site_code: string}>> $grouped */
        $grouped = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $userId = (int) $row['user_id'];
            $grouped[$userId][] = [
                'ee_site_id' => (int) $row['ee_site_id'],
                'site_code' => (string) $row['site_code'],
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<array{ee_site_id: int, site_code: string}>  $legacySites
     * @return array{local_site_ids: list<int>, unmapped_codes: list<string>}
     */
    protected function resolveLocalSiteIds(array $legacySites): array
    {
        $localSiteIds = [];
        $unmappedCodes = [];
        $seenLocalIds = [];

        foreach ($legacySites as $legacySite) {
            $siteCode = trim($legacySite['site_code']);

            if ($siteCode === '') {
                continue;
            }

            $localSiteId = Site::query()->where('site_code', $siteCode)->value('id');

            if (! $localSiteId) {
                $unmappedCodes[] = $siteCode;

                continue;
            }

            $localSiteId = (int) $localSiteId;

            if (isset($seenLocalIds[$localSiteId])) {
                continue;
            }

            $seenLocalIds[$localSiteId] = true;
            $localSiteIds[] = $localSiteId;
        }

        return [
            'local_site_ids' => $localSiteIds,
            'unmapped_codes' => $unmappedCodes,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchMheUsers(PDO $pdo): array
    {
        $statement = $pdo->query(<<<'SQL'
            SELECT
                u.id,
                u.code,
                u.name,
                u.email,
                u.password,
                u.is_active,
                u.mobile_no,
                MAX(CASE WHEN at.code = 'FA' THEN 1 ELSE 0 END) AS has_fa
            FROM tb_sys_mf_user u
            LEFT JOIN tb_sys_mf_user_access_type uat ON u.id = uat.user_id
            LEFT JOIN tb_sys_mf_access_type at ON at.id = uat.access_type_id
            WHERE u.is_active = 1
              AND (
                EXISTS (
                    SELECT 1
                    FROM tb_sys_mf_user_access_type uat2
                    JOIN tb_sys_mf_mod_access_type mat ON uat2.access_type_id = mat.access_type_id
                    JOIN tb_sys_mf_mod m ON mat.mod_id = m.id AND m.code = 'MHE'
                    WHERE uat2.user_id = u.id
                )
                OR EXISTS (
                    SELECT 1
                    FROM tb_sys_mf_user_access_type uat3
                    JOIN tb_sys_mf_access_type at3 ON at3.id = uat3.access_type_id
                    WHERE uat3.user_id = u.id AND at3.code = 'FA'
                )
              )
            GROUP BY u.id, u.code, u.name, u.email, u.password, u.is_active, u.mobile_no
            ORDER BY u.code
        SQL);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
