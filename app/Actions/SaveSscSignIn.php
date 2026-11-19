<?php

namespace App\Actions;

use App\Enums\AllergyStatus;
use App\Enums\CaseStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SscStatus;
use App\Models\CaseSignoff;
use App\Models\SscSignIn;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\OptimisticLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSscSignIn
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): SscSignIn
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah data SSC Sign In-nya.'],
            ]);
        }

        if (! $case->hasRoomAndTeamAssigned()) {
            throw ValidationException::withMessages([
                'sign_in' => ['Ruang operasi dan seluruh tim (5 petugas) wajib ditetapkan terlebih dahulu sebelum mengisi SSC Sign In.'],
            ]);
        }

        OptimisticLock::assertFresh(
            $case->sscSignIn()->first(),
            $data['expected_updated_at'] ?? null,
        );

        if ($actor->isDokterAnestesi()) {
            return $this->saveAnesthesiologistSection($case, $data, $actor);
        }

        if (! $actor->isPerawat()) {
            throw ValidationException::withMessages([
                'auth' => ['Anda tidak memiliki kewenangan untuk mengubah SSC Sign In.'],
            ]);
        }

        $targetStatus = SscStatus::tryFrom((string) ($data['status'] ?? '')) ?? SscStatus::DalamProses;

        $readiness = [];
        $rawReadiness = is_array($data['readiness'] ?? null) ? $data['readiness'] : [];
        foreach (SscSignIn::READINESS_KEYS as $key) {
            $val = $rawReadiness[$key] ?? null;
            $readiness[$key] = is_string($val) && $val !== '' ? $val : null;
        }

        $payload = [
            'status' => $targetStatus->value,
            'identity_confirmed' => $this->nullableString($data['identity_confirmed'] ?? null),
            'procedure_confirmed' => $this->nullableString($data['procedure_confirmed'] ?? null),
            'site_confirmed' => $this->nullableString($data['site_confirmed'] ?? null),
            'consent_surgery' => $this->nullableString($data['consent_surgery'] ?? null),
            'consent_anesthesia' => $this->nullableString($data['consent_anesthesia'] ?? null),
            'site_marking' => $this->nullableString($data['site_marking'] ?? null),
            'readiness' => $readiness,
            'pulse_ox_attached' => array_key_exists('pulse_ox_attached', $data) && $data['pulse_ox_attached'] !== null
                ? (bool) $data['pulse_ox_attached']
                : null,
            'pulse_ox_functional' => array_key_exists('pulse_ox_functional', $data) && $data['pulse_ox_functional'] !== null
                ? (bool) $data['pulse_ox_functional']
                : null,
            'spo2' => is_numeric($data['spo2'] ?? null) ? (int) $data['spo2'] : null,
            'pulse' => is_numeric($data['pulse'] ?? null) ? (int) $data['pulse'] : null,
            'pulse_ox_note' => $this->nullableString($data['pulse_ox_note'] ?? null),
            'allergy_status' => $this->nullableString($data['allergy_status'] ?? null),
            'allergy_type' => $this->nullableString($data['allergy_type'] ?? null),
            'allergy_agent' => $this->nullableString($data['allergy_agent'] ?? null),
            'allergy_reaction' => $this->nullableString($data['allergy_reaction'] ?? null),
            'allergy_followup' => $this->nullableString($data['allergy_followup'] ?? null),
            'difficult_airway_risk' => $this->nullableString($data['difficult_airway_risk'] ?? null),
            'aspiration_risk' => $this->nullableString($data['aspiration_risk'] ?? null),
            'anesthesiologist_assessment' => $this->nullableString($data['anesthesiologist_assessment'] ?? null),
            'anticipation_plan' => $this->nullableString($data['anticipation_plan'] ?? null),
            'airway_equipment_ready' => $this->nullableString($data['airway_equipment_ready'] ?? null),
            'bleeding_risk' => $this->nullableString($data['bleeding_risk'] ?? null),
            'estimated_blood_loss_ml' => is_numeric($data['estimated_blood_loss_ml'] ?? null) ? (int) $data['estimated_blood_loss_ml'] : null,
            'iv_access' => $this->nullableString($data['iv_access'] ?? null),
            'fluids_ready' => $this->nullableString($data['fluids_ready'] ?? null),
            'blood_ready' => $this->nullableString($data['blood_ready'] ?? null),
            'bleeding_note' => $this->nullableString($data['bleeding_note'] ?? null),
            'notes_followup' => $this->nullableString($data['notes_followup'] ?? null),
        ];

        // Jika alergi != ada, bersihkan detail alergi
        if ($payload['allergy_status'] !== AllergyStatus::Ada->value) {
            $payload['allergy_type'] = null;
            $payload['allergy_agent'] = null;
            $payload['allergy_reaction'] = null;
            $payload['allergy_followup'] = null;
        }

        $this->validateForStatus($payload, $targetStatus);

        return DB::transaction(function () use ($case, $payload, $targetStatus, $actor): SscSignIn {
            return AuditContext::run(function () use ($case, $payload, $targetStatus, $actor): SscSignIn {
                $existing = $case->sscSignIn;

                $signedAt = null;
                if ($targetStatus === SscStatus::Selesai) {
                    $signedAt = $existing !== null && $existing->signed_at !== null
                        ? $existing->signed_at
                        : now();
                }

                $attributes = [
                    ...$payload,
                    'signed_at' => $signedAt,
                    'filled_by' => $actor->id,
                ];

                if ($existing !== null) {
                    $existing->update($attributes);
                    $signIn = $existing->fresh() ?? $existing;
                } else {
                    $signIn = SscSignIn::query()->create([
                        ...$attributes,
                        'case_id' => $case->id,
                        'created_by' => $actor->id,
                    ]);
                }

                // Reset verifikasi dokter anestesi jika data Sign In diperbarui
                $activeVerifications = CaseSignoff::query()
                    ->where('case_id', $case->id)
                    ->where('section', SignoffSection::SignIn)
                    ->where('kind', SignoffKind::Verification)
                    ->whereNull('revoked_at')
                    ->get();

                foreach ($activeVerifications as $verification) {
                    $verification->update([
                        'revoked_at' => now(),
                        'revoke_reason' => 'Data SSC Sign In diperbarui setelah verifikasi dokter anestesi.',
                    ]);
                }

                return $signIn;
            }, 'Memperbarui formulir SSC Sign In', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveAnesthesiologistSection(SurgeryCase $case, array $data, User $actor): SscSignIn
    {
        if ($case->anesthesiologist_id !== $actor->id) {
            throw ValidationException::withMessages([
                'auth' => ['Hanya dokter anestesi yang ditetapkan pada kasus ini yang dapat mengisi penilaian risiko anestesi.'],
            ]);
        }

        $payload = [
            'difficult_airway_risk' => $this->nullableString($data['difficult_airway_risk'] ?? null),
            'aspiration_risk' => $this->nullableString($data['aspiration_risk'] ?? null),
            'anesthesiologist_assessment' => $this->nullableString($data['anesthesiologist_assessment'] ?? null),
            'anticipation_plan' => $this->nullableString($data['anticipation_plan'] ?? null),
            'airway_equipment_ready' => $this->nullableString($data['airway_equipment_ready'] ?? null),
            'bleeding_risk' => $this->nullableString($data['bleeding_risk'] ?? null),
            'estimated_blood_loss_ml' => is_numeric($data['estimated_blood_loss_ml'] ?? null) ? (int) $data['estimated_blood_loss_ml'] : null,
            'iv_access' => $this->nullableString($data['iv_access'] ?? null),
            'fluids_ready' => $this->nullableString($data['fluids_ready'] ?? null),
            'blood_ready' => $this->nullableString($data['blood_ready'] ?? null),
            'bleeding_note' => $this->nullableString($data['bleeding_note'] ?? null),
        ];

        if (array_key_exists('notes_followup', $data)) {
            $payload['notes_followup'] = $this->nullableString($data['notes_followup']);
        }

        return DB::transaction(function () use ($case, $payload, $actor): SscSignIn {
            return AuditContext::run(function () use ($case, $payload, $actor): SscSignIn {
                $existing = $case->sscSignIn;

                if ($existing !== null) {
                    $updateData = $payload;
                    if ($existing->status === SscStatus::BelumDimulai) {
                        $updateData['status'] = SscStatus::DalamProses->value;
                    }
                    $existing->update($updateData);

                    return $existing->fresh() ?? $existing;
                }

                return SscSignIn::query()->create([
                    ...$payload,
                    'case_id' => $case->id,
                    'status' => SscStatus::DalamProses->value,
                    'filled_by' => $actor->id,
                    'created_by' => $actor->id,
                ]);
            }, 'Memperbarui penilaian risiko anestesi pada SSC Sign In', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateForStatus(array $payload, SscStatus $targetStatus): void
    {
        $errors = [];

        if ($payload['allergy_status'] === AllergyStatus::Ada->value) {
            if ($payload['allergy_agent'] === null && $payload['allergy_type'] === null) {
                $errors['allergy_agent'] = ['Jenis atau nama bahan penyebab alergi wajib diisi jika riwayat alergi adalah Ada.'];
            }
        }

        if ($targetStatus !== SscStatus::Selesai) {
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return;
        }

        $requiredFields = [
            'identity_confirmed' => 'Konfirmasi identitas pasien',
            'procedure_confirmed' => 'Konfirmasi jenis tindakan operasi',
            'site_confirmed' => 'Konfirmasi lokasi/sisi operasi',
            'consent_surgery' => 'Informed consent operasi',
            'consent_anesthesia' => 'Informed consent anestesi',
            'site_marking' => 'Penandaan lokasi operasi',
            'allergy_status' => 'Pemeriksaan riwayat alergi',
            'difficult_airway_risk' => 'Risiko kesulitan jalan napas',
            'aspiration_risk' => 'Risiko aspirasi',
            'airway_equipment_ready' => 'Kesiapan alat pendukung jalan napas',
            'bleeding_risk' => 'Risiko perdarahan',
            'iv_access' => 'Akses intravena',
            'fluids_ready' => 'Kesiapan cairan',
            'blood_ready' => 'Kesiapan darah',
        ];

        $missing = [];
        foreach ($requiredFields as $field => $label) {
            if ($payload[$field] === null) {
                $missing[] = $label;
            }
        }

        $readiness = is_array($payload['readiness'] ?? null) ? $payload['readiness'] : [];
        foreach (SscSignIn::READINESS_KEYS as $key) {
            if (($readiness[$key] ?? null) === null) {
                $missing[] = 'Kesiapan alat & obat anestesi (semua 7 item)';

                break;
            }
        }

        if ($payload['pulse_ox_attached'] === null || $payload['pulse_ox_functional'] === null) {
            $missing[] = 'Pemeriksaan Pulse Oximeter (terpasang & berfungsi)';
        }

        if ($missing !== []) {
            $errors['status'] = [
                'Status Sign In belum dapat dinyatakan Selesai. Lengkapi bagian berikut: '.implode(', ', $missing).'.',
            ];
        }

        if (SscSignIn::hasProblematicValues($payload) && $payload['notes_followup'] === null) {
            $errors['notes_followup'] = [
                'Terdapat item pemeriksaan yang bermasalah (Tidak Sesuai / Belum Ada / Tidak Siap / Tidak Memadai). Catatan tindak lanjut wajib diisi sebelum menyelesaikan Sign In.',
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
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
