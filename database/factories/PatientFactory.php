<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rm_number' => 'RM-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'birth_date' => fake()->dateTimeBetween('-75 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement([Gender::Male, Gender::Female]),
            'address' => fake()->address(),
            'phone' => fake()->numerify('08##########'),
            'created_by' => User::factory(),
        ];
    }
}
