<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
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
        $supplierRoleId = Role::query()->where('slug', Role::SLUG_SUPPLIER_USER)->value('id');
        $user = $this->route('user');
        $isSuperAdmin = $this->boolean('is_super_admin');
        $isSupplierRole = (int) $this->input('role_id') === (int) $supplierRoleId;

        return [
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_super_admin' => ['boolean'],
            'supplier_ids' => [
                Rule::prohibitedIf($isSuperAdmin),
                Rule::requiredIf(fn () => $isSupplierRole && ! $isSuperAdmin),
                'nullable',
                'array',
                'min:1',
            ],
            'supplier_ids.*' => ['integer', 'exists:suppliers,id'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'site_ids' => [
                Rule::prohibitedIf($isSuperAdmin),
                Rule::requiredIf(fn () => $isSupplierRole && ! $isSuperAdmin),
                'nullable',
                'array',
                'min:1',
            ],
            'site_ids.*' => ['integer', 'exists:sites,id'],
        ];
    }
}
