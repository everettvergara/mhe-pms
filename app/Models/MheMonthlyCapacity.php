<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MheMonthlyCapacity extends Model
{
    protected $fillable = [
        'yyyymm',
        'mhe_inventory_id',
        'site_id',
        'mhe_type_id',
        'supplier_id',
        'unit_no',
        'available_hours',
        'is_override',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_override' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    public function mheInventory(): BelongsTo
    {
        return $this->belongsTo(MheInventory::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function mheType(): BelongsTo
    {
        return $this->belongsTo(MheType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
