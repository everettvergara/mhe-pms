<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MheDowntimeActionPlanComment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'mhe_downtime_action_plan_id',
        'comment',
        'progress_status',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_status' => ProgressStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(MheDowntimeActionPlan::class, 'mhe_downtime_action_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
