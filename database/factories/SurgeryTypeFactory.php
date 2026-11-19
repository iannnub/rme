<?php

namespace Database\Factories;

use App\Models\SurgeryType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurgeryType>
 */
class SurgeryTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Tindakan '.fake()->unique()->words(2, true),
            'specialty' => fake()->randomElement(['Bedah Umum', 'Bedah Digestif', 'Ortopedi', 'Obstetri & Ginekologi']),
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
