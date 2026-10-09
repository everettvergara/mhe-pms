<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportUserMigrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $username = $this->user()?->username;

        return is_string($username)
            && in_array($username, config('fsc_web_import.operator_usernames', []), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string'],
            'confirmation' => ['required', 'string', Rule::in([config('fsc_web_import.confirmation_phrase')])],
            'deactivate' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type the confirmation phrase exactly before importing.',
            'preview_token.required' => 'Run preview before importing.',
        ];
    }
}
