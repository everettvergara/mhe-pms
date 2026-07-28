<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistGroupRequest extends FormRequest
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
            'group_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('checklist_groups', 'group_name')->ignore($this->route('checklist_group')),
            ],
            'sequence' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
