<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\FluidDirection;
use App\Enums\FluidKind;
use App\Enums\OxygenDevice;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\VitalSignPhase;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\PostopObservation;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use App\Support\AuditContext;
use App\Support\PreopClinicalDefaults;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorePostopObservation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): PostopObservation
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat ditambahkan observasi pasca-operasinya.'],
            ]);
        }

        $oxygenDevice = is_string($data['oxygen_device'] ?? null) ? $data['oxygen_device'] : OxygenDevice::TanpaOksigen->value;
        $flowLpm = is_numeric($data['oxygen_flow_lpm'] ?? null) ? (int) $data['oxygen_flow_lpm'] : null;

        if ($oxygenDevice !== OxygenDevice::TanpaOksigen->value && ($flowLpm === null || $flowLpm < 1 || $flowLpm > 15)) {
            throw ValidationException::withMessages([
                'oxygen_flow_lpm' => ['Aliran oksigen (1–15 LPM) wajib diisi jika alat bantu oksigen bukan Tanpa Oksigen.'],
            ]);
        }

        return DB::transaction(function () use ($case, $data, $oxygenDevice, $flowLpm, $actor): PostopObservation {
            return AuditContext::run(function () use ($case, $data, $oxygenDevice, $flowLpm, $actor): PostopObservation {
                $recordedAt = is_string($data['recorded_at'] ?? null) && trim($data['recorded_at']) !== ''
                    ? Carbon::parse($data['recorded_at'], 'Asia/Jakarta')
                    : now('Asia/Jakarta');

                $complications = array_values(array_filter(
                    is_array($data['complications'] ?? null) ? $data['complications'] : [],
                    fn (mixed $v): bool => is_string($v) && in_array($v, PostopObservation::COMPLICATION_OPTIONS, true)
                ));

                $observation = PostopObservation::query()->create([
                    'case_id' => $case->id,
                    'recorded_at' => $recordedAt,
                    'gcs_eye' => (int) $data['gcs_eye'],
                    'gcs_verbal' => (int) $data['gcs_verbal'],
                    'gcs_motor' => (int) $data['gcs_motor'],
                    'general_condition' => (string) $data['general_condition'],
                    'response' => (string) $data['response'],
                    'airway_patency' => (string) $data['airway_patency'],
                    'wound_location' => $this->nullableString($data['wound_location'] ?? null),
                    'dressing' => $this->nullableString($data['dressing'] ?? null),
                    'bleeding_seepage' => (bool) ($data['bleeding_seepage'] ?? false),
                    'swelling_hematoma' => (bool) ($data['swelling_hematoma'] ?? false),
                    'suture_condition' => $this->nullableString($data['suture_condition'] ?? null),
                    'pain_nrs' => (int) ($data['pain_nrs'] ?? 0),
                    'pain_location' => $this->nullableString($data['pain_location'] ?? null),
                    'pain_character' => $this->nullableString($data['pain_character'] ?? null),
                    'pain_duration' => $this->nullableString($data['pain_duration'] ?? null),
                    'analgesic_response' => $this->nullableString($data['analgesic_response'] ?? null),
                    'anesthesia_recovery' => $this->nullableString($data['anesthesia_recovery'] ?? null),
                    'nausea_vomiting' => (bool) ($data['nausea_vomiting'] ?? false),
                    'shivering_hypothermia' => (bool) ($data['shivering_hypothermia'] ?? false),
                    'complications' => $complications,
                    'notes' => $this->nullableString($data['notes'] ?? null),
                    'clinical_details' => is_array($data['clinical_details'] ?? null)
                        ? PreopClinicalDefaults::mergePostopClinical($data['clinical_details'])
                        : null,
                    'created_by' => $actor->id,
                ]);

                // Simpan TTV & pernapasan fase pasca-op
                VitalSign::query()->create([
                    'case_id' => $case->id,
                    'phase' => VitalSignPhase::Post,
                    'recorded_at' => $recordedAt,
                    'bp_systolic' => is_numeric($data['bp_systolic'] ?? null) ? (int) $data['bp_systolic'] : null,
                    'bp_diastolic' => is_numeric($data['bp_diastolic'] ?? null) ? (int) $data['bp_diastolic'] : null,
                    'pulse' => is_numeric($data['pulse'] ?? null) ? (int) $data['pulse'] : null,
                    'temperature' => is_numeric($data['temperature'] ?? null) ? (float) $data['temperature'] : null,
                    'respiratory_rate' => is_numeric($data['respiratory_rate'] ?? null) ? (int) $data['respiratory_rate'] : null,
                    'spo2' => is_numeric($data['spo2'] ?? null) ? (int) $data['spo2'] : null,
                    'consciousness' => $this->nullableString($data['anesthesia_recovery'] ?? null) ?? Consciousness::ComposMentis->value,
                    'breathing_pattern' => $this->nullableString($data['breathing_pattern'] ?? null),
                    'oxygen_device' => $oxygenDevice,
                    'oxygen_flow_lpm' => $oxygenDevice !== OxygenDevice::TanpaOksigen->value ? $flowLpm : null,
                    'notes' => $this->nullableString($data['notes'] ?? null),
                    'created_by' => $actor->id,
                ]);

                // Simpan catatan cairan pasca-op opsional (masuk, urine, drain+warna+bau, perdarahan)
                if (is_numeric($data['fluid_intake_ml'] ?? null) && (int) $data['fluid_intake_ml'] > 0) {
                    FluidRecord::query()->create([
                        'case_id' => $case->id,
                        'phase' => VitalSignPhase::Post,
                        'recorded_at' => $recordedAt,
                        'direction' => FluidDirection::Intake,
                        'kind' => FluidKind::Kristaloid,
                        'volume_ml' => (int) $data['fluid_intake_ml'],
                        'note' => 'Cairan masuk pasca-operasi',
                        'created_by' => $actor->id,
                    ]);
                }

                if (is_numeric($data['urine_ml'] ?? null) && (int) $data['urine_ml'] > 0) {
                    FluidRecord::query()->create([
                        'case_id' => $case->id,
                        'phase' => VitalSignPhase::Post,
                        'recorded_at' => $recordedAt,
                        'direction' => FluidDirection::Output,
                        'kind' => FluidKind::Urine,
                        'volume_ml' => (int) $data['urine_ml'],
                        'note' => 'Urine pasca-operasi',
                        'created_by' => $actor->id,
                    ]);
                }

                if (is_numeric($data['drain_ml'] ?? null) && (int) $data['drain_ml'] > 0) {
                    FluidRecord::query()->create([
                        'case_id' => $case->id,
                        'phase' => VitalSignPhase::Post,
                        'recorded_at' => $recordedAt,
                        'direction' => FluidDirection::Output,
                        'kind' => FluidKind::SuctionDrain,
                        'volume_ml' => (int) $data['drain_ml'],
                        'color' => $this->nullableString($data['drain_color'] ?? null),
                        'odor' => $this->nullableString($data['drain_odor'] ?? null),
                        'note' => 'Drain pasca-operasi',
                        'created_by' => $actor->id,
                    ]);
                }

                if (is_numeric($data['bleeding_ml'] ?? null) && (int) $data['bleeding_ml'] > 0) {
                    FluidRecord::query()->create([
                        'case_id' => $case->id,
                        'phase' => VitalSignPhase::Post,
                        'recorded_at' => $recordedAt,
                        'direction' => FluidDirection::Output,
                        'kind' => FluidKind::Perdarahan,
                        'volume_ml' => (int) $data['bleeding_ml'],
                        'note' => 'Perdarahan pasca-operasi',
                        'created_by' => $actor->id,
                    ]);
                }

                // Reset verifikasi dokter pasca-op jika ada observasi baru
                $this->revokePostopVerifications($case, 'Observasi pasca-operasi ditambahkan setelah verifikasi dokter.');

                return $observation;
            }, 'Menambahkan observasi pasca-operasi', $case->id, $actor);
        });
    }

    public function delete(SurgeryCase $case, PostopObservation $observation, User $actor): void
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat dihapus observasinya.'],
            ]);
        }

        abort_if($observation->case_id !== $case->id, 404);

        AuditContext::run(function () use ($case, $observation): void {
            $observation->delete();
            $this->revokePostopVerifications($case, 'Observasi pasca-operasi dihapus setelah verifikasi dokter.');
        }, 'Menghapus observasi pasca-operasi', $case->id, $actor);
    }

    private function revokePostopVerifications(SurgeryCase $case, string $reason): void
    {
        $verifications = CaseSignoff::query()
            ->where('case_id', $case->id)
            ->where('section', SignoffSection::PostOp)
            ->where('kind', SignoffKind::Verification)
            ->whereNull('revoked_at')
            ->get();

        foreach ($verifications as $v) {
            $v->update([
                'revoked_at' => now(),
                'revoke_reason' => $reason,
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
