<?php

namespace App\Http\Requests;

use App\Services\PmsScheduleReportService;
use App\Support\DistrictSiteFilters;
use Illuminate\Foundation\Http\FormRequest;

class PmsScheduleReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dashboard.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        [$minYear, $maxYear] = PmsScheduleReportService::yearRange();

        return [
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'year' => [
                'nullable',
                'integer',
                'between:'.$minYear.','.$maxYear,
            ],
            'month' => [
                'nullable',
                'integer',
                'between:1,12',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $districtId = $this->filled('district_id') ? $this->input('district_id') : null;

        $this->merge([
            'district_id' => $districtId,
            'site_id' => DistrictSiteFilters::scopedSiteId(
                $districtId,
                $this->filled('site_id') ? $this->input('site_id') : null,
            ),
        ]);
    }

    /**
     * @return array{district_id: int|null, site_id: int|null, year: int, month: int}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'district_id' => isset($validated['district_id']) ? (int) $validated['district_id'] : null,
            'site_id' => isset($validated['site_id']) ? (int) $validated['site_id'] : null,
            'year' => isset($validated['year']) ? (int) $validated['year'] : (int) now()->year,
            'month' => isset($validated['month']) ? (int) $validated['month'] : (int) now()->month,
        ];
    }
}
