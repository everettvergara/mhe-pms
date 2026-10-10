<?php

namespace App\Models;

use App\Enums\DowntimeActionPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MheDowntimeActionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_eagle_eye_id',
        'action_plan_no',
        'mhe_downtime_id',
        'title',
        'description',
        'responsible_person',
        'action_plan_date',
        'timeline_from',
        'timeline_to',
        'status',
        'unit_safe_guaranteed',
        'unit_safe_guaranteed_by',
        'unit_safe_guaranteed_at',
        'date_implemented',
        'confirmed_by',
        'confirmed_at',
        'rejected_by',
        'rejected_at',
        'rejection_remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DowntimeActionPlanStatus::class,
            'unit_safe_guaranteed' => 'boolean',
            'unit_safe_guaranteed_at' => 'datetime',
            'action_plan_date' => 'date',
            'timeline_from' => 'date',
            'timeline_to' => 'date',
            'date_implemented' => 'datetime',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function mheDowntime(): BelongsTo
    {
        return $this->belongsTo(MheDowntime::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MheDowntimeActionPlanComment::class)->orderBy('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function unitSafeGuarantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unit_safe_guaranteed_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isPending(): bool
    {
        return $this->status === DowntimeActionPlanStatus::Pending;
    }

    public function isWaitingForConfirmation(): bool
    {
        return $this->status === DowntimeActionPlanStatus::WaitingForFastConfirmation;
    }

    public function isConfirmed(): bool
    {
        return $this->status === DowntimeActionPlanStatus::Confirmed;
    }

    public function isRejected(): bool
    {
        return $this->status === DowntimeActionPlanStatus::Rejected;
    }

    public function isCancelled(): bool
    {
        return $this->status === DowntimeActionPlanStatus::Cancelled;
    }

    public function parentShowUrl(): ?string
    {
        if ($this->mhe_downtime_id === null) {
            return null;
        }

        return route('mhe-downtimes.show', [
            'mhe_downtime' => $this->mhe_downtime_id,
            'action_plan' => $this->id,
        ]);
    }
}
