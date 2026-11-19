<?php

namespace Database\Factories;

use App\Enums\CaseStatus;
use App\Enums\NurseType;
use App\Models\AnesthesiaType;
use App\Models\MedicalDiagnosis;
use App\Models\Patient;
use App\Models\Room;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurgeryCase>
 */
class SurgeryCaseFactory extends Factory
{
    public function definition(): array
    {
        $admitted = now()->subDays(1);
        $scheduled = now()->addHours(4);

        return [
            'patient_id' => Patient::factory(),
            'case_number' => 'OK-'.now()->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'admitted_at' => $admitted,
            'origin_ward' => fake()->randomElement(['Mawar 1', 'Melati 2', 'Anggrek 3', 'IGD']),
            'origin_class' => fake()->randomElement(['Kelas 1', 'Kelas 2', 'Kelas 3', 'VIP']),
            'scheduled_at' => $scheduled,
            'medical_diagnosis_id' => MedicalDiagnosis::factory(),
            'medical_diagnosis_other' => null,
            'surgery_type_id' => SurgeryType::factory(),
            'surgery_type_other' => null,
            'surgery_site' => 'Abdomen kuadran kanan bawah',
            'operation_plan_actual' => null,
            'anesthesia_type_id' => AnesthesiaType::factory(),
            'room_id' => null,
            'surgeon_id' => null,
            'anesthesiologist_id' => null,
            'circulating_nurse_id' => null,
            'scrub_nurse_id' => null,
            'anesthesia_nurse_id' => null,
            'team_assigned_at' => null,
            'ready_at' => null,
            'entered_or_at' => null,
            'anesthesia_start_at' => null,
            'incision_at' => null,
            'surgery_end_at' => null,
            'left_or_at' => null,
            'completed_at' => null,
            'stage_readiness_flags' => null,
            'created_by' => User::factory(),
        ];
    }

    public function withTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => Room::factory(),
            'surgeon_id' => User::factory()->dokterOperator(),
            'anesthesiologist_id' => User::factory()->dokterAnestesi(),
            'circulating_nurse_id' => User::factory()->perawat(NurseType::Sirkuler),
            'scrub_nurse_id' => User::factory()->perawat(NurseType::Instrumen),
            'anesthesia_nurse_id' => User::factory()->perawat(NurseType::Anestesi),
            'team_assigned_at' => now(),
        ]);
    }

    public function atStatus(CaseStatus $status): static
    {
        return $this->afterMaking(function (SurgeryCase $case) use ($status): void {
            $case->forceFill(['status' => $status]);
        });
    }
}
