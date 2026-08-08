<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EagleEyeImportLog extends Model
{
    protected $fillable = [
        'batch_id',
        'level',
        'entity_type',
        'legacy_id',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }
}
