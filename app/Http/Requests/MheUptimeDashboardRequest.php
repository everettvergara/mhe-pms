<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MheUptimeDashboardRequest extends FormRequest
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
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'mhe_type_id' => ['nullable', 'integer', 'exists:mhe_types,id'],
            'grain' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'quarterly'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $timezone = config('mhe.scheduler_timezone', 'Asia/Manila');
        $now = now($timezone);

        $this->merge([
            'date_from' => $this->input('date_from', $now->copy()->startOfMonth()->toDateString()),
            'date_to' => $this->input('date_to', $now->toDateString()),
            'grain' => $this->input('grain', 'monthly'),
        ]);
    }
}
