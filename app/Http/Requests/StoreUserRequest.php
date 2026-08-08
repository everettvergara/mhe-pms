<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Http\Requests\Concerns\ValidatesUserAssignments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ValidatesUserAssignments;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_super_admin' => $this->boolean('is_super_admin'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_super_admin' => ['boolean'],
            'supplier_ids' => [
                Rule::prohibitedIf($this->isSuperAdminInput()),
                Rule::requiredIf(fn () => $this->requiresSupplierAssignment()),
                'nullable',
                'array',
                'min:1',
            ],
            'supplier_ids.*' => ['integer', 'exists:suppliers,id'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'site_ids' => [
                Rule::prohibitedIf($this->isSuperAdminInput()),
                Rule::requiredIf(fn () => $this->requiresSiteAssignment()),
                'nullable',
                'array',
                'min:1',
            ],
            'site_ids.*' => ['integer', 'exists:sites,id'],
        ];
    }
}
