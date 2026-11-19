<?php

namespace Database\Factories;

use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstrumentCheck>
 */
class InstrumentCheckFactory extends Factory
{
    protected $model = InstrumentCheck::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'instrument_id' => Instrument::factory(),
            'is_available' => true,
            'is_sterile' => true,
            'is_functional' => true,
            'is_rusty' => false,
            'note' => null,
            'checked_by' => User::factory()->perawat(),
            'checked_at' => now(),
        ];
    }

    public function problematic(?string $note = 'Alat diganti dengan set steril cadangan.'): static
    {
        return $this->state(fn (): array => [
            'is_sterile' => false,
            'note' => $note,
        ]);
    }
}
