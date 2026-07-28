<?php

namespace App\Http\Requests;

use App\Enums\ChecklistAnswer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePmsRequest extends FormRequest
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
        $user = $this->user();
        $assignedSupplierIds = $user?->assignedSupplierIds() ?? [];

        return [
            'supplier_id' => [
                Rule::requiredIf(count($assignedSupplierIds) > 1),
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id'),
                Rule::in($assignedSupplierIds),
            ],
            'site_id' => ['required', 'exists:sites,id'],
            'technician_name' => ['required', 'string', 'max:150'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'mhe_type_id' => ['required', 'exists:mhe_types,id'],
            'unit_number' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100'],
            'next_schedule_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
