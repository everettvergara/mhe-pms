<?php

namespace App\Services\EagleEye;

use App\Enums\DowntimeStatus;
use App\Enums\FscWebImportPhase;
use App\Models\Attachment;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Services\FscWebImport\FscWebImportProgressReporter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EagleEyeDowntimeImportService
{
    protected EagleEyeMasterResolver $resolver;

    protected ?EagleEyeImportLogger $logger = null;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, int|string>
     */
    public function importFromPayload(array $payload, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $batchId = (string) ($options['batch_id'] ?? (string) Str::uuid());
        $logger = new EagleEyeImportLogger($batchId, ! $dryRun);
        $this->logger = $logger;
        $this->resolver = new EagleEyeMasterResolver($logger);

        if (! empty($options['legacy_maps'])) {
            $this->resolver->setLegacyMaps($options['legacy_maps']);
        } else {
            $this->resolver->loadLegacyMaps($options['seed_path'] ?? null);
        }

        $counts = [
            'downtimes' => 0,
            'action_plans' => 0,
            'downtime_attachments' => 0,
            'action_plan_attachments' => 0,
            'warnings' => 0,
            'errors' => 0,
            'batch_id' => $batchId,
        ];

        $downtimeIdMap = [];
        /** @var FscWebImportProgressReporter|null $progress */
        $progress = $options['progress'] ?? null;

        $import = function () use ($payload, $logger, $progress, &$counts, &$downtimeIdMap): void {
            $downtimeRows = $payload['downtimes'] ?? [];
            $progress?->startPhase(
                FscWebImportPhase::ImportingDowntimes,
                count($downtimeRows),
                'Importing downtimes…',
            );

            foreach ($downtimeRows as $index => $row) {
                try {
                    $downtime = $this->importDowntime($row);
                    $downtimeIdMap[(int) $row['legacy_id']] = $downtime->id;
                    $counts['downtimes']++;
                } catch (\Throwable $exception) {
                    $logger->error('downtime', (int) ($row['legacy_id'] ?? 0), $exception->getMessage());
                }

                $progress?->tick(
                    $index + 1,
                    sprintf('Importing downtime %d of %d…', $index + 1, count($downtimeRows)),
                    count($downtimeRows),
                );
            }

            $actionPlanRows = $payload['action_plans'] ?? [];
            $progress?->startPhase(
                FscWebImportPhase::ImportingActionPlans,
                count($actionPlanRows),
                'Importing action plans…',
            );

            foreach ($actionPlanRows as $index => $row) {
                try {
                    $localDowntimeId = $downtimeIdMap[(int) $row['mhe_id']]
                        ?? MheDowntime::query()->where('legacy_eagle_eye_id', $row['mhe_id'])->value('id');

                    if (! $localDowntimeId) {
                        $logger->warning(
                            'action_plan',
                            (int) ($row['legacy_id'] ?? 0),
                            "Skipped orphan action plan; parent downtime {$row['mhe_id']} not found.",
                        );

                        continue;
                    }

                    $this->importActionPlan($row, (int) $localDowntimeId);
                    $counts['action_plans']++;
                } catch (\Throwable $exception) {
                    $logger->error('action_plan', (int) ($row['legacy_id'] ?? 0), $exception->getMessage());
                }

                $progress?->tick(
                    $index + 1,
                    sprintf('Importing action plan %d of %d…', $index + 1, count($actionPlanRows)),
                    count($actionPlanRows),
                );
            }

            $actionPlanIdMap = MheDowntimeActionPlan::query()
                ->whereNotNull('legacy_eagle_eye_id')
                ->pluck('id', 'legacy_eagle_eye_id')
                ->all();

            $downtimeAttachmentRows = $payload['downtime_attachments'] ?? [];
            $actionPlanAttachmentRows = $payload['action_plan_attachments'] ?? [];
            $attachmentTotal = count($downtimeAttachmentRows) + count($actionPlanAttachmentRows);
            $attachmentProcessed = 0;

            $progress?->startPhase(
                FscWebImportPhase::ImportingAttachments,
                max(1, $attachmentTotal),
                'Importing attachments…',
            );

            foreach ($downtimeAttachmentRows as $row) {
                try {
                    $localDowntimeId = $downtimeIdMap[(int) $row['mhe_id']]
                        ?? MheDowntime::query()->where('legacy_eagle_eye_id', $row['mhe_id'])->value('id');

                    if (! $localDowntimeId) {
                        $logger->warning('downtime_attachment', (int) ($row['legacy_id'] ?? 0), "Parent downtime {$row['mhe_id']} not found.");

                        continue;
                    }

                    $this->importAttachment(MheDowntime::class, (int) $localDowntimeId, $row);
                    $counts['downtime_attachments']++;
                } catch (\Throwable $exception) {
                    $logger->error('downtime_attachment', (int) ($row['legacy_id'] ?? 0), $exception->getMessage());
                }

                $attachmentProcessed++;
                $progress?->tick(
                    $attachmentProcessed,
                    sprintf('Importing attachment %d of %d…', $attachmentProcessed, max(1, $attachmentTotal)),
                    max(1, $attachmentTotal),
                );
            }

            foreach ($actionPlanAttachmentRows as $row) {
                try {
                    $localActionPlanId = $actionPlanIdMap[(int) $row['mhe_action_plan_id']] ?? null;

                    if (! $localActionPlanId) {
                        $logger->warning('action_plan_attachment', (int) ($row['legacy_id'] ?? 0), "Parent action plan {$row['mhe_action_plan_id']} not found.");

                        continue;
                    }

                    $this->importAttachment(MheDowntimeActionPlan::class, (int) $localActionPlanId, $row);
                    $counts['action_plan_attachments']++;
                } catch (\Throwable $exception) {
                    $logger->error('action_plan_attachment', (int) ($row['legacy_id'] ?? 0), $exception->getMessage());
                }

                $attachmentProcessed++;
                $progress?->tick(
                    $attachmentProcessed,
                    sprintf('Importing attachment %d of %d…', $attachmentProcessed, max(1, $attachmentTotal)),
                    max(1, $attachmentTotal),
                );
            }
        };

        if ($dryRun) {
            DB::beginTransaction();

            try {
                $import();
            } finally {
                DB::rollBack();
            }
        } else {
            DB::transaction($import);
        }

        $counts['warnings'] = $logger->warnings();
        $counts['errors'] = $logger->errors();

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importDowntime(array $row): MheDowntime
    {
        $legacyId = (int) $row['legacy_id'];
        $siteId = $this->resolver->resolveSiteId((int) $row['ee_site_id']);
        $typeId = $this->resolver->resolveMheTypeId((int) $row['ee_mhe_type_id']);
        $categoryId = $this->resolver->resolveMheCategoryId((int) $row['ee_mhe_category_id']);
        $createdBy = $this->resolver->resolveUserId(isset($row['ee_created_by_id']) ? (int) $row['ee_created_by_id'] : null);
        $status = $this->resolver->mapDowntimeStatus(isset($row['ee_status_id']) ? (int) $row['ee_status_id'] : null);
        $inventory = $this->resolver->resolveInventory($siteId, $row['ref_unit_no'] ?? null);

        $rawDateOfIncident = $row['date_of_incident'] ?? null;
        $createdAt = $this->parseDateTimeOrNull($row['created_at'] ?? null);
        $dateOfIncident = $this->coalesceDateTime($rawDateOfIncident, $row['created_at'] ?? null);
        $uptime = $this->parseDateTimeOrNull($row['uptime'] ?? null);
        $updatedAt = $this->parseDateTimeOrNull($row['updated_at'] ?? null);

        if ($this->parseDateTimeOrNull($rawDateOfIncident) === null && $dateOfIncident !== null) {
            $this->logger?->warning(
                'downtime',
                $legacyId,
                "Zero date_of_incident for EE #{$legacyId}; used created_at.",
            );
        }

        $attributes = [
            'title' => $row['title'] ?? 'Imported Downtime',
            'site_id' => $siteId,
            'mhe_type_id' => $typeId,
            'mhe_category_id' => $categoryId,
            'mhe_inventory_id' => $inventory['inventory_id'],
            'supplier_id' => $inventory['supplier_id'],
            'ref_unit_no' => $row['ref_unit_no'] ?: config('eagle_eye.defaults.unknown_unit'),
            'date_of_incident' => $dateOfIncident,
            'uptime' => $uptime,
            'hours_down' => isset($row['hours_down']) ? (float) $row['hours_down'] : null,
            'time_from' => $this->parseTime($row['time_from'] ?? null),
            'time_to' => $this->parseTime($row['time_to'] ?? null),
            'root_cause' => $row['root_cause'] ?? null,
            'description' => $row['description'] ?? null,
            'w_spare_unit' => (bool) ($row['w_spare_unit'] ?? false),
            'status' => $status,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'posted_by' => $status === DowntimeStatus::Posted ? $createdBy : null,
            'posted_at' => $status === DowntimeStatus::Posted ? ($updatedAt ?? $createdAt) : null,
            'cancelled_by' => $status === DowntimeStatus::Cancelled ? $createdBy : null,
            'cancelled_at' => $status === DowntimeStatus::Cancelled ? ($updatedAt ?? $createdAt) : null,
        ];

        $downtime = MheDowntime::query()->updateOrCreate(
            ['legacy_eagle_eye_id' => $legacyId],
            $attributes,
        );

        if ($createdAt) {
            $downtime->created_at = $createdAt;
        }

        if ($updatedAt) {
            $downtime->updated_at = $updatedAt;
        }

        $downtime->save();

        return $downtime;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importActionPlan(array $row, int $localDowntimeId): MheDowntimeActionPlan
    {
        $legacyId = (int) $row['legacy_id'];
        $createdBy = $this->resolver->resolveUserId(isset($row['ee_created_by_id']) ? (int) $row['ee_created_by_id'] : null);
        $status = $this->resolver->mapActionPlanStatus(isset($row['ee_action_plan_status_id']) ? (int) $row['ee_action_plan_status_id'] : null);
        $createdAt = $this->parseDateTimeOrNull($row['created_at'] ?? null);
        $updatedAt = $this->parseDateTimeOrNull($row['updated_at'] ?? null);
        $description = (string) ($row['action_plan'] ?? '');
        $title = mb_substr(trim($description), 0, 255) ?: 'Imported Action Item';
        $timelineFrom = $this->parseDate($row['action_plan_date'] ?? null) ?? now()->toDateString();
        $timelineTo = $timelineFrom;

        $actionPlan = MheDowntimeActionPlan::query()->updateOrCreate(
            ['legacy_eagle_eye_id' => $legacyId],
            [
                'mhe_downtime_id' => $localDowntimeId,
                'action_plan_no' => 'EE-DT-AP-'.$legacyId,
                'title' => $title,
                'description' => $description,
                'responsible_person' => $row['responsible_person'] ?? 'Unknown',
                'action_plan_date' => $this->parseDate($row['action_plan_date'] ?? null),
                'timeline_from' => $timelineFrom,
                'timeline_to' => $timelineTo,
                'status' => $status,
                'date_implemented' => $this->parseDate($row['date_implemented'] ?? null),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ],
        );

        if ($createdAt) {
            $actionPlan->created_at = $createdAt;
        }

        if ($updatedAt) {
            $actionPlan->updated_at = $updatedAt;
        }

        $actionPlan->save();

        return $actionPlan;
    }

    /**
     * @param  class-string  $attachableType
     * @param  array<string, mixed>  $row
     */
    protected function importAttachment(string $attachableType, int $attachableId, array $row): Attachment
    {
        $legacyId = (int) $row['legacy_id'];
        $filename = basename((string) ($row['attachment'] ?? 'legacy-file'));
        $createdAt = $this->parseDateTimeOrNull($row['created_at'] ?? null);

        $attachment = Attachment::query()->updateOrCreate(
            [
                'attachable_type' => (new $attachableType)->getMorphClass(),
                'attachable_id' => $attachableId,
                'original_filename' => $filename,
            ],
            [
                'file_path' => 'eagle-eye/legacy/'.$filename,
                'mime_type' => null,
                'file_size' => null,
                'created_by' => $this->resolver->resolveUserId(null),
            ],
        );

        if ($createdAt) {
            $attachment->created_at = $createdAt;
            $attachment->updated_at = $createdAt;
            $attachment->save();
        }

        return $attachment;
    }

    protected function parseDateTimeOrNull(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (preg_match('/^0000-00-00/', $trimmed)) {
            return null;
        }

        try {
            $parsed = Carbon::parse($trimmed);
        } catch (\Throwable) {
            return null;
        }

        if ($parsed->year <= 0 || $parsed->year < 1970) {
            return null;
        }

        return $parsed;
    }

    protected function coalesceDateTime(?string $primary, ?string $fallback): ?Carbon
    {
        return $this->parseDateTimeOrNull($primary)
            ?? $this->parseDateTimeOrNull($fallback);
    }

    protected function parseDate(?string $value): ?string
    {
        return $this->parseDateTimeOrNull($value)?->toDateString();
    }

    protected function parseTime(?string $value): ?string
    {
        return $this->parseDateTimeOrNull($value)?->format('H:i:s');
    }
}
