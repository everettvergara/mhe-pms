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

    public function resolveMheDowntime(): ?MheDowntime
    {
        $attachable = $this->attachable;

        if ($attachable instanceof MheDowntime) {
            return $attachable;
        }

        if ($attachable instanceof MheDowntimeActionPlan) {
            return $attachable->mheDowntime;
        }

        return null;
    }

    public function url(): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function isAvailable(): bool
    {
        if ($this->file_path === null || $this->file_path === '') {
            return false;
        }

        return Storage::disk('public')->exists($this->file_path);
    }

    public function isLegacyPlaceholder(): bool
    {
        return str_starts_with((string) $this->file_path, 'eagle-eye/legacy/') && ! $this->isAvailable();
    }

    public function displayLabel(): string
    {
        return $this->original_filename;
    }
}
