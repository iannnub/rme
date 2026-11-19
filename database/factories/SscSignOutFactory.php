<?php

namespace Database\Factories;

use App\Enums\SignOutStatus;
use App\Models\SscSignOut;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SscSignOut>
 */
class SscSignOutFactory extends Factory
{
    protected $model = SscSignOut::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'status' => SignOutStatus::Terverifikasi,
            'q1_procedure_confirmed' => true,
            'q2_counts_correct' => true,
            'q3_specimen_labeled' => 'tidak_berlaku',
            'q4_equipment_problem' => false,
            'q5_recovery_discussed' => true,
            'extra_6_procedure_matches' => 'sesuai',
            'extra_7_changes_recorded' => 'tidak_ada_perubahan',
            'extra_8_discrepancy_followup' => 'tidak_ada_selisih',
            'extra_9_specimen_matches' => 'tidak_ada_spesimen',
            'extra_10_equipment_issue_recorded' => 'tidak_ada_masalah',
            'extra_11_complication_communicated' => 'tidak_ada_komplikasi',
            'extra_12_postop_monitoring_discussed' => 'sudah',
            'extra_13_devices_instructions' => 'sudah',
            'recovery_notes' => 'Pantau TTV, luka operasi, dan nyeri di ruang pemulihan.',
            'issues_and_actions' => null,
            'signed_at' => now(),
            'filled_by' => User::factory()->perawat(),
            'created_by' => User::factory()->perawat(),
        ];
    }
}
