<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewDowntimeMigrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'district' => ['required', 'string', 'max:30'],
            'sites' => ['nullable', 'array'],
            'sites.*' => ['integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['integer', Rule::in([1, 2, 3, 4])],
            'tab' => ['nullable', 'string', Rule::in(['ready', 'blocked', 'already_migrated'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
