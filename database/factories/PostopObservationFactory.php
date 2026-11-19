<?php

namespace Database\Factories;

use App\Enums\AirwayPatency;
use App\Enums\AnalgesicResponse;
use App\Enums\Consciousness;
use App\Enums\DressingCondition;
use App\Enums\GeneralCondition;
use App\Enums\PainCharacter;
use App\Enums\PatientResponse;
use App\Enums\SutureCondition;
use App\Models\PostopObservation;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostopObservation>
 */
class PostopObservationFactory extends Factory
{
    protected $model = PostopObservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'recorded_at' => now(),
            'gcs_eye' => 4,
            'gcs_verbal' => 5,
            'gcs_motor' => 6,
            'general_condition' => GeneralCondition::Baik,
            'response' => PatientResponse::Stabil,
            'airway_patency' => AirwayPatency::Paten,
            'wound_location' => 'Abdomen kuadran kanan bawah',
            'dressing' => DressingCondition::Bersih,
            'bleeding_seepage' => false,
            'swelling_hematoma' => false,
            'suture_condition' => SutureCondition::RapiUtuh,
            'pain_nrs' => 3,
            'pain_location' => 'Area luka operasi',
            'pain_character' => PainCharacter::Tumpul,
            'pain_duration' => 'Intermiten',
            'analgesic_response' => AnalgesicResponse::Berkurang,
            'anesthesia_recovery' => Consciousness::ComposMentis,
            'nausea_vomiting' => false,
            'shivering_hypothermia' => false,
            'complications' => [],
            'notes' => 'Pasien sadar penuh, hemodinamik stabil.',
            'created_by' => User::factory()->perawat(),
        ];
    }
}
