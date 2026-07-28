<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistrictRequest extends FormRequest
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
            'district_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('districts', 'district_code')->ignore($this->route('district')),
            ],
            'district_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('districts', 'district_name')->ignore($this->route('district')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
