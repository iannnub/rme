<?php

namespace App\Http\Requests\Preop;

use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\ExamType;
use App\Enums\ReadinessStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePreopAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->isPerawat() ?? false)
            && $case->status !== CaseStatus::Selesai;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_updated_at' => ['nullable', 'string'],
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'current_illness_history' => ['nullable', 'string', 'max:2000'],
            'past_illness_history' => ['nullable', 'string', 'max:2000'],
            'surgery_history' => ['nullable', 'string', 'max:1000'],
            'allergy_status' => ['required', Rule::enum(AllergyStatus::class)],
            'allergy_detail' => ['nullable', 'string', 'max:1000'],
            'medication_history' => ['nullable', 'string', 'max:1000'],
            'anesthesia_history' => ['nullable', 'string', 'max:1000'],
            'fasting_status' => ['required', 'boolean'],
            'fasting_since' => ['nullable', 'date'],
            'skin_condition' => ['nullable', 'string', 'max:500'],
            'anxiety_level' => ['required', Rule::enum(AnxietyLevel::class)],
            'surgery_knowledge' => ['nullable', 'string', 'max:500'],
            'family_support' => ['nullable', 'string', 'max:500'],
            'mental_readiness' => ['nullable', 'string', 'max:500'],
            'preparation' => ['nullable', 'array'],
            'implementation_actions' => ['nullable', 'string', 'max:1000'],
            'implementation_education' => ['nullable', 'string', 'max:1000'],
            'implementation_monitoring' => ['nullable', 'string', 'max:1000'],
            'implementation_response' => ['nullable', 'string', 'max:1000'],
            'evaluation_condition' => ['nullable', 'string', 'max:1000'],
            'evaluation_completeness' => ['nullable', 'string', 'max:1000'],
            'readiness_status' => ['required', Rule::enum(ReadinessStatus::class)],
            'evaluation_notes' => ['nullable', 'string', 'max:1000'],
            'clinical_details' => ['nullable', 'array'],
            'clinical_details.*' => ['nullable', 'array'],
            'clinical_details.*.*' => ['nullable', 'string', 'max:1000'],
            'vital_signs' => ['nullable', 'array'],
            'vital_signs.bp_systolic' => ['nullable', 'integer', 'min:30', 'max:300'],
            'vital_signs.bp_diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'vital_signs.pulse' => ['nullable', 'integer', 'min:20', 'max:250'],
            'vital_signs.temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'vital_signs.respiratory_rate' => ['nullable', 'integer', 'min:5', 'max:80'],
            'vital_signs.spo2' => ['nullable', 'integer', 'min:40', 'max:100'],
            'vital_signs.weight_kg' => ['nullable', 'numeric', 'min:1', 'max:350'],
            'vital_signs.height_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'vital_signs.consciousness' => ['nullable', Rule::enum(Consciousness::class)],
            'vital_signs.notes' => ['nullable', 'string', 'max:500'],
            'supporting_exams' => ['nullable', 'array'],
            'supporting_exams.*.id' => ['nullable', 'integer'],
            'supporting_exams.*.exam_type' => ['nullable', Rule::enum(ExamType::class)],
            'supporting_exams.*.result' => ['nullable', 'string', 'max:1000'],
            'supporting_exams.*.exam_date' => ['nullable', 'date'],
            'medical_diagnosis_id' => ['nullable'],
            'medical_diagnosis_other' => ['nullable', 'string', 'max:255'],
            'surgery_type_id' => ['nullable'],
            'surgery_type_other' => ['nullable', 'string', 'max:255'],
            'surgery_site' => ['nullable', 'string', 'max:255'],
            'anesthesia_type_id' => ['nullable', 'exists:anesthesia_types,id'],
            'surgeon_id' => ['nullable', 'exists:users,id'],
            'anesthesiologist_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
