<?php

namespace App\Http\Requests;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\ProgressStatus;
use App\Models\MheDowntimeActionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMheDowntimeActionPlanCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actionPlan = $this->route('action_plan');

        return $actionPlan instanceof MheDowntimeActionPlan
            && $this->user()->can('comment', $actionPlan);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
            'progress_status' => ['required', Rule::enum(ProgressStatus::class)],
            'unit_safe_guaranteed' => $this->isImplementing() ? ['accepted'] : ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit_safe_guaranteed.accepted' => 'You must guarantee that the unit is safe to use.',
        ];
    }

    protected function isImplementing(): bool
    {
        if ($this->input('progress_status') !== ProgressStatus::Implemented->value) {
            return false;
        }

        $actionPlan = $this->route('action_plan');

        if (! $actionPlan instanceof MheDowntimeActionPlan) {
            return false;
        }

        return in_array($actionPlan->status, [DowntimeActionPlanStatus::Pending, DowntimeActionPlanStatus::Rejected], true);
    }
}
