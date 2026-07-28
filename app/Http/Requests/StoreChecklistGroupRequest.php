<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistGroupRequest extends FormRequest
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
            'group_name' => ['required', 'string', 'max:150', 'unique:checklist_groups,group_name'],
            'sequence' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
