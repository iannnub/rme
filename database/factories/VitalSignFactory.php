<?php

namespace Database\Factories;

use App\Enums\Consciousness;
use App\Enums\VitalSignPhase;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalSign>
 */
class VitalSignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'phase' => VitalSignPhase::Pre,
            'recorded_at' => now(),
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'pulse' => 80,
            'temperature' => 36.5,
            'respiratory_rate' => 18,
            'spo2' => 98,
            'weight_kg' => 60.0,
            'height_cm' => 165.0,
            'consciousness' => Consciousness::ComposMentis,
            'breathing_pattern' => null,
            'oxygen_device' => null,
            'oxygen_flow_lpm' => null,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
