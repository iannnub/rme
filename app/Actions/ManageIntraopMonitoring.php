<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\FluidKind;
use App\Enums\NursingPhase;
use App\Enums\OxygenDevice;
use App\Enums\VitalSignPhase;
use App\Models\FluidRecord;
use App\Models\Medication;
use App\Models\NursingAction;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use App\Support\AuditContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageIntraopMonitoring
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function addVitalSign(SurgeryCase $case, array $data, User $actor): VitalSign
    {
        $this->ensureNotFinished($case);

        $oxygenDevice = is_string($data['oxygen_device'] ?? null) ? $data['oxygen_device'] : OxygenDevice::TanpaOksigen->value;
        $flowLpm = is_numeric($data['oxygen_flow_lpm'] ?? null) ? (int) $data['oxygen_flow_lpm'] : null;

        if ($oxygenDevice !== OxygenDevice::TanpaOksigen->value && ($flowLpm === null || $flowLpm < 1 || $flowLpm > 15)) {
            throw ValidationException::withMessages([
                'oxygen_flow_lpm' => ['Aliran oksigen (1–15 LPM) wajib diisi jika alat bantu oksigen bukan Tanpa Oksigen.'],
            ]);
        }

        return DB::transaction(function () use ($case, $data, $oxygenDevice, $flowLpm, $actor): VitalSign {
            return AuditContext::run(function () use ($case, $data, $oxygenDevice, $flowLpm, $actor): VitalSign {
                $recordedAt = is_string($data['recorded_at'] ?? null) && trim($data['recorded_at']) !== ''
                    ? Carbon::parse($data['recorded_at'], 'Asia/Jakarta')
                    : now('Asia/Jakarta');

                return VitalSign::query()->create([
                    'case_id' => $case->id,
                    'phase' => VitalSignPhase::Intra,
                    'recorded_at' => $recordedAt,
                    'bp_systolic' => is_numeric($data['bp_systolic'] ?? null) ? (int) $data['bp_systolic'] : null,
                    'bp_diastolic' => is_numeric($data['bp_diastolic'] ?? null) ? (int) $data['bp_diastolic'] : null,
                    'pulse' => is_numeric($data['pulse'] ?? null) ? (int) $data['pulse'] : null,
                    'temperature' => is_numeric($data['temperature'] ?? null) ? (float) $data['temperature'] : null,
                    'respiratory_rate' => is_numeric($data['respiratory_rate'] ?? null) ? (int) $data['respiratory_rate'] : null,
                    'spo2' => is_numeric($data['spo2'] ?? null) ? (int) $data['spo2'] : null,
                    'consciousness' => is_string($data['consciousness'] ?? null) && $data['consciousness'] !== ''
                        ? $data['consciousness']
                        : Consciousness::ComposMentis->value,
                    'breathing_pattern' => is_string($data['breathing_pattern'] ?? null) && $data['breathing_pattern'] !== '' ? $data['breathing_pattern'] : null,
                    'oxygen_device' => $oxygenDevice,
                    'oxygen_flow_lpm' => $oxygenDevice !== OxygenDevice::TanpaOksigen->value ? $flowLpm : null,
                    'notes' => is_string($data['notes'] ?? null) && trim($data['notes']) !== '' ? trim($data['notes']) : null,
                    'created_by' => $actor->id,
                ]);
            }, 'Menambahkan pemantauan TTV intra-operasi', $case->id, $actor);
        });
    }

    public function deleteVitalSign(SurgeryCase $case, VitalSign $vitalSign, User $actor): void
    {
        $this->ensureNotFinished($case);
        abort_if($vitalSign->case_id !== $case->id || $vitalSign->phase !== VitalSignPhase::Intra, 404);

        AuditContext::run(function () use ($vitalSign): void {
            $vitalSign->delete();
        }, 'Menghapus entri TTV intra-operasi', $case->id, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addMedication(SurgeryCase $case, array $data, User $actor): Medication
    {
        $this->ensureNotFinished($case);

        return DB::transaction(function () use ($case, $data, $actor): Medication {
            return AuditContext::run(function () use ($case, $data, $actor): Medication {
                $givenAt = is_string($data['given_at'] ?? null) && trim($data['given_at']) !== ''
                    ? Carbon::parse($data['given_at'], 'Asia/Jakarta')
                    : now('Asia/Jakarta');

                return Medication::query()->create([
                    'case_id' => $case->id,
                    'phase' => VitalSignPhase::Intra,
                    'name' => trim((string) $data['name']),
                    'dose' => trim((string) $data['dose']),
                    'route' => is_string($data['route'] ?? null) && trim($data['route']) !== '' ? trim($data['route']) : null,
                    'given_at' => $givenAt,
                    'note' => is_string($data['note'] ?? null) && trim($data['note']) !== '' ? trim($data['note']) : null,
                    'created_by' => $actor->id,
                ]);
            }, 'Menambahkan pemberian obat intra-operasi', $case->id, $actor);
        });
    }

    public function deleteMedication(SurgeryCase $case, Medication $medication, User $actor): void
    {
        $this->ensureNotFinished($case);
        abort_if($medication->case_id !== $case->id, 404);

        AuditContext::run(function () use ($medication): void {
            $medication->delete();
        }, 'Menghapus entri obat intra-operasi', $case->id, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addFluid(SurgeryCase $case, array $data, User $actor): FluidRecord
    {
        $this->ensureNotFinished($case);

        $kind = FluidKind::from((string) $data['kind']);

        return DB::transaction(function () use ($case, $data, $kind, $actor): FluidRecord {
            return AuditContext::run(function () use ($case, $data, $kind, $actor): FluidRecord {
                $recordedAt = is_string($data['recorded_at'] ?? null) && trim($data['recorded_at']) !== ''
                    ? Carbon::parse($data['recorded_at'], 'Asia/Jakarta')
                    : now('Asia/Jakarta');

                return FluidRecord::query()->create([
                    'case_id' => $case->id,
                    'phase' => VitalSignPhase::Intra,
                    'recorded_at' => $recordedAt,
                    'direction' => $kind->direction(),
                    'kind' => $kind,
                    'volume_ml' => (int) $data['volume_ml'],
                    'note' => is_string($data['note'] ?? null) && trim($data['note']) !== '' ? trim($data['note']) : null,
                    'created_by' => $actor->id,
                ]);
            }, 'Menambahkan catatan cairan intra-operasi', $case->id, $actor);
        });
    }

    public function deleteFluid(SurgeryCase $case, FluidRecord $fluidRecord, User $actor): void
    {
        $this->ensureNotFinished($case);
        abort_if($fluidRecord->case_id !== $case->id, 404);

        AuditContext::run(function () use ($fluidRecord): void {
            $fluidRecord->delete();
        }, 'Menghapus catatan cairan intra-operasi', $case->id, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addNursingAction(SurgeryCase $case, array $data, User $actor): NursingAction
    {
        $this->ensureNotFinished($case);

        return DB::transaction(function () use ($case, $data, $actor): NursingAction {
            return AuditContext::run(function () use ($case, $data, $actor): NursingAction {
                $performedAt = is_string($data['performed_at'] ?? null) && trim($data['performed_at']) !== ''
                    ? Carbon::parse($data['performed_at'], 'Asia/Jakarta')
                    : now('Asia/Jakarta');

                return NursingAction::query()->create([
                    'case_id' => $case->id,
                    'phase' => NursingPhase::Intra,
                    'performed_at' => $performedAt,
                    'performed_by_role' => (string) $data['performed_by_role'],
                    'action' => trim((string) $data['action']),
                    'evaluation' => is_string($data['evaluation'] ?? null) && trim($data['evaluation']) !== '' ? trim($data['evaluation']) : null,
                    'created_by' => $actor->id,
                ]);
            }, 'Menambahkan tindakan keperawatan intra-operasi', $case->id, $actor);
        });
    }

    public function applyTemplates(SurgeryCase $case, User $actor): int
    {
        $this->ensureNotFinished($case);

        return DB::transaction(function () use ($case, $actor): int {
            return AuditContext::run(function () use ($case, $actor): int {
                $existingActions = $case->nursingActions()
                    ->where('phase', NursingPhase::Intra)
                    ->pluck('action')
                    ->all();

                $added = 0;
                $baseTime = now('Asia/Jakarta');

                foreach (NursingAction::INTRAOP_TEMPLATES as $idx => $template) {
                    if (in_array($template['action'], $existingActions, true)) {
                        continue;
                    }

                    NursingAction::query()->create([
                        'case_id' => $case->id,
                        'phase' => NursingPhase::Intra,
                        'performed_at' => $baseTime->copy()->addSeconds($idx),
                        'performed_by_role' => $template['role'],
                        'action' => $template['action'],
                        'evaluation' => $template['evaluation'],
                        'created_by' => $actor->id,
                    ]);
                    $added++;
                }

                return $added;
            }, 'Menambahkan template tindakan keperawatan intra-operasi', $case->id, $actor);
        });
    }

    public function deleteNursingAction(SurgeryCase $case, NursingAction $nursingAction, User $actor): void
    {
        $this->ensureNotFinished($case);
        abort_if($nursingAction->case_id !== $case->id, 404);

        AuditContext::run(function () use ($nursingAction): void {
            $nursingAction->delete();
        }, 'Menghapus tindakan keperawatan intra-operasi', $case->id, $actor);
    }

    private function ensureNotFinished(SurgeryCase $case): void
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah catatan intra-operasinya.'],
            ]);
        }
    }
}
