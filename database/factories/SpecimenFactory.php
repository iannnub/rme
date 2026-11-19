<?php

namespace Database\Factories;

use App\Models\Specimen;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specimen>
 */
class SpecimenFactory extends Factory
{
    protected $model = Specimen::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'has_specimen' => false,
            'specimen_type' => null,
            'origin_organ' => null,
            'purpose' => null,
            'preservative' => null,
            'created_by' => User::factory()->perawat(),
        ];
    }
}
