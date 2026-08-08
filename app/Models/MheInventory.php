<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MheInventory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'district',
        'site',
        'site_id',
        'provider',
        'supplier_id',
        'brand',
        'model',
        'equipment_type',
        'mhe_type_id',
        'unit_no',
        'unit_role',
        'equipment_status',
        'client_fsc',
        'years_in_service',
        'total_kl_run',
        'total_down_hours',
        'battery_unit_no',
        'battery_years',
        'battery_man_count',
        'technicians_on_site',
        'branch_location',
        'total_technicians',
        'remarks',
        'next_pms_date',
        'last_pms_header_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'equipment_status' => RecordStatus::class,
            'next_pms_date' => 'date',
        ];
    }

    public function siteRelation(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function mheType(): BelongsTo
    {
        return $this->belongsTo(MheType::class);
    }

    public function lastPmsHeader(): BelongsTo
    {
        return $this->belongsTo(PmsHeader::class, 'last_pms_header_id');
    }

    public static function findBySiteAndUnit(int $siteId, string $unitNo, bool $activeOnly = false): ?self
    {
        $unit = trim($unitNo);

        if ($unit === '') {
            return null;
        }

        $query = static::query()
            ->where('site_id', $siteId)
            ->whereRaw('LOWER(unit_no) = ?', [strtolower($unit)]);

        if ($activeOnly) {
            $query->where('equipment_status', RecordStatus::Active);
        }

        return $query->first();
    }
}
