<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
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
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'site_ids' => ['nullable', 'array'],
            'site_ids.*' => ['integer', 'exists:sites,id'],
            'period_from' => ['nullable', 'date_format:Y-m'],
            'period_to' => ['nullable', 'date_format:Y-m', 'after_or_equal:period_from'],
            'view' => ['nullable', 'string', 'in:table,chart-month-supplier,chart-supplier-site'],
            'mhe_type_id' => ['nullable', 'integer', 'exists:mhe_types,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
