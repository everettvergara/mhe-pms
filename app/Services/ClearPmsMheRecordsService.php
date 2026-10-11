<?php

namespace App\Services;

use App\Models\ActionPlan;
use App\Models\ActionPlanComment;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\EagleEyeImportLog;
use App\Models\EagleEyeImportMap;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheDowntimeActionPlanComment;
use App\Models\MheDowntimeImportBatch;
use App\Models\MheInventory;
use App\Models\MheMonthlyCapacity;
use App\Models\NumberSequence;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearPmsMheRecordsService
{
    /**
     * @var list<string>
     */
    public const ACTIVITY_MODULES = [
        'pms',
        'action_plan',
        'mhe_downtimes',
        'mhe_downtime_action_plans',
    ];

    /**
     * @var list<string>
     */
    public const SEQUENCE_TYPES = [
        'pms',
        'action_plan',
        'downtime_action_plan',
    ];

    public function __construct(private ActivityLogService $activityLogService) {}

    /**
     * @return list<array{label: string, count: int}>
     */
    public function counts(): array
    {
        return [
            ['label' => 'PMS headers', 'count' => PmsHeader::query()->count()],
            ['label' => 'PMS details', 'count' => PmsDetail::query()->count()],
            ['label' => 'PMS action plans', 'count' => ActionPlan::query()->count()],
            ['label' => 'PMS action plan comments', 'count' => ActionPlanComment::query()->count()],
            ['label' => 'PMS attachments', 'count' => $this->pmsAttachments()->count()],
            ['label' => 'MHE downtimes', 'count' => MheDowntime::withTrashed()->count()],
            ['label' => 'MHE downtime action plans', 'count' => MheDowntimeActionPlan::withTrashed()->count()],
            ['label' => 'MHE downtime action plan comments', 'count' => MheDowntimeActionPlanComment::query()->count()],
            ['label' => 'MHE attachments', 'count' => $this->mheAttachments()->count()],
            ['label' => 'MHE monthly capacities', 'count' => MheMonthlyCapacity::query()->count()],
            ['label' => 'Eagle Eye import logs', 'count' => EagleEyeImportLog::query()->count()],
            ['label' => 'Eagle Eye import maps', 'count' => EagleEyeImportMap::query()->count()],
            ['label' => 'MHE downtime import batches', 'count' => MheDowntimeImportBatch::query()->count()],
            ['label' => 'Number sequences', 'count' => NumberSequence::query()->whereIn('type', self::SEQUENCE_TYPES)->count()],
            ['label' => 'Activity logs', 'count' => ActivityLog::query()->whereIn('module', self::ACTIVITY_MODULES)->count()],
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    public function clear(User $operator): array
    {
        $paths = [];

        $deleted = DB::transaction(function () use ($operator, &$paths): array {
            $paths = array_merge(
                $this->pmsAttachments()->pluck('file_path')->all(),
                $this->mheAttachments()->pluck('file_path')->all(),
            );

            MheInventory::withTrashed()->update([
                'next_pms_date' => null,
                'last_pms_header_id' => null,
            ]);

            $deleted = [
                ['label' => 'PMS attachments', 'count' => $this->pmsAttachments()->delete()],
                ['label' => 'MHE attachments', 'count' => $this->mheAttachments()->delete()],
                ['label' => 'PMS action plan comments', 'count' => ActionPlanComment::query()->delete()],
                ['label' => 'PMS action plans', 'count' => ActionPlan::query()->delete()],
                ['label' => 'PMS details', 'count' => PmsDetail::query()->delete()],
                ['label' => 'PMS headers', 'count' => PmsHeader::query()->delete()],
                ['label' => 'MHE downtime action plan comments', 'count' => MheDowntimeActionPlanComment::query()->delete()],
                ['label' => 'MHE downtime action plans', 'count' => MheDowntimeActionPlan::withTrashed()->forceDelete()],
                ['label' => 'MHE downtimes', 'count' => MheDowntime::withTrashed()->forceDelete()],
                ['label' => 'MHE monthly capacities', 'count' => MheMonthlyCapacity::query()->delete()],
                ['label' => 'Eagle Eye import logs', 'count' => EagleEyeImportLog::query()->delete()],
                ['label' => 'Eagle Eye import maps', 'count' => EagleEyeImportMap::query()->delete()],
                ['label' => 'MHE downtime import batches', 'count' => MheDowntimeImportBatch::query()->delete()],
                ['label' => 'Number sequences', 'count' => NumberSequence::query()->whereIn('type', self::SEQUENCE_TYPES)->delete()],
                ['label' => 'Activity logs', 'count' => ActivityLog::query()->whereIn('module', self::ACTIVITY_MODULES)->delete()],
            ];

            $this->activityLogService->log(
                $operator,
                'system',
                'clear_data',
                null,
                $this->description($operator, $deleted),
            );

            return $deleted;
        });

        $this->deleteFiles($paths);

        return $deleted;
    }

    /**
     * @param  list<array{label: string, count: int}>  $deleted
     */
    private function description(User $operator, array $deleted): string
    {
        $parts = [];

        foreach ($deleted as $row) {
            if ($row['count'] > 0) {
                $parts[] = $row['label'].' '.$row['count'];
            }
        }

        $summary = $operator->name.' cleared all PMS transactions and MHE downtime history.';

        if ($parts === []) {
            return $summary;
        }

        return $summary.' Removed '.implode(', ', $parts).'.';
    }

    /**
     * @param  list<string|null>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        $disk = Storage::disk('public');

        foreach (array_unique($paths) as $path) {
            if (is_string($path) && $path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    /**
     * @return Builder<Attachment>
     */
    private function pmsAttachments(): Builder
    {
        return Attachment::query()->whereIn('attachable_type', $this->morphTypes([
            PmsHeader::class,
            PmsDetail::class,
        ]));
    }

    /**
     * @return Builder<Attachment>
     */
    private function mheAttachments(): Builder
    {
        return Attachment::query()->whereIn('attachable_type', $this->morphTypes([
            MheDowntime::class,
            MheDowntimeActionPlan::class,
        ]));
    }

    /**
     * @param  list<class-string>  $models
     * @return list<string>
     */
    private function morphTypes(array $models): array
    {
        $types = [];

        foreach ($models as $model) {
            $types[] = $model;
            $types[] = (new $model)->getMorphClass();
        }

        return array_values(array_unique($types));
    }
}
