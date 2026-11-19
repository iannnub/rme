<?php

namespace Database\Factories;

use App\Enums\NursingPhase;
use App\Models\NursingAction;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NursingAction>
 */
class NursingActionFactory extends Factory
{
    protected $model = NursingAction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'phase' => NursingPhase::Intra,
            'performed_at' => now(),
            'performed_by_role' => 'sirkuler',
            'action' => 'Memantau prinsip aseptik-steril selama operasi berlangsung.',
            'evaluation' => 'Prinsip steril terjaga ketat.',
            'created_by' => User::factory()->perawat(),
        ];
    }
}
