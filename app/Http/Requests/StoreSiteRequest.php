<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('region_id') === '' || $this->input('region_id') === null) {
            $this->merge(['region_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_code' => ['required', 'string', 'max:50', 'unique:sites,site_code'],
            'site_name' => ['required', 'string', 'max:150', 'unique:sites,site_name'],
            'district_id' => ['required', 'exists:districts,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
