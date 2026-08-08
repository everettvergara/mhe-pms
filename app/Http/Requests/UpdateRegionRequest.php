<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
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
            'region_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('regions', 'region_code')->ignore($this->route('region')),
            ],
            'region_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('regions', 'region_name')->ignore($this->route('region')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
