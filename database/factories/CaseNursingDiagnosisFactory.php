<?php

namespace Database\Factories;

use App\Enums\NursingPhase;
use App\Models\CaseNursingDiagnosis;
use App\Models\NursingDiagnosis;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseNursingDiagnosis>
 */
class CaseNursingDiagnosisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'nursing_diagnosis_id' => NursingDiagnosis::factory(),
            'phase' => NursingPhase::Pre,
            'custom_text' => null,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
