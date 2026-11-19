<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\CountVerification;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Models\CaseSignoff;
use App\Models\InstrumentCount;
use App\Models\SscSignOut;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\OptimisticLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveSscSignOut
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): SscSignOut
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah data SSC Sign Out-nya.'],
            ]);
        }

        if (! $actor->isPerawat()) {
            throw ValidationException::withMessages([
                'auth' => ['Formulir SSC Sign Out hanya dapat diisi oleh perawat.'],
            ]);
        }

        OptimisticLock::assertFresh(
            $case->sscSignOut()->first(),
            $data['expected_updated_at'] ?? null,
        );

        $targetStatus = SignOutStatus::tryFrom((string) ($data['status'] ?? '')) ?? SignOutStatus::Draf;

        $payload = [
            'status' => $targetStatus->value,
            'q1_procedure_confirmed' => $this->nullableBool($data['q1_procedure_confirmed'] ?? null),
            'q2_counts_correct' => $this->nullableBool($data['q2_counts_correct'] ?? null),
            'q3_specimen_labeled' => $this->nullableString($data['q3_specimen_labeled'] ?? null),
            'q4_equipment_problem' => $this->nullableBool($data['q4_equipment_problem'] ?? null),
            'q5_recovery_discussed' => $this->nullableBool($data['q5_recovery_discussed'] ?? null),
            'extra_6_procedure_matches' => $this->nullableString($data['extra_6_procedure_matches'] ?? null),
            'extra_7_changes_recorded' => $this->nullableString($data['extra_7_changes_recorded'] ?? null),
            'extra_8_discrepancy_followup' => $this->nullableString($data['extra_8_discrepancy_followup'] ?? null),
            'extra_9_specimen_matches' => $this->nullableString($data['extra_9_specimen_matches'] ?? null),
            'extra_10_equipment_issue_recorded' => $this->nullableString($data['extra_10_equipment_issue_recorded'] ?? null),
            'extra_11_complication_communicated' => $this->nullableString($data['extra_11_complication_communicated'] ?? null),
            'extra_12_postop_monitoring_discussed' => $this->nullableString($data['extra_12_postop_monitoring_discussed'] ?? null),
            'extra_13_devices_instructions' => $this->nullableString($data['extra_13_devices_instructions'] ?? null),
            'recovery_notes' => $this->nullableString($data['recovery_notes'] ?? null),
            'issues_and_actions' => $this->nullableString($data['issues_and_actions'] ?? null),
        ];

        $this->validateForVerification($case, $payload, $targetStatus);

        return DB::transaction(function () use ($case, $payload, $targetStatus, $actor): SscSignOut {
            return AuditContext::run(function () use ($case, $payload, $targetStatus, $actor): SscSignOut {
                $existing = $case->sscSignOut;
                $signedAt = null;
                if ($targetStatus === SignOutStatus::Terverifikasi) {
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
                    $signOut = $existing->fresh() ?? $existing;
                } else {
                    $signOut = SscSignOut::query()->create([
                        ...$attributes,
                        'case_id' => $case->id,
                        'created_by' => $actor->id,
                    ]);
                }

                // Reset verifikasi dokter pada Sign Out bila perawat mengubah formulir
                $activeVerifications = CaseSignoff::query()
                    ->where('case_id', $case->id)
                    ->where('section', SignoffSection::SignOut)
                    ->where('kind', SignoffKind::Verification)
                    ->whereNull('revoked_at')
                    ->get();

                foreach ($activeVerifications as $verification) {
                    $verification->update([
                        'revoked_at' => now(),
                        'revoke_reason' => 'Data SSC Sign Out diperbarui setelah verifikasi dokter.',
                    ]);
                }

                return $signOut;
            }, 'Memperbarui formulir SSC Sign Out', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateForVerification(SurgeryCase $case, array $payload, SignOutStatus $targetStatus): void
    {
        if ($targetStatus !== SignOutStatus::Terverifikasi) {
            return;
        }

        $errors = [];
        $missingWho = [];

        if ($payload['q1_procedure_confirmed'] !== true) {
            $missingWho[] = 'konfirmasi nama tindakan operasi (Q1)';
        }
        if ($payload['q2_counts_correct'] === null) {
            $missingWho[] = 'penghitungan instrumen, kasa, dan jarum (Q2)';
        }
        if ($payload['q3_specimen_labeled'] === null) {
            $missingWho[] = 'pemeriksaan label spesimen (Q3)';
        }
        if ($payload['q4_equipment_problem'] === null) {
            $missingWho[] = 'pemeriksaan masalah peralatan (Q4)';
        }
        if ($payload['q5_recovery_discussed'] !== true) {
            $missingWho[] = 'pembahasan rencana pemulihan pasien (Q5)';
        }

        if ($missingWho !== []) {
            $errors['status'] = [
                'Sign Out belum dapat berstatus Terverifikasi. Lengkapi pemeriksaan WHO berikut: '.implode(', ', $missingWho).'.',
            ];
        }

        // Validasi silang dengan data Hitungan Instrumen di Intra-Op
        $case->loadMissing(['instrumentCounts', 'intraopRecord', 'specimens']);
        $counts = $case->instrumentCounts;
        $record = $case->intraopRecord;

        $allFiveFinal = $counts->count() >= count(InstrumentCountItem::cases())
            && $counts->every(fn (InstrumentCount $c): bool => $c->final_count !== null);
        $hasTs = $counts->contains(fn (InstrumentCount $c): bool => $c->status === InstrumentCountStatus::TidakSesuai);
        $verification = $record !== null ? $record->count_verification : null;
        $discrepancyActions = $record !== null ? ($record->count_discrepancy_actions ?? []) : [];

        if (! $allFiveFinal || $verification === null) {
            $errors['q2_counts_correct'] = [
                'Hitungan akhir 5 item instrumen, kasa, dan jarum beserta verifikasi akhirnya wajib dilengkapi di tab Intra-Op sebelum Sign Out dinyatakan Terverifikasi.',
            ];
        } elseif ($hasTs || $verification === CountVerification::TidakSesuai || $payload['q2_counts_correct'] === false) {
            if ($discrepancyActions === [] || $payload['extra_8_discrepancy_followup'] !== 'sudah' || $payload['issues_and_actions'] === null) {
                $errors['issues_and_actions'] = [
                    'Hitungan instrumen tidak sesuai. Tindak lanjut SOP di tab Intra-Op, konfirmasi Q8 (Sudah), serta catatan masalah & tindakan wajib dilengkapi sebelum Sign Out Terverifikasi.',
                ];
            }
        }

        if ($payload['q4_equipment_problem'] === true) {
            if ($payload['extra_10_equipment_issue_recorded'] !== 'sudah' || $payload['issues_and_actions'] === null) {
                $errors['issues_and_actions'] = [
                    'Terdapat masalah peralatan (Q4). Pastikan pencatatan masalah (Q10 = Sudah) dan uraian tindakan pada kolom catatan diisi sebelum Terverifikasi.',
                ];
            }
        }

        $hasSpecimen = $case->specimens->contains(fn ($s): bool => (bool) $s->has_specimen);
        if ($hasSpecimen && $payload['q3_specimen_labeled'] !== 'ya') {
            $errors['q3_specimen_labeled'] = [
                'Terdapat spesimen operasi yang tercatat di tab Intra-Op; label spesimen (Q3) harus dikonfirmasi Ya sebelum Terverifikasi.',
            ];
        }

        if ($payload['q3_specimen_labeled'] === 'tidak' && $payload['issues_and_actions'] === null) {
            $errors['issues_and_actions'] = [
                'Label spesimen belum benar (Q3 = Tidak). Catatan masalah dan tindakan penyelesaian wajib diisi.',
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function nullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool) $value;
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
