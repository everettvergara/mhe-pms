<?php

namespace App\Services\FscWebImport;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\FscWebImportStatus;
use App\Enums\MheDowntimeImportSource;
use App\Models\Attachment;
use App\Models\EagleEyeImportMap;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheDowntimeImportBatch;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\EagleEye\EagleEyeImportLogger;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PDO;
use Throwable;

class FscDowntimeMigrationService
{
    public const STATUS_READY = 'ready';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_ALREADY = 'already_migrated';

    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchDistricts(PDO $pdo): array
    {
        $map = new FscDowntimeMasterMap;
        $rows = [];

        foreach ($pdo->query('SELECT id, code, name, is_active FROM tb_fin_mf_district ORDER BY code')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $local = $map->districtForCode((string) $row['code']);
            $rows[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'is_active' => (int) ($row['is_active'] ?? 0) === 1,
                'mapped' => $local !== null,
                'local_deleted' => $local?->trashed() ?? false,
                'local_district_name' => $local?->district_name,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchSites(PDO $pdo, string $districtCode): array
    {
        $districtCode = trim($districtCode);

        if ($districtCode === '') {
            throw new InvalidArgumentException('Select a district first.');
        }

        $map = new FscDowntimeMasterMap;

        if ($map->districtForCode($districtCode) === null) {
            throw new InvalidArgumentException("District {$districtCode} is not in mhe-pms.");
        }

        $statement = $pdo->prepare(<<<'SQL'
            SELECT s.id, s.code, s.name,
                (SELECT COUNT(*) FROM tb_sf_tr_mhe m WHERE m.site_id = s.id) AS downtime_count
            FROM tb_fin_mf_site s
            JOIN tb_fin_mf_district d ON d.id = s.district_id
            WHERE d.code = ?
            ORDER BY s.code
        SQL);
        $statement->execute([$districtCode]);

        $sites = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $resolved = $map->resolveSite((int) $row['id'], $row['code'], $districtCode);
            $sites[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'downtime_count' => (int) $row['downtime_count'],
                'mapped' => $resolved['reason'] === null,
                'local_site_code' => $resolved['code'],
                'local_site_name' => $resolved['name'],
                'block_message' => $resolved['message'],
            ];
        }

        return $sites;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    public function count(PDO $pdo, array $filter): array
    {
        $filter = $this->normalizeFilter($filter);

        return [
            'filter' => $filter,
            'summary' => $this->summarize($this->classify($pdo, $filter)),
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    public function preview(PDO $pdo, array $filter, string $tab = self::STATUS_READY, int $page = 1): array
    {
        $filter = $this->normalizeFilter($filter);
        $rows = $this->classify($pdo, $filter);
        $summary = $this->summarize($rows);
        $tab = in_array($tab, [self::STATUS_READY, self::STATUS_BLOCKED, self::STATUS_ALREADY], true)
            ? $tab
            : self::STATUS_READY;

        $matching = array_values(array_filter($rows, fn (array $row): bool => $row['status'] === $tab));
        $perPage = max(1, (int) config('fsc_web_import.downtime_preview_per_page', 50));
        $total = count($matching);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $ready = array_values(array_filter($rows, fn (array $row): bool => $row['status'] === self::STATUS_READY));

        return [
            'filter' => $filter,
            'summary' => $summary,
            'tab' => $tab,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'rows' => array_slice($matching, ($page - 1) * $perPage, $perPage),
            'ready_total' => count($ready),
            'preview_token' => FscDistrictImportToken::issue([
                'tool' => 'downtime',
                'filter' => $filter,
                'snapshot_hash' => $this->snapshotHash($ready),
            ]),
        ];
    }

    /**
     * @param  list<int>|null  $legacyIds  Null migrates every ready row from the preview.
     * @return array<string, mixed>
     */
    public function migrate(PDO $pdo, string $token, ?array $legacyIds, int $operatorId): array
    {
        $payload = FscDistrictImportToken::parse($token);

        if (($payload['tool'] ?? null) !== 'downtime' || ! is_array($payload['filter'] ?? null)) {
            throw new InvalidArgumentException('Preview is invalid. Run preview again.');
        }

        $filter = $this->normalizeFilter($payload['filter']);
        $rows = $this->classify($pdo, $filter);
        $ready = array_values(array_filter($rows, fn (array $row): bool => $row['status'] === self::STATUS_READY));

        if ($this->snapshotHash($ready) !== ($payload['snapshot_hash'] ?? '')) {
            throw new InvalidArgumentException('fsc_web downtimes changed since preview. Run preview again.');
        }

        $selected = $this->selectRows($ready, $legacyIds);
        $max = max(1, (int) config('fsc_web_import.downtime_max_rows_per_run', 2000));

        if (count($selected) > $max) {
            throw new InvalidArgumentException("This selection has ".count($selected)." downtimes. The limit per run is {$max}. Narrow the site or date filter and preview again.");
        }

        $lock = Cache::lock('fsc-downtime-migration', 600);

        if (! $lock->get()) {
            throw new InvalidArgumentException('Another downtime migration is already running.');
        }

        try {
            return $this->migrateSelected($selected, $filter, $operatorId);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{district_code: string, legacy_site_ids: list<int>, date_from: ?string, date_to: ?string, status_ids: list<int>}
     */
    public function normalizeFilter(array $input): array
    {
        $district = trim((string) ($input['district_code'] ?? $input['district'] ?? ''));

        if ($district === '') {
            throw new InvalidArgumentException('Select a district.');
        }

        $sites = $this->intList($input['legacy_site_ids'] ?? $input['sites'] ?? []);
        $statuses = array_values(array_filter(
            $this->intList($input['status_ids'] ?? $input['statuses'] ?? []),
            fn (int $id): bool => in_array($id, [1, 2, 3, 4], true),
        ));

        return [
            'district_code' => $district,
            'legacy_site_ids' => $sites,
            'date_from' => $this->blankToNull($input['date_from'] ?? null),
            'date_to' => $this->blankToNull($input['date_to'] ?? null),
            'status_ids' => $statuses,
        ];
    }

    /**
     * @param  array{district_code: string, legacy_site_ids: list<int>, date_from: ?string, date_to: ?string, status_ids: list<int>}  $filter
     * @return list<array<string, mixed>>
     */
    protected function classify(PDO $pdo, array $filter): array
    {
        $map = new FscDowntimeMasterMap;

        if ($map->districtForCode($filter['district_code']) === null) {
            throw new InvalidArgumentException("District {$filter['district_code']} is not in mhe-pms.");
        }

        $this->assertSitesBelongToDistrict($pdo, $filter);
        $downtimes = $this->fetchDowntimes($pdo, $filter);
        $ids = array_map(fn (array $row): int => (int) $row['id'], $downtimes);

        $plansByDowntime = [];

        foreach ($this->fetchByIds($pdo, 'SELECT * FROM tb_sf_tr_mhe_action_plan WHERE 1 = 1', 'mhe_id', $ids) as $plan) {
            $plansByDowntime[(int) $plan['mhe_id']][] = $plan;
        }

        $attachmentsByDowntime = [];

        foreach ($this->fetchByIds($pdo, 'SELECT * FROM tb_sf_tr_mhe_attachment WHERE 1 = 1', 'mhe_id', $ids) as $attachment) {
            $attachmentsByDowntime[(int) $attachment['mhe_id']][] = $attachment;
        }

        $planAttachmentsByDowntime = [];

        foreach ($this->fetchByIds($pdo, <<<'SQL'
            SELECT a.id, a.mhe_action_plan_id, a.attachment, a.created_at, a.updated_at, p.mhe_id
            FROM tb_sf_tr_mhe_action_plan_attachment a
            JOIN tb_sf_tr_mhe_action_plan p ON p.id = a.mhe_action_plan_id
            WHERE 1 = 1
        SQL, 'p.mhe_id', $ids) as $attachment) {
            $planAttachmentsByDowntime[(int) $attachment['mhe_id']][] = $attachment;
        }

        $existing = MheDowntime::withTrashed()
            ->whereNotNull('legacy_eagle_eye_id')
            ->pluck('id', 'legacy_eagle_eye_id');

        $classified = [];

        foreach ($downtimes as $row) {
            $legacyId = (int) $row['id'];
            $existingId = $existing->get($legacyId) ?? $existing->get((string) $legacyId);

            $classified[] = $this->classifyRow(
                $row,
                $map,
                $filter['district_code'],
                $plansByDowntime[$legacyId] ?? [],
                $attachmentsByDowntime[$legacyId] ?? [],
                $planAttachmentsByDowntime[$legacyId] ?? [],
                $existingId !== null ? (int) $existingId : null,
            );
        }

        return $classified;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<array<string, mixed>>  $plans
     * @param  list<array<string, mixed>>  $attachments
     * @param  list<array<string, mixed>>  $planAttachments
     * @return array<string, mixed>
     */
    protected function classifyRow(
        array $row,
        FscDowntimeMasterMap $map,
        string $districtCode,
        array $plans,
        array $attachments,
        array $planAttachments,
        ?int $existingLocalId,
    ): array {
        $legacyId = (int) $row['id'];
        $site = $map->resolveSite((int) $row['site_id'], $row['site_code'] ?? null, $districtCode);
        $type = $map->resolveType($row['type_code'] ?? null);
        $category = $map->resolveCategory($row['category_code'] ?? null);
        $user = $map->resolveUser(
            isset($row['created_by_id']) ? (int) $row['created_by_id'] : null,
            $row['user_code'] ?? null,
        );

        $createdAt = $this->parseDateTimeOrNull($row['created_at'] ?? null);
        $updatedAt = $this->parseDateTimeOrNull($row['updated_at'] ?? null);
        $rawIncident = $row['date_of_incident'] ?? null;
        $incident = $this->parseDateTimeOrNull(is_string($rawIncident) ? $rawIncident : null);
        $uptime = $this->parseDateTimeOrNull($row['uptime'] ?? null);
        $warnings = [];
        $dateBlocked = false;

        if ($incident === null && $createdAt !== null) {
            $incident = $createdAt;
            $warnings[] = "Zero or invalid date_of_incident for fsc_web #{$legacyId}; created_at was used.";
        } elseif ($incident === null) {
            $dateBlocked = true;
        }

        $block = $site['reason'] ? $site : ($type['reason'] ? $type : ($category['reason'] ? $category : ($user['reason'] ? $user : null)));

        if ($block === null && $dateBlocked) {
            $block = [
                'reason' => FscDowntimeMasterMap::REASON_INVALID_DATE,
                'message' => "fsc_web #{$legacyId} has no usable incident date or created_at.",
            ];
        }

        $refUnit = trim((string) ($row['ref_unit_no'] ?? ''));

        if ($refUnit === '') {
            $refUnit = (string) config('eagle_eye.defaults.unknown_unit', 'UNKNOWN');
            $warnings[] = 'ref_unit_no was blank; it will be stored as '.$refUnit.'.';
        }

        $inventory = $map->resolveInventory($site['id'], $row['ref_unit_no'] ?? null);

        if ($block === null && ! $inventory['matched']) {
            $warnings[] = 'No mhe-pms inventory match for unit '.$refUnit.' at this site.';
        }

        $hours = ($row['hours_down'] === null || $row['hours_down'] === '') ? null : round((float) $row['hours_down'], 2);

        if ($block === null && $hours !== null && $incident !== null && $uptime !== null) {
            $computed = $incident->diffInSeconds($uptime, false) / 3600;

            if (abs($hours - $computed) > 0.01) {
                $warnings[] = sprintf('hours_down %.2f differs from uptime minus incident (%.2f). The fsc_web value is kept.', $hours, $computed);
            }
        }

        $timeFrom = $this->parseTime($row['time_from'] ?? null);
        $timeTo = $this->parseTime($row['time_to'] ?? null);

        foreach (['time_from' => $row['time_from'] ?? null, 'time_to' => $row['time_to'] ?? null] as $field => $rawTime) {
            if ($this->timeDateDiffers(is_string($rawTime) ? $rawTime : null, $incident)) {
                $warnings[] = "{$field} date differs from the incident date; only the time is stored.";
            }
        }

        $statusId = $row['status_id'] !== null && $row['status_id'] !== '' ? (int) $row['status_id'] : null;
        $newStatus = $this->mapStatus($statusId);

        if ($block === null && $statusId !== null && ! array_key_exists($statusId, config('fsc_web_import.downtime_status_map', []))) {
            $warnings[] = "Unknown fsc_web status {$statusId}; stored as Draft.";
        }

        $childNames = array_map(fn (array $attachment): string => mb_strtolower(basename((string) $attachment['attachment'])), $attachments);
        $header = trim((string) ($row['attachment'] ?? ''));
        $headerAttachment = $header !== '' && ! in_array(mb_strtolower(basename($header)), $childNames, true) ? $header : null;

        $actionPlans = array_map(function (array $plan) use ($uptime, $incident): array {
            $implemented = $this->parseDateTimeOrNull($plan['date_implemented'] ?? null)
                ?? $uptime
                ?? $this->parseDateTimeOrNull($plan['action_plan_date'] ?? null)
                ?? $incident;

            return [
                'legacy_id' => (int) $plan['id'],
                'text' => (string) ($plan['action_plan'] ?? ''),
                'responsible' => $plan['responsible'] ?? null,
                'action_plan_date' => $plan['action_plan_date'] ?? null,
                'date_implemented' => $plan['date_implemented'] ?? null,
                'implemented_at' => $implemented?->toDateTimeString(),
                'created_at' => $plan['created_at'] ?? null,
                'updated_at' => $plan['updated_at'] ?? null,
                'new_status' => DowntimeActionPlanStatus::Confirmed->value,
            ];
        }, $plans);

        $status = self::STATUS_READY;

        if ($existingLocalId !== null) {
            $status = self::STATUS_ALREADY;
        } elseif ($block !== null) {
            $status = self::STATUS_BLOCKED;
        }

        return [
            'legacy_id' => $legacyId,
            'title' => trim((string) ($row['title'] ?? '')) !== '' ? (string) $row['title'] : 'Imported Downtime',
            'legacy_site_id' => (int) $row['site_id'],
            'legacy_site_code' => (string) ($row['site_code'] ?? ''),
            'legacy_site_name' => (string) ($row['site_name'] ?? ''),
            'local_site_id' => $site['id'],
            'local_site_code' => $site['code'],
            'local_site_name' => $site['name'],
            'type_code' => (string) ($row['type_code'] ?? ''),
            'local_type_id' => $type['id'],
            'category_code' => (string) ($row['category_code'] ?? ''),
            'category_name' => (string) ($row['category_name'] ?? ''),
            'local_category_id' => $category['id'],
            'ref_unit_no' => $refUnit,
            'inventory_id' => $inventory['inventory_id'],
            'supplier_id' => $inventory['supplier_id'],
            'inventory_matched' => $inventory['matched'],
            'date_of_incident' => $incident?->toDateTimeString(),
            'uptime' => $uptime?->toDateTimeString(),
            'hours_down' => $hours,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'root_cause' => $row['root_cause'] ?? null,
            'description' => $row['description'] ?? null,
            'approver_remarks' => $row['approver_remarks'] ?? null,
            'w_spare_unit' => (bool) (int) ($row['w_spare_unit'] ?? 0),
            'legacy_status_id' => $statusId,
            'legacy_status_name' => $row['status_name'] ?? ($statusId === null ? 'None' : (string) $statusId),
            'new_status' => $newStatus,
            'creator_legacy_id' => isset($row['created_by_id']) ? (int) $row['created_by_id'] : null,
            'creator_code' => (string) ($row['user_code'] ?? ''),
            'creator_id' => $user['id'],
            'created_at' => $createdAt?->toDateTimeString(),
            'updated_at' => $updatedAt?->toDateTimeString(),
            'source_updated_at' => (string) ($row['updated_at'] ?? ''),
            'status' => $status,
            'block_reason' => $status === self::STATUS_BLOCKED ? $block['reason'] : null,
            'block_message' => $status === self::STATUS_BLOCKED ? $block['message'] : null,
            'warnings' => $status === self::STATUS_BLOCKED ? [] : $warnings,
            'local_downtime_id' => $existingLocalId,
            'action_plans' => $actionPlans,
            'downtime_attachments' => array_map(fn (array $attachment): array => [
                'legacy_id' => (int) $attachment['id'],
                'attachment' => (string) $attachment['attachment'],
                'created_at' => $attachment['created_at'] ?? null,
            ], $attachments),
            'action_plan_attachments' => array_map(fn (array $attachment): array => [
                'legacy_id' => (int) $attachment['id'],
                'mhe_action_plan_id' => (int) $attachment['mhe_action_plan_id'],
                'attachment' => (string) $attachment['attachment'],
                'created_at' => $attachment['created_at'] ?? null,
            ], $planAttachments),
            'header_attachment' => $headerAttachment,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function summarize(array $rows): array
    {
        $reasons = array_fill_keys(array_keys(FscDowntimeMasterMap::REASON_LABELS), 0);
        $creators = [];
        $bySite = [];
        $ready = 0;
        $already = 0;
        $blocked = 0;
        $actionPlans = 0;
        $downtimeAttachments = 0;
        $actionPlanAttachments = 0;

        foreach ($rows as $row) {
            $siteKey = (int) $row['legacy_site_id'];

            if (! isset($bySite[$siteKey])) {
                $bySite[$siteKey] = [
                    'legacy_site_code' => $row['legacy_site_code'],
                    'legacy_site_name' => $row['legacy_site_name'],
                    'local_site_code' => $row['local_site_code'],
                    'total' => 0,
                    'ready' => 0,
                    'blocked' => 0,
                    'already_migrated' => 0,
                ];
            }

            $bySite[$siteKey]['total']++;

            if ($row['status'] === self::STATUS_READY) {
                $ready++;
                $bySite[$siteKey]['ready']++;
                $actionPlans += count($row['action_plans']);
                $downtimeAttachments += count($row['downtime_attachments']) + ($row['header_attachment'] ? 1 : 0);
                $actionPlanAttachments += count($row['action_plan_attachments']);
            } elseif ($row['status'] === self::STATUS_ALREADY) {
                $already++;
                $bySite[$siteKey]['already_migrated']++;
            } else {
                $blocked++;
                $bySite[$siteKey]['blocked']++;
                $reason = (string) $row['block_reason'];
                $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;

                if ($reason === FscDowntimeMasterMap::REASON_CREATOR && $row['creator_code'] !== '') {
                    $creators[$row['creator_code']] = $row['creator_code'];
                }
            }
        }

        ksort($bySite);
        $creatorList = array_values($creators);
        sort($creatorList);

        return [
            'total' => count($rows),
            'ready' => $ready,
            'already_migrated' => $already,
            'blocked' => $blocked,
            'blocked_by_reason' => $reasons,
            'reason_labels' => FscDowntimeMasterMap::REASON_LABELS,
            'unmapped_creators' => $creatorList,
            'ready_action_plans' => $actionPlans,
            'ready_downtime_attachments' => $downtimeAttachments,
            'ready_action_plan_attachments' => $actionPlanAttachments,
            'by_site' => array_values($bySite),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $ready
     * @param  list<int>|null  $legacyIds
     * @return list<array<string, mixed>>
     */
    protected function selectRows(array $ready, ?array $legacyIds): array
    {
        if ($ready === []) {
            throw new InvalidArgumentException('No downtimes to migrate.');
        }

        if ($legacyIds === null) {
            return $ready;
        }

        $legacyIds = $this->intList($legacyIds);

        if ($legacyIds === []) {
            throw new InvalidArgumentException('Select at least one downtime to migrate.');
        }

        $byId = [];

        foreach ($ready as $row) {
            $byId[$row['legacy_id']] = $row;
        }

        $selected = [];

        foreach ($legacyIds as $legacyId) {
            if (! isset($byId[$legacyId])) {
                throw new InvalidArgumentException('A selected downtime is no longer ready to migrate. Run preview again.');
            }

            $selected[] = $byId[$legacyId];
        }

        return $selected;
    }

    /**
     * @param  list<array<string, mixed>>  $selected
     * @param  array{district_code: string, legacy_site_ids: list<int>, date_from: ?string, date_to: ?string, status_ids: list<int>}  $filter
     * @return array<string, mixed>
     */
    protected function migrateSelected(array $selected, array $filter, int $operatorId): array
    {
        $batchId = (string) Str::uuid();
        $logger = new EagleEyeImportLogger($batchId);
        $operator = User::query()->find($operatorId);
        $createdDowntimes = 0;
        $createdPlans = 0;
        $createdDowntimeAttachments = 0;
        $createdPlanAttachments = 0;
        $skipped = 0;
        $failed = [];

        foreach ($selected as $row) {
            try {
                $counts = $this->migrateOne($row, $batchId, $operatorId);

                if ($counts === null) {
                    $skipped++;

                    continue;
                }

                $createdDowntimes++;
                $createdPlans += $counts['action_plans'];
                $createdDowntimeAttachments += $counts['downtime_attachments'];
                $createdPlanAttachments += $counts['action_plan_attachments'];

                foreach ($row['warnings'] as $warning) {
                    $logger->warning('downtime', (int) $row['legacy_id'], (string) $warning);
                }
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    $skipped++;

                    continue;
                }

                $logger->error('downtime', (int) $row['legacy_id'], $exception->getMessage());
                $failed[] = ['legacy_id' => (int) $row['legacy_id'], 'message' => $exception->getMessage()];
            } catch (Throwable $exception) {
                $logger->error('downtime', (int) $row['legacy_id'], $exception->getMessage());
                $failed[] = ['legacy_id' => (int) $row['legacy_id'], 'message' => $exception->getMessage()];
            }
        }

        $result = [
            'batch_id' => $batchId,
            'downtimes_created' => $createdDowntimes,
            'action_plans_created' => $createdPlans,
            'downtime_attachments_created' => $createdDowntimeAttachments,
            'action_plan_attachments_created' => $createdPlanAttachments,
            'skipped' => $skipped,
            'failed' => $failed,
            'warnings' => $logger->warnings(),
            'errors' => $logger->errors(),
        ];

        MheDowntimeImportBatch::query()->create([
            'batch_id' => $batchId,
            'source' => MheDowntimeImportSource::FscWebTool,
            'source_summary' => $this->sourceSummary($filter),
            'dry_run' => false,
            'status' => $failed === [] ? FscWebImportStatus::Completed : FscWebImportStatus::Failed,
            'downtimes' => $createdDowntimes,
            'action_plans' => $createdPlans,
            'downtime_attachments' => $createdDowntimeAttachments,
            'action_plan_attachments' => $createdPlanAttachments,
            'warnings' => $logger->warnings(),
            'errors' => $logger->errors(),
            'result' => $result,
            'created_by' => $operatorId,
        ]);

        $this->activityLogService->log(
            $operator,
            'MHE Downtimes',
            'import',
            null,
            sprintf(
                'Migrated %d fsc_web downtime(s) for %s. Batch %s.',
                $createdDowntimes,
                $filter['district_code'],
                $batchId,
            ),
        );

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{action_plans: int, downtime_attachments: int, action_plan_attachments: int}|null
     */
    protected function migrateOne(array $row, string $batchId, int $operatorId): ?array
    {
        return DB::transaction(function () use ($row, $batchId, $operatorId): ?array {
            $existing = MheDowntime::withTrashed()
                ->where('legacy_eagle_eye_id', $row['legacy_id'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return null;
            }

            $status = DowntimeStatus::from($row['new_status']);
            $creatorId = (int) $row['creator_id'];
            $stamp = $this->parseDateTimeOrNull($row['updated_at']) ?? $this->parseDateTimeOrNull($row['created_at']);

            $downtime = new MheDowntime([
                'legacy_eagle_eye_id' => $row['legacy_id'],
                'legacy_import_batch_id' => $batchId,
                'title' => $row['title'],
                'site_id' => $row['local_site_id'],
                'mhe_type_id' => $row['local_type_id'],
                'mhe_category_id' => $row['local_category_id'],
                'mhe_inventory_id' => $row['inventory_id'],
                'supplier_id' => $row['supplier_id'],
                'ref_unit_no' => $row['ref_unit_no'],
                'date_of_incident' => $row['date_of_incident'],
                'uptime' => $row['uptime'],
                'hours_down' => $row['hours_down'],
                'time_from' => $row['time_from'],
                'time_to' => $row['time_to'],
                'root_cause' => $row['root_cause'],
                'description' => $row['description'],
                'approver_remarks' => $row['approver_remarks'],
                'w_spare_unit' => $row['w_spare_unit'],
                'status' => $status,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
                'posted_by' => $status === DowntimeStatus::Posted ? $creatorId : null,
                'posted_at' => $status === DowntimeStatus::Posted ? $stamp : null,
                'cancelled_by' => $status === DowntimeStatus::Cancelled ? $creatorId : null,
                'cancelled_at' => $status === DowntimeStatus::Cancelled ? $stamp : null,
            ]);

            if ($row['created_at']) {
                $downtime->created_at = $row['created_at'];
            }

            if ($row['updated_at']) {
                $downtime->updated_at = $row['updated_at'];
            }

            $downtime->save();

            $this->rememberMap('downtime', (int) $row['legacy_id'], $downtime->id, null);

            $planIds = [];
            $planCount = 0;

            foreach ($row['action_plans'] as $plan) {
                $created = $this->migrateActionPlan($plan, $downtime->id, $creatorId, $operatorId);
                $planIds[(int) $plan['legacy_id']] = $created->id;
                $planCount++;
            }

            $downtimeAttachmentCount = 0;

            foreach ($row['downtime_attachments'] as $attachment) {
                $this->migrateAttachment(
                    MheDowntime::class,
                    $downtime->id,
                    $attachment,
                    'downtime_attachment',
                    $operatorId,
                );
                $downtimeAttachmentCount++;
            }

            if ($row['header_attachment']) {
                $this->migrateAttachment(
                    MheDowntime::class,
                    $downtime->id,
                    [
                        'legacy_id' => null,
                        'attachment' => $row['header_attachment'],
                        'created_at' => $row['created_at'],
                    ],
                    null,
                    $operatorId,
                );
                $downtimeAttachmentCount++;
            }

            $planAttachmentCount = 0;

            foreach ($row['action_plan_attachments'] as $attachment) {
                $localPlanId = $planIds[(int) $attachment['mhe_action_plan_id']] ?? null;

                if ($localPlanId === null) {
                    continue;
                }

                $this->migrateAttachment(
                    MheDowntimeActionPlan::class,
                    $localPlanId,
                    $attachment,
                    'action_plan_attachment',
                    $operatorId,
                );
                $planAttachmentCount++;
            }

            return [
                'action_plans' => $planCount,
                'downtime_attachments' => $downtimeAttachmentCount,
                'action_plan_attachments' => $planAttachmentCount,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    protected function migrateActionPlan(array $plan, int $downtimeId, int $creatorId, int $operatorId): MheDowntimeActionPlan
    {
        $legacyId = (int) $plan['legacy_id'];
        $description = (string) $plan['text'];
        $title = mb_substr(trim($description), 0, 255);
        $implemented = $this->parseDateTimeOrNull($plan['implemented_at'] ?? null);
        $timeline = $this->parseDateTimeOrNull($plan['action_plan_date'] ?? null)?->toDateString()
            ?? $implemented?->toDateString();

        $actionPlan = new MheDowntimeActionPlan([
            'legacy_eagle_eye_id' => $legacyId,
            'action_plan_no' => 'EE-DT-AP-'.$legacyId,
            'mhe_downtime_id' => $downtimeId,
            'title' => $title !== '' ? $title : 'Imported Action Item',
            'description' => $description,
            'responsible_person' => trim((string) ($plan['responsible'] ?? '')) !== '' ? $plan['responsible'] : 'Unknown',
            'action_plan_date' => $this->parseDateTimeOrNull($plan['action_plan_date'] ?? null)?->toDateString(),
            'timeline_from' => $timeline,
            'timeline_to' => $timeline,
            'status' => DowntimeActionPlanStatus::Confirmed,
            'unit_safe_guaranteed' => false,
            'date_implemented' => $implemented,
            'confirmed_by' => $operatorId,
            'confirmed_at' => $implemented,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
        ]);

        $createdAt = $this->parseDateTimeOrNull($plan['created_at'] ?? null);
        $updatedAt = $this->parseDateTimeOrNull($plan['updated_at'] ?? null);

        if ($createdAt) {
            $actionPlan->created_at = $createdAt;
        }

        if ($updatedAt) {
            $actionPlan->updated_at = $updatedAt;
        }

        $actionPlan->save();
        $this->rememberMap('action_plan', $legacyId, $actionPlan->id, null);

        return $actionPlan;
    }

    /**
     * @param  class-string  $attachableType
     * @param  array<string, mixed>  $attachment
     */
    protected function migrateAttachment(string $attachableType, int $attachableId, array $attachment, ?string $mapType, int $operatorId): void
    {
        $filename = basename((string) ($attachment['attachment'] ?? 'legacy-file'));
        $createdAt = $this->parseDateTimeOrNull($attachment['created_at'] ?? null);

        $record = new Attachment([
            'attachable_type' => (new $attachableType)->getMorphClass(),
            'attachable_id' => $attachableId,
            'file_path' => 'eagle-eye/legacy/'.$filename,
            'original_filename' => $filename,
            'mime_type' => null,
            'file_size' => null,
            'created_by' => $operatorId,
        ]);

        if ($createdAt) {
            $record->created_at = $createdAt;
            $record->updated_at = $createdAt;
        }

        $record->save();

        if ($mapType !== null && isset($attachment['legacy_id'])) {
            $this->rememberMap($mapType, (int) $attachment['legacy_id'], $record->id, $filename);
        }
    }

    protected function rememberMap(string $entityType, int $legacyId, int $localId, ?string $legacyCode): void
    {
        EagleEyeImportMap::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'legacy_id' => $legacyId,
            ],
            [
                'local_id' => $localId,
                'legacy_code' => $legacyCode,
            ],
        );
    }

    /**
     * @param  array{district_code: string, legacy_site_ids: list<int>, date_from: ?string, date_to: ?string, status_ids: list<int>}  $filter
     * @return list<array<string, mixed>>
     */
    protected function fetchDowntimes(PDO $pdo, array $filter): array
    {
        $sql = <<<'SQL'
            SELECT
                m.id, m.title, m.site_id, m.hours_down, m.mhe_category_id, m.date_of_incident,
                m.description, m.attachment, m.created_by_id, m.status_id, m.created_at, m.updated_at,
                m.mhe_type_id, m.approver_remarks, m.uptime, m.ref_unit_no, m.w_spare_unit,
                m.root_cause, m.time_from, m.time_to,
                s.code AS site_code, s.name AS site_name,
                t.code AS type_code,
                c.code AS category_code, c.name AS category_name,
                u.code AS user_code,
                st.name AS status_name
            FROM tb_sf_tr_mhe m
            JOIN tb_fin_mf_site s ON s.id = m.site_id
            JOIN tb_fin_mf_district d ON d.id = s.district_id
            LEFT JOIN tb_sf_mf_mhe_type t ON t.id = m.mhe_type_id
            LEFT JOIN tb_sf_mf_mhe_category c ON c.id = m.mhe_category_id
            LEFT JOIN tb_sys_mf_user u ON u.id = m.created_by_id
            LEFT JOIN tb_sys_mf_status st ON st.id = m.status_id
            WHERE d.code = ?
        SQL;
        $params = [$filter['district_code']];

        if ($filter['legacy_site_ids'] !== []) {
            $sql .= ' AND s.id IN ('.$this->placeholders(count($filter['legacy_site_ids'])).')';
            $params = [...$params, ...$filter['legacy_site_ids']];
        }

        if ($filter['date_from']) {
            $sql .= ' AND date(m.date_of_incident) >= ?';
            $params[] = $filter['date_from'];
        }

        if ($filter['date_to']) {
            $sql .= ' AND date(m.date_of_incident) <= ?';
            $params[] = $filter['date_to'];
        }

        if ($filter['status_ids'] !== []) {
            $sql .= ' AND m.status_id IN ('.$this->placeholders(count($filter['status_ids'])).')';
            $params = [...$params, ...$filter['status_ids']];
        }

        $sql .= ' ORDER BY m.id';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param  array{district_code: string, legacy_site_ids: list<int>, date_from: ?string, date_to: ?string, status_ids: list<int>}  $filter
     */
    protected function assertSitesBelongToDistrict(PDO $pdo, array $filter): void
    {
        if ($filter['legacy_site_ids'] === []) {
            return;
        }

        $statement = $pdo->prepare(
            'SELECT s.id FROM tb_fin_mf_site s JOIN tb_fin_mf_district d ON d.id = s.district_id WHERE d.code = ? AND s.id IN ('.$this->placeholders(count($filter['legacy_site_ids'])).')'
        );
        $statement->execute([$filter['district_code'], ...$filter['legacy_site_ids']]);
        $found = array_map(intval(...), $statement->fetchAll(PDO::FETCH_COLUMN));
        $missing = array_diff($filter['legacy_site_ids'], $found);

        if ($missing !== []) {
            throw new InvalidArgumentException('A selected site is not in district '.$filter['district_code'].'.');
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    protected function fetchByIds(PDO $pdo, string $sql, string $column, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = [];

        foreach (array_chunk($ids, 500) as $chunk) {
            $statement = $pdo->prepare($sql.' AND '.$column.' IN ('.$this->placeholders(count($chunk)).')');
            $statement->execute(array_values($chunk));

            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    protected function mapStatus(?int $statusId): string
    {
        if ($statusId === null) {
            return DowntimeStatus::Draft->value;
        }

        return config('fsc_web_import.downtime_status_map')[$statusId] ?? DowntimeStatus::Draft->value;
    }

    /**
     * @param  list<array<string, mixed>>  $ready
     */
    protected function snapshotHash(array $ready): string
    {
        $lines = [];

        foreach ($ready as $row) {
            $plans = array_map(
                fn (array $plan): string => $plan['legacy_id'].'@'.($plan['updated_at'] ?? ''),
                $row['action_plans'],
            );
            sort($plans);
            $lines[] = $row['legacy_id'].':'.$row['source_updated_at'].':'.implode(',', $plans);
        }

        sort($lines);

        return hash('sha256', implode('|', $lines));
    }

    /**
     * @param  array{district_code: string, legacy_site_ids: list<int>, date_from: ?string, date_to: ?string, status_ids: list<int>}  $filter
     */
    protected function sourceSummary(array $filter): string
    {
        $sites = $filter['legacy_site_ids'] === [] ? 'all sites' : 'sites '.implode(',', $filter['legacy_site_ids']);
        $dates = trim(($filter['date_from'] ?? '').' to '.($filter['date_to'] ?? ''), ' to');

        return trim($filter['district_code'].'; '.$sites.($dates !== '' ? '; '.$dates : ''));
    }

    protected function parseDateTimeOrNull(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '' || preg_match('/^0000-00-00/', $trimmed)) {
            return null;
        }

        try {
            $parsed = Carbon::parse($trimmed);
        } catch (Throwable) {
            return null;
        }

        if ($parsed->year < 1970) {
            return null;
        }

        return $parsed;
    }

    protected function parseTime(mixed $value): ?string
    {
        return $this->parseDateTimeOrNull($value)?->format('H:i:s');
    }

    protected function timeDateDiffers(?string $raw, ?Carbon $incident): bool
    {
        if ($raw === null || ! str_contains($raw, '-') || $incident === null) {
            return false;
        }

        $parsed = $this->parseDateTimeOrNull($raw);

        return $parsed !== null && $parsed->toDateString() !== $incident->toDateString();
    }

    protected function isUniqueViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[0] ?? '') === '23000'
            || str_contains($exception->getMessage(), 'UNIQUE');
    }

    /**
     * @return list<int>
     */
    protected function intList(mixed $values): array
    {
        $ids = [];

        foreach ((array) $values as $value) {
            $id = (int) $value;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $ids = array_values($ids);
        sort($ids);

        return $ids;
    }

    protected function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function placeholders(int $count): string
    {
        return implode(',', array_fill(0, $count, '?'));
    }
}
