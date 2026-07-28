<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'created_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolvePmsHeader(): ?PmsHeader
    {
        $attachable = $this->attachable;

        if ($attachable instanceof PmsHeader) {
            return $attachable;
        }

        if ($attachable instanceof PmsDetail) {
            return $attachable->pmsHeader;
        }

        return null;
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
