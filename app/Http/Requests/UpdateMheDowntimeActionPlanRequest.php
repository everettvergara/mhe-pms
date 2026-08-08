<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMheDowntimeActionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actionPlan = $this->route('action_plan');

        return $actionPlan instanceof \App\Models\MheDowntimeActionPlan
            && $this->user()->can('update', $actionPlan);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'responsible_person' => ['required', 'string', 'max:150'],
            'timeline_from' => ['required', 'date'],
            'timeline_to' => ['required', 'date', 'after_or_equal:timeline_from'],
        ];
    }
}
