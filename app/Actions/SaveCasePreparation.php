<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveCasePreparation
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     case: SurgeryCase,
     *     room_conflict: array{case_id: int, case_number: string, patient_name: string, room_code: string}|null
     * }
     */
    public function execute(SurgeryCase $case, array $data, User $actor): array
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah penetapan ruang dan timnya.'],
            ]);
        }

        $updatedCase = DB::transaction(function () use ($case, $data, $actor): SurgeryCase {
            return AuditContext::run(function () use ($case, $data): SurgeryCase {
                $case->fill([
                    'room_id' => is_numeric($data['room_id'] ?? null) ? (int) $data['room_id'] : null,
                    'surgeon_id' => is_numeric($data['surgeon_id'] ?? null) ? (int) $data['surgeon_id'] : null,
                    'anesthesiologist_id' => is_numeric($data['anesthesiologist_id'] ?? null) ? (int) $data['anesthesiologist_id'] : null,
                    'circulating_nurse_id' => is_numeric($data['circulating_nurse_id'] ?? null) ? (int) $data['circulating_nurse_id'] : null,
                    'scrub_nurse_id' => is_numeric($data['scrub_nurse_id'] ?? null) ? (int) $data['scrub_nurse_id'] : null,
                    'anesthesia_nurse_id' => is_numeric($data['anesthesia_nurse_id'] ?? null) ? (int) $data['anesthesia_nurse_id'] : null,
                ]);

                if ($case->hasRoomAndTeamAssigned() && $case->team_assigned_at === null) {
                    $case->team_assigned_at = now();
                }

                $case->save();

                return $case->fresh(['room']) ?? $case;
            }, 'Memperbarui penetapan ruang operasi dan tim', $case->id, $actor);
        });

        $conflict = $this->findActiveRoomConflict($updatedCase);

        return [
            'case' => $updatedCase,
            'room_conflict' => $conflict,
        ];
    }

    /**
     * @return array{case_id: int, case_number: string, patient_name: string, room_code: string}|null
     */
    public function findActiveRoomConflict(SurgeryCase $case): ?array
    {
        if ($case->room_id === null) {
            return null;
        }

        $conflictingCase = SurgeryCase::query()
            ->with(['patient', 'room'])
            ->where('id', '!=', $case->id)
            ->where('room_id', $case->room_id)
            ->where('status', CaseStatus::IntraOp)
            ->first();

        if ($conflictingCase === null) {
            return null;
        }

        return [
            'case_id' => $conflictingCase->id,
            'case_number' => $conflictingCase->case_number,
            'patient_name' => $conflictingCase->patient !== null ? $conflictingCase->patient->name : '-',
            'room_code' => $conflictingCase->room !== null ? $conflictingCase->room->code : '-',
        ];
    }
}
