<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDistrictRequest extends FormRequest
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
            'district_code' => ['required', 'string', 'max:50', 'unique:districts,district_code'],
            'district_name' => ['required', 'string', 'max:150', 'unique:districts,district_name'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
