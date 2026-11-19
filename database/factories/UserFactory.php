<?php

namespace Database\Factories;

use App\Enums\NurseType;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'role' => Role::Perawat,
            'nurse_type' => NurseType::Sirkuler,
            'nip' => fake()->numerify('198#########20######'),
            'sip' => null,
            'is_active' => true,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Admin,
            'nurse_type' => null,
            'nip' => null,
            'sip' => null,
        ]);
    }

    public function kepalaRuangan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::KepalaRuangan,
            'nurse_type' => null,
            'nip' => fake()->numerify('197#########10######'),
            'sip' => null,
        ]);
    }

    public function dokterOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::DokterOperator,
            'nurse_type' => null,
            'nip' => null,
            'sip' => fake()->numerify('SIP.503/####/SP.B/2026'),
        ]);
    }

    public function dokterAnestesi(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::DokterAnestesi,
            'nurse_type' => null,
            'nip' => null,
            'sip' => fake()->numerify('SIP.503/####/SP.AN/2026'),
        ]);
    }

    public function perawat(NurseType $type = NurseType::Sirkuler): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Perawat,
            'nurse_type' => $type,
            'nip' => fake()->numerify('198#########20######'),
            'sip' => null,
        ]);
    }
}
