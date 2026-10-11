<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MigrateDowntimeRequest extends FormRequest
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
            'preview_token' => ['required', 'string'],
            'confirmation' => ['required', 'string', Rule::in([config('fsc_web_import.confirmation_phrase')])],
            'migrate_all' => ['nullable', 'boolean'],
            'import_ids' => ['required_unless:migrate_all,1', 'array'],
            'import_ids.*' => ['integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type the confirmation phrase exactly before migrating.',
            'preview_token.required' => 'Run preview before migrating.',
            'import_ids.required_unless' => 'Select at least one downtime to migrate.',
        ];
    }

    /**
     * @return list<int>|null
     */
    public function selectedLegacyIds(): ?array
    {
        if ($this->boolean('migrate_all')) {
            return null;
        }

        return array_values(array_unique(array_map(intval(...), (array) $this->input('import_ids', []))));
    }
}
