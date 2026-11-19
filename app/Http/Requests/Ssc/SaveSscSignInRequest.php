<?php

namespace App\Http\Requests\Ssc;

use App\Enums\AllergyStatus;
use App\Enums\BloodReadiness;
use App\Enums\IvAccessStatus;
use App\Enums\RiskPresence;
use App\Enums\SiteMarkingStatus;
use App\Enums\SscConsentStatus;
use App\Enums\SscMatchStatus;
use App\Enums\SscReadiness;
use App\Enums\SscStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSscSignInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updateSignIn', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_updated_at' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(SscStatus::class)],

            'identity_confirmed' => ['nullable', Rule::enum(SscMatchStatus::class)],
            'procedure_confirmed' => ['nullable', Rule::enum(SscMatchStatus::class)],
            'site_confirmed' => ['nullable', Rule::enum(SscMatchStatus::class)],
            'consent_surgery' => ['nullable', Rule::enum(SscConsentStatus::class)],
            'consent_anesthesia' => ['nullable', Rule::enum(SscConsentStatus::class)],
            'site_marking' => ['nullable', Rule::enum(SiteMarkingStatus::class)],

            'readiness' => ['nullable', 'array'],
            'readiness.anesthesia_machine' => ['nullable', Rule::enum(SscReadiness::class)],
            'readiness.oxygen_source' => ['nullable', Rule::enum(SscReadiness::class)],
            'readiness.anesthesia_drugs' => ['nullable', Rule::enum(SscReadiness::class)],
            'readiness.airway_aids' => ['nullable', Rule::enum(SscReadiness::class)],
            'readiness.suction' => ['nullable', Rule::enum(SscReadiness::class)],
            'readiness.monitoring_device' => ['nullable', Rule::enum(SscReadiness::class)],
            'readiness.emergency_drugs' => ['nullable', Rule::enum(SscReadiness::class)],

            'pulse_ox_attached' => ['nullable', 'boolean'],
            'pulse_ox_functional' => ['nullable', 'boolean'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pulse' => ['nullable', 'integer', 'min:0', 'max:300'],
            'pulse_ox_note' => ['nullable', 'string', 'max:1000'],

            'allergy_status' => ['nullable', Rule::enum(AllergyStatus::class)],
            'allergy_type' => ['nullable', 'string', 'max:100'],
            'allergy_agent' => ['nullable', 'string', 'max:150'],
            'allergy_reaction' => ['nullable', 'string', 'max:1000'],
            'allergy_followup' => ['nullable', 'string', 'max:1000'],

            'difficult_airway_risk' => ['nullable', Rule::enum(RiskPresence::class)],
            'aspiration_risk' => ['nullable', Rule::enum(RiskPresence::class)],
            'anesthesiologist_assessment' => ['nullable', 'string', 'max:2000'],
            'anticipation_plan' => ['nullable', 'string', 'max:2000'],
            'airway_equipment_ready' => ['nullable', Rule::enum(SscReadiness::class)],

            'bleeding_risk' => ['nullable', Rule::enum(RiskPresence::class)],
            'estimated_blood_loss_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'iv_access' => ['nullable', Rule::enum(IvAccessStatus::class)],
            'fluids_ready' => ['nullable', Rule::enum(SscReadiness::class)],
            'blood_ready' => ['nullable', Rule::enum(BloodReadiness::class)],
            'bleeding_note' => ['nullable', 'string', 'max:1000'],

            'notes_followup' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
