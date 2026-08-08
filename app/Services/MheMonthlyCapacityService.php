<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\MheDowntime;
use App\Models\MheInventory;
use App\Models\MheMonthlyCapacity;
use App\Models\MheType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MheMonthlyCapacityService
{
    public function daysInMonth(int $yyyymm): int
    {
        $year = intdiv($yyyymm, 100);
        $month = $yyyymm % 100;

        return Carbon::createFromDate($year, $month, 1)->daysInMonth;
    }

    public function availableHoursForMonth(int $yyyymm): int
    {
        return $this->daysInMonth($yyyymm) * (int) config('mhe.default_daily_hours', 24);
    }

    /**
     * @return array{inserted: int, skipped: int}
     */
    public function generateForMonth(int $yyyymm, bool $force = false): array
    {
        $inserted = 0;
        $skipped = 0;
        $availableHours = $this->availableHoursForMonth($yyyymm);
        $now = now();

        $inventories = MheInventory::query()
            ->where('equipment_status', RecordStatus::Active)
            ->whereNotNull('site_id')
            ->get();

        foreach ($inventories as $inventory) {
            $mheTypeId = $this->resolveMheTypeId($inventory);

            if ($mheTypeId === null) {
                $skipped++;

                continue;
            }

            $exists = MheMonthlyCapacity::query()
                ->where('yyyymm', $yyyymm)
                ->where('mhe_inventory_id', $inventory->id)
                ->exists();

            if ($exists && ! $force) {
                $skipped++;

                continue;
            }

            if ($exists && $force) {
                MheMonthlyCapacity::query()
                    ->where('yyyymm', $yyyymm)
                    ->where('mhe_inventory_id', $inventory->id)
                    ->where('is_override', false)
                    ->delete();
            }

            if (! $exists || $force) {
                MheMonthlyCapacity::query()->updateOrCreate(
                    [
                        'yyyymm' => $yyyymm,
                        'mhe_inventory_id' => $inventory->id,
                    ],
                    [
                        'site_id' => $inventory->site_id,
                        'mhe_type_id' => $mheTypeId,
                        'supplier_id' => $inventory->supplier_id,
                        'unit_no' => $inventory->unit_no ?: 'N/A',
                        'available_hours' => $availableHours,
                        'is_override' => false,
                        'generated_at' => $now,
                    ],
                );
                $inserted++;
            }
        }

        return compact('inserted', 'skipped');
    }

    public function resolveMheTypeId(MheInventory $inventory): ?int
    {
        if ($inventory->mhe_type_id) {
            return (int) $inventory->mhe_type_id;
        }

        $equipmentType = trim((string) $inventory->equipment_type);
        if ($equipmentType !== '' && strcasecmp($equipmentType, 'N/A') !== 0) {
            $matched = MheType::query()
                ->where(function ($query) use ($equipmentType) {
                    $query->whereRaw('LOWER(description) = ?', [strtolower($equipmentType)])
                        ->orWhereRaw('LOWER(code) = ?', [strtolower($equipmentType)]);
                })
                ->value('id');

            if ($matched) {
                return (int) $matched;
            }
        }

        $fromDowntime = MheDowntime::query()
            ->whereNotNull('mhe_type_id')
            ->where(function ($query) use ($inventory) {
                $query->where('mhe_inventory_id', $inventory->id);

                if ($inventory->site_id && filled($inventory->unit_no)) {
                    $query->orWhere(function ($nested) use ($inventory) {
                        $nested->where('site_id', $inventory->site_id)
                            ->where('ref_unit_no', $inventory->unit_no);
                    });
                }
            })
            ->orderByDesc('date_of_incident')
            ->value('mhe_type_id');

        if ($fromDowntime) {
            return (int) $fromDowntime;
        }

        $unknownTypeId = MheType::query()
            ->where('code', 'EE-UNK')
            ->value('id');

        return $unknownTypeId ? (int) $unknownTypeId : null;
    }

    /**
     * @return list<int>
     */
    public function monthsInRange(Carbon $from, Carbon $to): array
    {
        $months = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $months[] = (int) $cursor->format('Ym');
            $cursor->addMonth();
        }

        return array_values(array_unique($months));
    }

    public function ensureSnapshotsForRange(Carbon $from, Carbon $to): void
    {
        foreach ($this->monthsInRange($from, $to) as $yyyymm) {
            if (MheMonthlyCapacity::query()->where('yyyymm', $yyyymm)->doesntExist()) {
                $this->generateForMonth($yyyymm);
            }
        }
    }

    /**
     * Sum available hours for filtered capacity rows, pro-rated for partial months in a date range.
     *
     * @param  callable(\Illuminate\Database\Query\Builder): void  $scopeCapacity
     */
    public function availableHoursForDateRange(
        Carbon $from,
        Carbon $to,
        callable $scopeCapacity,
    ): float {
        $total = 0.0;

        foreach ($this->monthsInRange($from, $to) as $yyyymm) {
            $monthStart = Carbon::createFromFormat('Ym-d', $yyyymm.'-01')->startOfDay();
            $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
            $rangeStart = $from->copy()->startOfDay()->max($monthStart);
            $rangeEnd = $to->copy()->startOfDay()->min($monthEnd);
            $daysInRange = $rangeStart->diffInDays($rangeEnd) + 1;

            if ($daysInRange <= 0) {
                continue;
            }

            $query = DB::table('mhe_monthly_capacities')->where('yyyymm', $yyyymm);
            $scopeCapacity($query);
            $monthCapacity = (float) $query->sum('available_hours');

            $daysInMonth = $this->daysInMonth($yyyymm);
            $total += $monthCapacity * ($daysInRange / $daysInMonth);
        }

        return $total;
    }
}
