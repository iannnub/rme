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
            'igd_visit_number',
            'igd_admitted_at',
            'origin_class',
            'urgency_status',
            'medical_diagnosis_id',
            'medical_diagnosis_other',
            'surgery_type_id',
            'surgery_type_other',
            'surgery_site',
            'anesthesia_type_id',
            'dpjp_name',
            'identity_number',
            'address',
            'phone',
            'guardian_name',
            'guardian_relation',
            'guardian_phone',
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
            'identity_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_relation' => ['nullable', 'string', 'max:80'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'igd_visit_number' => ['nullable', 'string', 'max:80'],
            'admitted_at' => ['required', 'date'],
            'igd_admitted_at' => ['nullable', 'date'],
            'origin_ward' => ['required', 'string', 'max:150'],
            'origin_class' => ['nullable', 'string', 'max:100'],
            'urgency_status' => ['nullable', 'in:emergency,elektif'],
            'scheduled_at' => ['required', 'date'],
            'medical_diagnosis_id' => ['nullable', 'exists:medical_diagnoses,id'],
            'medical_diagnosis_other' => ['nullable', 'string', 'max:255'],
            'surgery_type_id' => ['nullable', 'exists:surgery_types,id'],
            'surgery_type_other' => ['nullable', 'string', 'max:255'],
            'surgery_site' => ['nullable', 'string', 'max:255'],
            'anesthesia_type_id' => ['nullable', 'exists:anesthesia_types,id'],
            'dpjp_name' => ['nullable', 'string', 'max:150'],
        ];
    }
}
