<?php

namespace App\Http\Requests\Cases;

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurgeryCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SurgeryCase::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'patient_id',
            'origin_class',
            'medical_diagnosis_id',
            'medical_diagnosis_other',
            'surgery_type_id',
            'surgery_type_other',
            'surgery_site',
            'anesthesia_type_id',
            'address',
            'phone',
        ];

        $merged = [
            'rm_number' => is_string($this->input('rm_number')) ? strtoupper(trim($this->input('rm_number'))) : $this->input('rm_number'),
            'patient_name' => is_string($this->input('patient_name')) ? trim($this->input('patient_name')) : $this->input('patient_name'),
            'origin_ward' => is_string($this->input('origin_ward')) ? trim($this->input('origin_ward')) : $this->input('origin_ward'),
        ];

        foreach ($nullableFields as $field) {
            $val = $this->input($field);
            if (is_string($val)) {
                $trimmed = trim($val);
                $merged[$field] = $trimmed !== '' ? $trimmed : null;
            } else {
                $merged[$field] = $val;
            }
        }

        if ($merged['medical_diagnosis_id'] === 'other') {
            $merged['medical_diagnosis_id'] = null;
        } elseif ($merged['medical_diagnosis_id'] !== null) {
            $merged['medical_diagnosis_other'] = null;
        }

        if ($merged['surgery_type_id'] === 'other') {
            $merged['surgery_type_id'] = null;
        } elseif ($merged['surgery_type_id'] !== null) {
            $merged['surgery_type_other'] = null;
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_mode' => ['required', 'in:existing,new'],
            'patient_id' => ['nullable', 'required_if:patient_mode,existing', 'exists:patients,id'],
            'rm_number' => ['nullable', 'required_if:patient_mode,new', 'string', 'max:50', Rule::unique(Patient::class, 'rm_number')],
            'patient_name' => ['nullable', 'required_if:patient_mode,new', 'string', 'max:255'],
            'birth_date' => ['nullable', 'required_if:patient_mode,new', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'required_if:patient_mode,new', Rule::enum(Gender::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'admitted_at' => ['required', 'date'],
            'origin_ward' => ['required', 'string', 'max:150'],
            'origin_class' => ['nullable', 'string', 'max:100'],
            'scheduled_at' => ['required', 'date'],
            'medical_diagnosis_id' => ['nullable', 'exists:medical_diagnoses,id'],
            'medical_diagnosis_other' => ['nullable', 'string', 'max:255'],
            'surgery_type_id' => ['nullable', 'exists:surgery_types,id'],
            'surgery_type_other' => ['nullable', 'string', 'max:255'],
            'surgery_site' => ['nullable', 'string', 'max:255'],
            'anesthesia_type_id' => ['nullable', 'exists:anesthesia_types,id'],
        ];
    }
}
