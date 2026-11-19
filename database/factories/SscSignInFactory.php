<?php

namespace Database\Factories;

use App\Enums\AllergyStatus;
use App\Enums\BloodReadiness;
use App\Enums\IvAccessStatus;
use App\Enums\RiskPresence;
use App\Enums\SiteMarkingStatus;
use App\Enums\SscConsentStatus;
use App\Enums\SscMatchStatus;
use App\Enums\SscReadiness;
use App\Enums\SscStatus;
use App\Models\SscSignIn;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SscSignIn>
 */
class SscSignInFactory extends Factory
{
    protected $model = SscSignIn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'status' => SscStatus::Selesai,
            'identity_confirmed' => SscMatchStatus::Sesuai,
            'procedure_confirmed' => SscMatchStatus::Sesuai,
            'site_confirmed' => SscMatchStatus::Sesuai,
            'consent_surgery' => SscConsentStatus::Ada,
            'consent_anesthesia' => SscConsentStatus::Ada,
            'site_marking' => SiteMarkingStatus::Ada,
            'readiness' => [
                'anesthesia_machine' => SscReadiness::Siap->value,
                'oxygen_source' => SscReadiness::Siap->value,
                'anesthesia_drugs' => SscReadiness::Siap->value,
                'airway_aids' => SscReadiness::Siap->value,
                'suction' => SscReadiness::Siap->value,
                'monitoring_device' => SscReadiness::Siap->value,
                'emergency_drugs' => SscReadiness::Siap->value,
            ],
            'pulse_ox_attached' => true,
            'pulse_ox_functional' => true,
            'spo2' => 99,
            'pulse' => 80,
            'pulse_ox_note' => null,
            'allergy_status' => AllergyStatus::TidakAda,
            'allergy_type' => null,
            'allergy_agent' => null,
            'allergy_reaction' => null,
            'allergy_followup' => null,
            'difficult_airway_risk' => RiskPresence::TidakAda,
            'aspiration_risk' => RiskPresence::TidakAda,
            'anesthesiologist_assessment' => 'Jalan napas Mallampati I, risiko rendah.',
            'anticipation_plan' => 'Pemantauan rutin standar.',
            'airway_equipment_ready' => SscReadiness::Siap,
            'bleeding_risk' => RiskPresence::TidakAda,
            'estimated_blood_loss_ml' => 100,
            'iv_access' => IvAccessStatus::Memadai,
            'fluids_ready' => SscReadiness::Siap,
            'blood_ready' => BloodReadiness::TidakDiindikasikan,
            'bleeding_note' => null,
            'notes_followup' => null,
            'signed_at' => now(),
            'filled_by' => User::factory()->perawat(),
            'created_by' => User::factory()->perawat(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => SscStatus::DalamProses,
            'signed_at' => null,
        ]);
    }
}
