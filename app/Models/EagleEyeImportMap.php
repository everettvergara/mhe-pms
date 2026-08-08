<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EagleEyeImportMap extends Model
{
    protected $fillable = [
        'entity_type',
        'legacy_id',
        'local_id',
        'legacy_code',
    ];
}
