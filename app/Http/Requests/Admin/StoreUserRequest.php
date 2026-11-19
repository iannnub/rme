<?php

namespace App\Http\Requests\Admin;

use App\Enums\NurseType;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $role = is_string($this->input('role')) ? $this->input('role') : null;
        $email = $this->input('email');
        $nurseType = $this->input('nurse_type');
        $nip = $this->input('nip');
        $sip = $this->input('sip');

        $this->merge([
            'username' => is_string($this->input('username')) ? strtolower(trim($this->input('username'))) : $this->input('username'),
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($email) && trim($email) !== '' ? strtolower(trim($email)) : null,
            'nurse_type' => $role === Role::Perawat->value && is_string($nurseType) && trim($nurseType) !== ''
                ? trim($nurseType)
                : null,
            'nip' => is_string($nip) && trim($nip) !== '' ? trim($nip) : null,
            'sip' => is_string($sip) && trim($sip) !== '' ? trim($sip) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'password' => ['required', Password::defaults(), 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function baseRules(): array
    {
        $targetUser = $this->targetUser();

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'lowercase',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique(User::class, 'username')->ignore($targetUser),
            ],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($targetUser),
            ],
            'role' => ['required', Rule::enum(Role::class)],
            'nurse_type' => ['nullable', 'required_if:role,perawat', Rule::enum(NurseType::class)],
            'nip' => ['nullable', 'required_if:role,perawat,kepala_ruangan', 'string', 'max:50'],
            'sip' => ['nullable', 'required_if:role,dokter_operator,dokter_anestesi', 'string', 'max:100'],
        ];
    }

    protected function targetUser(): mixed
    {
        return null;
    }
}
