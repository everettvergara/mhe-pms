<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMheDowntimeRequest extends StoreMheDowntimeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $downtime = $this->route('mhe_downtime');

        if ($downtime?->isPosted()) {
            return [
                'date_of_incident' => ['required', 'date'],
                'uptime' => ['nullable', 'date', 'after_or_equal:date_of_incident'],
            ];
        }

        return [
            ...$this->baseRules(),
            'save_as' => ['nullable', Rule::in(['draft', 'post', 'final'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->route('mhe_downtime')?->isPosted()) {
            return;
        }

        parent::withValidator($validator);
    }
}
