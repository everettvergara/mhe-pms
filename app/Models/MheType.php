<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MheType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
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

    public function pmsHeaders(): HasMany
    {
        return $this->hasMany(PmsHeader::class);
    }
}
