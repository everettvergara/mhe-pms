<?php

namespace App\Http\Requests;

use App\Enums\MheDowntimeImportSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RunMheDowntimeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('mhe-downtimes.import') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => MheDowntimeImportSource::EagleEyeMysql->value,
            'dry_run' => $this->boolean('dry_run'),
            'import_users' => $this->has('import_users') ? $this->boolean('import_users') : true,
            'import_downtimes' => $this->has('import_downtimes') ? $this->boolean('import_downtimes') : true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dryRun = $this->boolean('dry_run');
        $confirmationPhrase = config('fsc_web_import.confirmation_phrase');

        return [
            'source' => ['required', Rule::in([MheDowntimeImportSource::EagleEyeMysql->value])],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'import_users' => ['boolean'],
            'import_downtimes' => ['boolean'],
            'dry_run' => ['boolean'],
            'confirmation' => [
                Rule::requiredIf(! $dryRun),
                'nullable',
                'string',
                Rule::in([$confirmationPhrase]),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('import_users') && ! $this->boolean('import_downtimes')) {
                $validator->errors()->add('import_users', 'Select at least one import target.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'You must type the exact confirmation phrase to proceed.',
            'confirmation.required' => 'Confirmation is required for live imports.',
        ];
    }
}
