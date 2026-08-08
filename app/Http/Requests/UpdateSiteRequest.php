<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteRequest extends FormRequest
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
            'site_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sites', 'site_code')->ignore($this->route('site')),
            ],
            'site_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('sites', 'site_name')->ignore($this->route('site')),
            ],
            'district_id' => ['required', 'exists:districts,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
