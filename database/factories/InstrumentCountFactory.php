<?php

namespace Database\Factories;

use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Models\InstrumentCount;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstrumentCount>
 */
class InstrumentCountFactory extends Factory
{
    protected $model = InstrumentCount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'item' => InstrumentCountItem::KasaSteril,
            'initial_count' => 10,
            'additional_count' => 0,
            'final_count' => 10,
            'status' => InstrumentCountStatus::Sesuai,
            'created_by' => User::factory()->perawat(),
        ];
    }
}
