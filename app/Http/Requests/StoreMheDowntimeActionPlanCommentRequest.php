<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMheDowntimeActionPlanCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actionPlan = $this->route('action_plan');

        return $actionPlan instanceof \App\Models\MheDowntimeActionPlan
            && $this->user()->can('comment', $actionPlan);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
            'progress_status' => ['required', Rule::enum(\App\Enums\ProgressStatus::class)],
        ];
    }
}
