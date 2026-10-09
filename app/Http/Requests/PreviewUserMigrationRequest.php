<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewUserMigrationRequest extends FormRequest
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
            'districts' => ['required', 'array', 'min:1'],
            'districts.*' => ['required', 'string', 'max:30'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
