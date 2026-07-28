<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMheTypeRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('mhe_types', 'code')->ignore($this->route('mhe_type')),
            ],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
