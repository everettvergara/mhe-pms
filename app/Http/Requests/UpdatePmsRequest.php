<?php

namespace App\Http\Requests;

use App\Enums\ChecklistAnswer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePmsRequest extends FormRequest
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
            'site_id' => ['required', 'exists:sites,id'],
            'technician_name' => ['required', 'string', 'max:150'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'mhe_type_id' => ['required', 'exists:mhe_types,id'],
            'unit_number' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100'],
            'next_schedule_date' => ['required', 'date', 'after_or_equal:today'],
            'save_as' => ['nullable', Rule::in(['draft', 'final'])],
            'details' => ['nullable', 'array'],
            'details.*.id' => ['required', 'integer', 'exists:pms_details,id'],
            'details.*.answer' => ['nullable', Rule::enum(ChecklistAnswer::class)],
            'details.*.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
