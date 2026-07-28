<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectActionPlanRequest extends FormRequest
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
            'rejection_remarks' => ['required', 'string', 'max:2000'],
        ];
    }
}
