<?php

namespace Database\Factories;

use App\Models\AnesthesiaType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnesthesiaType>
 */
class AnesthesiaTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('AN_????')),
            'name' => 'Anestesi '.fake()->unique()->word(),
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
