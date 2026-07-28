<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChecklistItemRequest extends FormRequest
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
            'checklist_group_id' => ['required', 'exists:checklist_groups,id'],
            'sequence' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:500'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }
}
