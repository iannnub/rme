<?php

namespace Database\Factories;

use App\Enums\HandoverDestination;
use App\Models\Handover;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Handover>
 */
class HandoverFactory extends Factory
{
    protected $model = Handover::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'destination' => HandoverDestination::RuangRawatInap,
            'destination_detail' => 'Ruang Melati 2',
            'documents_complete' => true,
            'handed_by_id' => User::factory()->perawat(),
            'received_by_name' => 'Ns. Budi Santoso, S.Kep',
            'received_by_id' => null,
            'handed_over_at' => now(),
            'ongoing_issues' => 'Nyeri ringan NRS 3 pada luka operasi.',
            'follow_up_instructions' => 'Pantau TTV tiap 1 jam, lanjutkan cairan RL 20 tpm, analgetik sesuai order.',
            'summary_snapshot' => [],
            'created_by' => User::factory()->perawat(),
        ];
    }
}
