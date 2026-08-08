<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMheInventoryRequest extends FormRequest
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
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'mhe_type_id' => ['nullable', 'exists:mhe_types,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'unit_no' => ['required', 'string', 'max:255'],
            'unit_role' => ['nullable', 'string', 'max:255'],
            'equipment_status' => ['required', Rule::enum(RecordStatus::class)],
            'client_fsc' => ['nullable', 'string', 'max:255'],
            'years_in_service' => ['nullable', 'string', 'max:255'],
            'total_kl_run' => ['nullable', 'string', 'max:255'],
            'total_down_hours' => ['nullable', 'string', 'max:255'],
            'battery_unit_no' => ['nullable', 'string', 'max:255'],
            'battery_years' => ['nullable', 'string', 'max:255'],
            'battery_man_count' => ['nullable', 'string', 'max:255'],
            'technicians_on_site' => ['nullable', 'string', 'max:255'],
            'branch_location' => ['nullable', 'string', 'max:255'],
            'total_technicians' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
