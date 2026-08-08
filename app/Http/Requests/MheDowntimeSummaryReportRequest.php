<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MheDowntimeSummaryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('mhe-downtimes.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'as_of_date' => ['nullable', 'date'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'is_pending' => ['nullable', 'boolean'],
            'is_implemented' => ['nullable', 'boolean'],
            'is_no_action_plan' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'as_of_date' => $this->input('as_of_date', now('Asia/Manila')->toDateString()),
            'is_pending' => $this->boolean('is_pending', true),
            'is_implemented' => $this->boolean('is_implemented', true),
            'is_no_action_plan' => $this->boolean('is_no_action_plan', true),
        ]);
    }
}
