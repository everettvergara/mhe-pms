<?php

namespace App\Http\Requests;

use App\Support\DistrictSiteFilters;
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
            'ap_date_from' => ['nullable', 'date'],
            'ap_date_to' => ['nullable', 'date', 'after_or_equal:ap_date_from'],
            'ap_district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'ap_site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'ap_is_pending' => ['nullable', 'boolean'],
            'ap_is_implemented' => ['nullable', 'boolean'],
            'ap_is_no_action_plan' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $today = now('Asia/Manila');

        $this->merge([
            'as_of_date' => $this->input('as_of_date', $today->toDateString()),
            'ap_date_from' => $this->input('ap_date_from') ?: $today->copy()->subYear()->toDateString(),
            'ap_date_to' => $this->input('ap_date_to') ?: $today->toDateString(),
            'ap_is_pending' => $this->boolean('ap_is_pending', true),
            'ap_is_implemented' => $this->boolean('ap_is_implemented', true),
            'ap_is_no_action_plan' => $this->boolean('ap_is_no_action_plan', true),
            'site_id' => DistrictSiteFilters::scopedSiteId($this->input('district_id'), $this->input('site_id')),
            'ap_site_id' => DistrictSiteFilters::scopedSiteId($this->input('ap_district_id'), $this->input('ap_site_id')),
        ]);
    }
}
