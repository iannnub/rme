<?php

namespace Database\Factories;

use App\Enums\NursingPhase;
use App\Models\NursingDiagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NursingDiagnosis>
 */
class NursingDiagnosisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'D.0080',
            'name' => 'Ansietas',
            'phase' => NursingPhase::Pre,
            'is_active' => true,
        ];
    }
}
