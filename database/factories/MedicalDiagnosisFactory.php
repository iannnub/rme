<?php

namespace Database\Factories;

use App\Models\MedicalDiagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalDiagnosis>
 */
class MedicalDiagnosisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Diagnosis '.fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
