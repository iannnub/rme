<?php

namespace Database\Factories;

use App\Enums\VitalSignPhase;
use App\Models\Medication;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'phase' => VitalSignPhase::Intra,
            'name' => 'Propofol',
            'dose' => '100 mg',
            'route' => 'IV',
            'given_at' => now(),
            'note' => null,
            'created_by' => User::factory()->perawat(),
        ];
    }
}
