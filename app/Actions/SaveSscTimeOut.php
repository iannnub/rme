<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\SscStatus;
use App\Models\SscTimeOut;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\OptimisticLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSscTimeOut
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): SscTimeOut
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah data SSC Time Out-nya.'],
            ]);
        }

        OptimisticLock::assertFresh(
            $case->sscTimeOut()->first(),
            $data['expected_updated_at'] ?? null,
        );

        if ($actor->isDokterOperator()) {
            return $this->saveSurgeonBlock($case, $data, $actor);
        }

        if ($actor->isDokterAnestesi()) {
            return $this->saveAnesthesiologistBlock($case, $data, $actor);
        }

        if (! $actor->isPerawat()) {
            throw ValidationException::withMessages([
                'auth' => ['Anda tidak memiliki kewenangan untuk mengubah SSC Time Out.'],
            ]);
        }

        $targetStatus = SscStatus::tryFrom((string) ($data['status'] ?? '')) ?? SscStatus::DalamProses;

        $antibiotic = $this->nullableString($data['antibiotic_prophylaxis'] ?? null);
        $imagingNeeded = $this->nullableString($data['imaging_needed'] ?? null);

        $existing = $case->sscTimeOut;

        $payload = [
            'status' => $targetStatus->value,
            'intro_circulating' => (bool) ($data['intro_circulating'] ?? false),
            'intro_scrub' => (bool) ($data['intro_scrub'] ?? false),
            'intro_anesthesia_nurse' => (bool) ($data['intro_anesthesia_nurse'] ?? false),
            'intro_surgeon' => (bool) ($data['intro_surgeon'] ?? false),
            'intro_anesthesiologist' => (bool) ($data['intro_anesthesiologist'] ?? false),
            'identity_procedure_site_confirmed' => (bool) ($data['identity_procedure_site_confirmed'] ?? false),
            'antibiotic_prophylaxis' => $antibiotic,
            'antibiotic_name' => $antibiotic === 'ya' ? $this->nullableString($data['antibiotic_name'] ?? null) : null,
            'antibiotic_given_at' => $antibiotic === 'ya' && ! empty($data['antibiotic_given_at']) ? $data['antibiotic_given_at'] : null,
            'antibiotic_confirmed_by' => $antibiotic === 'ya' ? $this->nullableString($data['antibiotic_confirmed_by'] ?? null) : null,
            'nurse_sterility_confirmed' => (bool) ($data['nurse_sterility_confirmed'] ?? false),
            'nurse_indicator_checked' => (bool) ($data['nurse_indicator_checked'] ?? false),
            'equipment_problem' => $this->nullableString($data['equipment_problem'] ?? null),
            'other_concern' => $this->nullableString($data['other_concern'] ?? null),
            'nurse_note' => $this->nullableString($data['nurse_note'] ?? null),
            'imaging_needed' => $imagingNeeded,
            'imaging_displayed' => $imagingNeeded === 'ya' ? $this->nullableString($data['imaging_displayed'] ?? null) : null,
            'imaging_matches' => $imagingNeeded === 'ya' ? $this->nullableString($data['imaging_matches'] ?? null) : null,
        ];

        $this->validateNursePayload($payload, $targetStatus);

        return DB::transaction(function () use ($case, $payload, $targetStatus, $existing, $actor): SscTimeOut {
            return AuditContext::run(function () use ($case, $payload, $targetStatus, $existing, $actor): SscTimeOut {
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

                    return $existing->fresh() ?? $existing;
                }

                return SscTimeOut::query()->create([
                    ...$attributes,
                    'case_id' => $case->id,
                    'created_by' => $actor->id,
                ]);
            }, 'Memperbarui formulir SSC Time Out', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveSurgeonBlock(SurgeryCase $case, array $data, User $actor): SscTimeOut
    {
        if ($case->surgeon_id !== $actor->id) {
            throw ValidationException::withMessages([
                'auth' => ['Hanya dokter operator yang ditetapkan pada kasus ini yang dapat mengisi blok Dokter Operator pada Time Out.'],
            ]);
        }

        $criticalSteps = $this->nullableString($data['surgeon_critical_steps'] ?? null);
        $criticalText = $this->nullableString($data['surgeon_critical_steps_text'] ?? null);

        if ($criticalSteps === 'ya' && $criticalText === null) {
            throw ValidationException::withMessages([
                'surgeon_critical_steps_text' => ['Uraian langkah kritis wajib diisi jika terdapat langkah kritis atau tindakan tidak rutin.'],
            ]);
        }

        $payload = [
            'surgeon_critical_steps' => $criticalSteps,
            'surgeon_critical_steps_text' => $criticalSteps === 'ya' ? $criticalText : null,
            'surgeon_estimated_duration_min' => is_numeric($data['surgeon_estimated_duration_min'] ?? null)
                ? (int) $data['surgeon_estimated_duration_min']
                : null,
            'surgeon_estimated_blood_loss_ml' => is_numeric($data['surgeon_estimated_blood_loss_ml'] ?? null)
                ? (int) $data['surgeon_estimated_blood_loss_ml']
                : null,
        ];

        return DB::transaction(function () use ($case, $payload, $actor): SscTimeOut {
            return AuditContext::run(function () use ($case, $payload, $actor): SscTimeOut {
                $existing = $case->sscTimeOut;
                if ($existing !== null) {
                    $update = $payload;
                    if ($existing->status === SscStatus::BelumDimulai) {
                        $update['status'] = SscStatus::DalamProses->value;
                    }
                    $existing->update($update);

                    return $existing->fresh() ?? $existing;
                }

                return SscTimeOut::query()->create([
                    ...$payload,
                    'case_id' => $case->id,
                    'status' => SscStatus::DalamProses->value,
                    'filled_by' => $actor->id,
                    'created_by' => $actor->id,
                ]);
            }, 'Memperbarui blok Dokter Operator pada SSC Time Out', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveAnesthesiologistBlock(SurgeryCase $case, array $data, User $actor): SscTimeOut
    {
        if ($case->anesthesiologist_id !== $actor->id) {
            throw ValidationException::withMessages([
                'auth' => ['Hanya dokter anestesi yang ditetapkan pada kasus ini yang dapat mengisi blok Dokter Anestesi pada Time Out.'],
            ]);
        }

        $payload = [
            'anesthesia_special_condition' => $this->nullableString($data['anesthesia_special_condition'] ?? null),
            'anesthesia_risk' => $this->nullableString($data['anesthesia_risk'] ?? null),
            'anesthesia_note' => $this->nullableString($data['anesthesia_note'] ?? null),
        ];

        return DB::transaction(function () use ($case, $payload, $actor): SscTimeOut {
            return AuditContext::run(function () use ($case, $payload, $actor): SscTimeOut {
                $existing = $case->sscTimeOut;
                if ($existing !== null) {
                    $update = $payload;
                    if ($existing->status === SscStatus::BelumDimulai) {
                        $update['status'] = SscStatus::DalamProses->value;
                    }
                    $existing->update($update);

                    return $existing->fresh() ?? $existing;
                }

                return SscTimeOut::query()->create([
                    ...$payload,
                    'case_id' => $case->id,
                    'status' => SscStatus::DalamProses->value,
                    'filled_by' => $actor->id,
                    'created_by' => $actor->id,
                ]);
            }, 'Memperbarui blok Dokter Anestesi pada SSC Time Out', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateNursePayload(array $payload, SscStatus $targetStatus): void
    {
        $errors = [];

        if ($payload['antibiotic_prophylaxis'] === 'ya') {
            if ($payload['antibiotic_name'] === null) {
                $errors['antibiotic_name'] = ['Nama antibiotik profilaksis wajib diisi jika dipilih Ya.'];
            }
            if ($payload['antibiotic_given_at'] === null) {
                $errors['antibiotic_given_at'] = ['Waktu pemberian antibiotik wajib diisi jika dipilih Ya.'];
            }
        }

        if ($targetStatus === SscStatus::Selesai) {
            $missing = [];
            if (
                ! $payload['intro_circulating']
                || ! $payload['intro_scrub']
                || ! $payload['intro_anesthesia_nurse']
                || ! $payload['intro_surgeon']
                || ! $payload['intro_anesthesiologist']
            ) {
                $missing[] = 'perkenalan 5 anggota tim';
            }

            if (! $payload['identity_procedure_site_confirmed']) {
                $missing[] = 'konfirmasi identitas, jenis tindakan, dan lokasi operasi';
            }

            if ($payload['antibiotic_prophylaxis'] === null) {
                $missing[] = 'status antibiotik profilaksis';
            }

            if (! $payload['nurse_sterility_confirmed'] || ! $payload['nurse_indicator_checked']) {
                $missing[] = 'konfirmasi sterilitas & indikator sterilisasi oleh tim perawat';
            }

            if ($payload['equipment_problem'] === null || $payload['other_concern'] === null) {
                $missing[] = 'pemeriksaan masalah peralatan & kekhawatiran lain tim perawat';
            }

            if ($payload['imaging_needed'] === null) {
                $missing[] = 'kebutuhan penampilan pencitraan medis';
            } elseif ($payload['imaging_needed'] === 'ya' && ($payload['imaging_displayed'] === null || $payload['imaging_matches'] === null)) {
                $missing[] = 'konfirmasi penampilan dan kesesuaian pencitraan medis';
            }

            if ($missing !== []) {
                $errors['status'] = [
                    'Status Time Out belum dapat dinyatakan Selesai. Lengkapi: '.implode(', ', $missing).'.',
                ];
            }

            if (($payload['equipment_problem'] === 'ya' || $payload['other_concern'] === 'ya') && $payload['nurse_note'] === null) {
                $errors['nurse_note'] = ['Catatan tim perawat wajib diisi jika terdapat masalah peralatan atau kekhawatiran lain.'];
            }
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
