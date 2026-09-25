<?php

namespace App\Models;

use App\Enums\DowntimeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MheDowntime extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_eagle_eye_id',
        'title',
        'site_id',
        'mhe_type_id',
        'mhe_category_id',
        'mhe_inventory_id',
        'supplier_id',
        'ref_unit_no',
        'date_of_incident',
        'uptime',
        'hours_down',
        'time_from',
        'time_to',
        'root_cause',
        'description',
        'w_spare_unit',
        'status',
        'posted_by',
        'posted_at',
        'cancelled_by',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_incident' => 'datetime',
            'uptime' => 'datetime',
            'hours_down' => 'decimal:2',
            'w_spare_unit' => 'boolean',
            'status' => DowntimeStatus::class,
            'posted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function mheType(): BelongsTo
    {
        return $this->belongsTo(MheType::class);
    }

    public function mheCategory(): BelongsTo
    {
        return $this->belongsTo(MheCategory::class);
    }

    public function mheInventory(): BelongsTo
    {
        return $this->belongsTo(MheInventory::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function actionPlans(): HasMany
    {
        return $this->hasMany(MheDowntimeActionPlan::class);
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

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isDraft(): bool
    {
        return $this->status === DowntimeStatus::Draft;
    }

    public function isPosted(): bool
    {
        return $this->status === DowntimeStatus::Posted;
    }

    public function isCancelled(): bool
    {
        return $this->status === DowntimeStatus::Cancelled;
    }

    /**
     * @param  Builder<MheDowntime>  $query
     */
    public function scopeNeedsActionPlan(Builder $query): Builder
    {
        return $query
            ->where('status', DowntimeStatus::Posted)
            ->whereDoesntHave('actionPlans');
    }

    /**
     * Posted downtime that has not been brought back up.
     *
     * @param  Builder<MheDowntime>  $query
     */
    public function scopeCurrentlyDown(Builder $query): Builder
    {
        return $query
            ->where('status', DowntimeStatus::Posted)
            ->whereNull('uptime');
    }
}
