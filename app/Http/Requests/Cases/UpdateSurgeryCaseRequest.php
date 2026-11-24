<?php

namespace App\Http\Requests\Cases;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSurgeryCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase && ($this->user()?->can('update', $case) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
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
            'postop_diagnosis',
        ];

        $merged = [
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
            'postop_diagnosis' => ['nullable', 'string', 'max:255'],
        ];
    }
}
