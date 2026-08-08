<?php

namespace App\Models;

use App\Enums\FscWebImportStatus;
use App\Enums\MheDowntimeImportSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MheDowntimeImportBatch extends Model
{
    protected $fillable = [
        'batch_id',
        'source',
        'source_summary',
        'dry_run',
        'status',
        'phase',
        'progress_percent',
        'processed_count',
        'total_count',
        'status_message',
        'result',
        'error_message',
        'users',
        'users_purged',
        'downtimes_purged',
        'action_plans_purged',
        'downtimes',
        'action_plans',
        'downtime_attachments',
        'action_plan_attachments',
        'warnings',
        'errors',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'source' => MheDowntimeImportSource::class,
            'status' => FscWebImportStatus::class,
            'dry_run' => 'boolean',
            'result' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
