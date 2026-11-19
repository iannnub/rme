<?php

namespace Database\Factories;

use App\Enums\FluidDirection;
use App\Enums\FluidKind;
use App\Enums\VitalSignPhase;
use App\Models\FluidRecord;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FluidRecord>
 */
class FluidRecordFactory extends Factory
{
    protected $model = FluidRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'phase' => VitalSignPhase::Intra,
            'recorded_at' => now(),
            'direction' => FluidDirection::Intake,
            'kind' => FluidKind::Kristaloid,
            'volume_ml' => 500,
            'color' => null,
            'odor' => null,
            'note' => 'RL 500 ml',
            'created_by' => User::factory()->perawat(),
        ];
    }
}
