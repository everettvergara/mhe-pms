<?php

namespace App\Http\Requests;

use App\Support\DistrictSiteFilters;
use Illuminate\Foundation\Http\FormRequest;

class MheDowntimeUtilizationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('mhe-utilization.view') ?? false;
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $now = now();

        $this->merge([
            'date_from' => $this->input('date_from', $now->copy()->startOfWeek()->toDateString()),
            'date_to' => $this->input('date_to', $now->copy()->endOfWeek()->toDateString()),
            'site_id' => DistrictSiteFilters::scopedSiteId($this->input('district_id'), $this->input('site_id')),
        ]);
    }
}
