<?php

namespace App\Http\Requests;

use App\Enums\ActionPlanStatus;
use App\Enums\ProgressStatus;
use App\Models\ActionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActionPlanCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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

        if (! $actionPlan instanceof ActionPlan) {
            return false;
        }

        return in_array($actionPlan->status, [ActionPlanStatus::Pending, ActionPlanStatus::Rejected], true);
    }
}
