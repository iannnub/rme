<?php

namespace Database\Factories;

use App\Enums\CountVerification;
use App\Enums\SkinIntegrity;
use App\Enums\SurgicalPosition;
use App\Models\IntraopRecord;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntraopRecord>
 */
class IntraopRecordFactory extends Factory
{
    protected $model = IntraopRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'position' => SurgicalPosition::Supine,
            'pressure_protection' => ['ganjal_kepala_leher', 'safety_belt'],
            'skin_antiseptic' => ['povidone_iodine'],
            'grounding_plate_site' => 'Paha kanan bagian lateral',
            'skin_condition_pre' => SkinIntegrity::Utuh,
            'skin_condition_post' => SkinIntegrity::Utuh,
            'draping_done' => true,
            'warming' => ['blanket_warmer'],
            'room_temperature_c' => 21.5,
            'count_verification' => CountVerification::LengkapSesuai,
            'count_discrepancy_actions' => [],
            'has_complication' => false,
            'complication_description' => null,
            'complication_handling' => null,
            'created_by' => User::factory()->perawat(),
        ];
    }
}
