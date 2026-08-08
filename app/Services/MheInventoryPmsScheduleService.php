<?php

namespace App\Services;

use App\Enums\PmsStatus;
use App\Models\MheInventory;
use App\Models\PmsHeader;
use Illuminate\Support\Facades\Log;

class MheInventoryPmsScheduleService
{
    public function syncFromPmsHeader(PmsHeader $pms): void
    {
        if (! $pms->isSubmitted() || blank($pms->next_schedule_date)) {
            return;
        }

        $inventory = MheInventory::query()
            ->where('site_id', $pms->site_id)
            ->whereRaw('LOWER(unit_no) = ?', [strtolower(trim((string) $pms->unit_number))])
            ->first();

        if ($inventory === null) {
            Log::info('PMS finalize: no inventory match for schedule sync.', [
                'pms_id' => $pms->id,
                'site_id' => $pms->site_id,
                'unit_number' => $pms->unit_number,
            ]);

            return;
        }

        $inventory->update([
            'next_pms_date' => $pms->next_schedule_date,
            'last_pms_header_id' => $pms->id,
            'updated_by' => $pms->updated_by,
        ]);
    }

    public function recalculateForUnit(int $siteId, string $unitNo): void
    {
        $unit = trim($unitNo);

        if ($unit === '') {
            return;
        }

        $inventory = MheInventory::query()
            ->where('site_id', $siteId)
            ->whereRaw('LOWER(unit_no) = ?', [strtolower($unit)])
            ->first();

        if ($inventory === null) {
            return;
        }

        $latestPms = PmsHeader::query()
            ->where('site_id', $siteId)
            ->whereRaw('LOWER(unit_number) = ?', [strtolower($unit)])
            ->whereIn('status', [PmsStatus::WithFindings, PmsStatus::NoFindings])
            ->whereNotNull('next_schedule_date')
            ->orderByDesc('submitted_at')
            ->first();

        $inventory->update([
            'next_pms_date' => $latestPms?->next_schedule_date,
            'last_pms_header_id' => $latestPms?->id,
        ]);
    }
}
