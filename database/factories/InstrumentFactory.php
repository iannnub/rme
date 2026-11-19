<?php

namespace Database\Factories;

use App\Models\Instrument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instrument>
 */
class InstrumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Alat '.fake()->unique()->words(2, true),
            'category' => fake()->randomElement(['Instrumen Bedah', 'Elektromedik', 'Anestesi', 'Monitoring']),
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
