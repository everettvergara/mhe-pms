<?php

namespace App\Http\Requests;

use App\Support\DistrictSiteFilters;
use Illuminate\Foundation\Http\FormRequest;

class MheDowntimeSummaryActionPlanRequest extends FormRequest
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'is_pending' => ['nullable', 'boolean'],
            'is_implemented' => ['nullable', 'boolean'],
            'is_no_action_plan' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $today = now('Asia/Manila');

        $this->merge([
            'date_from' => $this->input('date_from') ?: $today->copy()->subYear()->toDateString(),
            'date_to' => $this->input('date_to') ?: $today->toDateString(),
            'is_pending' => $this->boolean('is_pending', true),
            'is_implemented' => $this->boolean('is_implemented', true),
            'is_no_action_plan' => $this->boolean('is_no_action_plan', true),
            'site_id' => DistrictSiteFilters::scopedSiteId($this->input('district_id'), $this->input('site_id')),
        ]);
    }
}
