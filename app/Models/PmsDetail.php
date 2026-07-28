<?php

namespace App\Models;

use App\Enums\ChecklistAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PmsDetail extends Model
{
    protected $fillable = [
        'pms_header_id',
        'checklist_item_id',
        'answer',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'answer' => ChecklistAnswer::class,
        ];
    }

    public function pmsHeader(): BelongsTo
    {
        return $this->belongsTo(PmsHeader::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function actionPlans(): HasMany
    {
        return $this->hasMany(ActionPlan::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isFinding(): bool
    {
        return $this->answer === ChecklistAnswer::NoGood;
    }
}
