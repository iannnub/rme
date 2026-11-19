<?php

namespace App\Http\Requests\Preop;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddCaseNursingDiagnosisRequest extends FormRequest
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
            'nursing_diagnosis_id' => ['nullable', 'exists:nursing_diagnoses,id'],
            'custom_text' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
