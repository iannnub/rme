<?php

namespace Database\Factories;

use App\Enums\SscStatus;
use App\Models\SscTimeOut;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SscTimeOut>
 */
class SscTimeOutFactory extends Factory
{
    protected $model = SscTimeOut::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'status' => SscStatus::Selesai,
            'intro_circulating' => true,
            'intro_scrub' => true,
            'intro_anesthesia_nurse' => true,
            'intro_surgeon' => true,
            'intro_anesthesiologist' => true,
            'identity_procedure_site_confirmed' => true,
            'antibiotic_prophylaxis' => 'ya',
            'antibiotic_name' => 'Cefazolin 1g IV',
            'antibiotic_given_at' => now()->subMinutes(20),
            'antibiotic_confirmed_by' => 'Ns. Sari Rahmawati',
            'surgeon_critical_steps' => 'tidak',
            'surgeon_critical_steps_text' => null,
            'surgeon_estimated_duration_min' => 60,
            'surgeon_estimated_blood_loss_ml' => 150,
            'anesthesia_special_condition' => 'tidak',
            'anesthesia_risk' => 'tidak',
            'anesthesia_note' => 'Stabil',
            'nurse_sterility_confirmed' => true,
            'nurse_indicator_checked' => true,
            'equipment_problem' => 'tidak',
            'other_concern' => 'tidak',
            'nurse_note' => null,
            'imaging_needed' => 'tidak',
            'imaging_displayed' => null,
            'imaging_matches' => null,
            'signed_at' => now(),
            'filled_by' => User::factory()->perawat(),
            'created_by' => User::factory()->perawat(),
        ];
    }
}
