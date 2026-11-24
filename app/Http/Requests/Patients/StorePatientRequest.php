<?php

namespace App\Http\Requests\Patients;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Patient::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $address = $this->input('address');
        $phone = $this->input('phone');
        $identity = $this->input('identity_number');
        $guardianName = $this->input('guardian_name');
        $guardianRelation = $this->input('guardian_relation');
        $guardianPhone = $this->input('guardian_phone');

        $this->merge([
            'rm_number' => is_string($this->input('rm_number')) ? strtoupper(trim($this->input('rm_number'))) : $this->input('rm_number'),
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'identity_number' => is_string($identity) && trim($identity) !== '' ? trim($identity) : null,
            'address' => is_string($address) && trim($address) !== '' ? trim($address) : null,
            'phone' => is_string($phone) && trim($phone) !== '' ? trim($phone) : null,
            'guardian_name' => is_string($guardianName) && trim($guardianName) !== '' ? trim($guardianName) : null,
            'guardian_relation' => is_string($guardianRelation) && trim($guardianRelation) !== '' ? trim($guardianRelation) : null,
            'guardian_phone' => is_string($guardianPhone) && trim($guardianPhone) !== '' ? trim($guardianPhone) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rm_number' => ['required', 'string', 'max:50', Rule::unique(Patient::class, 'rm_number')->ignore($this->targetPatient())],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_relation' => ['nullable', 'string', 'max:80'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function targetPatient(): mixed
    {
        return null;
    }
}
