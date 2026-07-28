<?php

namespace App\Models;

use App\Enums\PmsActionPlanStatus;
use App\Enums\PmsStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PmsHeader extends Model
{
    protected $fillable = [
        'pms_no',
        'supplier_id',
        'site_id',
        'technician_name',
        'date_from',
        'date_to',
        'next_schedule_date',
        'mhe_type_id',
        'unit_number',
        'serial_number',
        'status',
        'action_plan_status',
        'submitted_by',
        'submitted_at',
        'cancelled_by',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'datetime',
            'date_to' => 'datetime',
            'next_schedule_date' => 'date',
            'status' => PmsStatus::class,
            'action_plan_status' => PmsActionPlanStatus::class,
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function mheType(): BelongsTo
    {
        return $this->belongsTo(MheType::class);
    }

    public function pmsDetails(): HasMany
    {
        return $this->hasMany(PmsDetail::class);
    }

    public function actionPlans(): HasManyThrough
    {
        return $this->hasManyThrough(ActionPlan::class, PmsDetail::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === PmsStatus::Draft;
    }

    public function isCancelled(): bool
    {
        return $this->status === PmsStatus::Cancelled;
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, [PmsStatus::WithFindings, PmsStatus::NoFindings], true);
    }
}
