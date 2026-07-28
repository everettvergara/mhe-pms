<?php

namespace App\Models;

use App\Enums\ActionPlanStatus;
use App\Enums\PmsStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionPlan extends Model
{
    protected $fillable = [
        'action_plan_no',
        'pms_detail_id',
        'title',
        'description',
        'responsible_person',
        'timeline_from',
        'timeline_to',
        'status',
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
            'timeline_from' => 'date',
            'timeline_to' => 'date',
            'status' => ActionPlanStatus::class,
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function pmsDetail(): BelongsTo
    {
        return $this->belongsTo(PmsDetail::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ActionPlanComment::class)->orderBy('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * @param  Builder<ActionPlan>  $query
     * @return Builder<ActionPlan>
     */
    public function scopeWherePmsSubmitted(Builder $query): Builder
    {
        return $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->whereIn('status', [
            PmsStatus::WithFindings,
            PmsStatus::NoFindings,
        ]));
    }
}
