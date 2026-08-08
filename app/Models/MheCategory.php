<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MheCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }

    public function mheDowntimes(): HasMany
    {
        return $this->hasMany(MheDowntime::class);
    }
}
