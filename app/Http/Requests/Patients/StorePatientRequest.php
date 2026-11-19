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

        $this->merge([
            'rm_number' => is_string($this->input('rm_number')) ? strtoupper(trim($this->input('rm_number'))) : $this->input('rm_number'),
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'address' => is_string($address) && trim($address) !== '' ? trim($address) : null,
            'phone' => is_string($phone) && trim($phone) !== '' ? trim($phone) : null,
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
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function targetPatient(): mixed
    {
        return null;
    }
}
