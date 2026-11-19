<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\IntraopRecord;
use App\Models\Specimen;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\OptimisticLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveIntraopRecord
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function savePositionAndArea(SurgeryCase $case, array $data, User $actor): IntraopRecord
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah catatan posisi dan area operasinya.'],
            ]);
        }

        OptimisticLock::assertFresh(
            $case->intraopRecord()->first(),
            $data['expected_updated_at'] ?? null,
        );

        $payload = [
            'position' => $this->nullableString($data['position'] ?? null),
            'pressure_protection' => array_values(array_filter(
                is_array($data['pressure_protection'] ?? null) ? $data['pressure_protection'] : [],
                fn (mixed $v): bool => is_string($v) && in_array($v, IntraopRecord::PRESSURE_PROTECTION_OPTIONS, true)
            )),
            'skin_antiseptic' => array_values(array_filter(
                is_array($data['skin_antiseptic'] ?? null) ? $data['skin_antiseptic'] : [],
                fn (mixed $v): bool => is_string($v) && in_array($v, IntraopRecord::SKIN_ANTISEPTIC_OPTIONS, true)
            )),
            'grounding_plate_site' => $this->nullableString($data['grounding_plate_site'] ?? null),
            'skin_condition_pre' => $this->nullableString($data['skin_condition_pre'] ?? null),
            'skin_condition_post' => $this->nullableString($data['skin_condition_post'] ?? null),
            'draping_done' => (bool) ($data['draping_done'] ?? false),
            'warming' => array_values(array_filter(
                is_array($data['warming'] ?? null) ? $data['warming'] : [],
                fn (mixed $v): bool => is_string($v) && in_array($v, IntraopRecord::WARMING_OPTIONS, true)
            )),
            'room_temperature_c' => is_numeric($data['room_temperature_c'] ?? null) ? (float) $data['room_temperature_c'] : null,
        ];

        return DB::transaction(function () use ($case, $payload, $actor): IntraopRecord {
            return AuditContext::run(function () use ($case, $payload, $actor): IntraopRecord {
                $existing = $case->intraopRecord;
                if ($existing !== null) {
                    $existing->update($payload);

                    return $existing->fresh() ?? $existing;
                }

                return IntraopRecord::query()->create([
                    ...$payload,
                    'case_id' => $case->id,
                    'created_by' => $actor->id,
                ]);
            }, 'Memperbarui persiapan posisi dan area intra-operasi', $case->id, $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSpecimenAndComplication(SurgeryCase $case, array $data, User $actor): IntraopRecord
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah catatan spesimen/komplikasinya.'],
            ]);
        }

        OptimisticLock::assertFresh(
            $case->intraopRecord()->first(),
            $data['expected_updated_at'] ?? null,
        );

        $errors = [];

        $hasComplication = (bool) ($data['has_complication'] ?? false);
        $compDesc = $this->nullableString($data['complication_description'] ?? null);
        $compHandle = $this->nullableString($data['complication_handling'] ?? null);

        if ($hasComplication) {
            if ($compDesc === null) {
                $errors['complication_description'] = ['Uraian kejadian khusus/komplikasi wajib diisi jika terdapat komplikasi.'];
            }
            if ($compHandle === null) {
                $errors['complication_handling'] = ['Tindakan penanganan komplikasi wajib diisi jika terdapat komplikasi.'];
            }
        }

        $canEditSpecimen = $actor->isPerawat();
        $hasSpecimen = (bool) ($data['has_specimen'] ?? false);
        $specType = $this->nullableString($data['specimen_type'] ?? null);
        $specOrgan = $this->nullableString($data['origin_organ'] ?? null);
        $specPurpose = $this->nullableString($data['purpose'] ?? null);
        $specPreservative = $this->nullableString($data['preservative'] ?? null);

        if ($canEditSpecimen && $hasSpecimen) {
            if ($specType === null) {
                $errors['specimen_type'] = ['Jenis spesimen/jaringan wajib diisi jika ada spesimen.'];
            }
            if ($specOrgan === null) {
                $errors['origin_organ'] = ['Asal jaringan/organ wajib diisi jika ada spesimen.'];
            }
            if ($specPurpose === null) {
                $errors['purpose'] = ['Tujuan pemeriksaan spesimen wajib dipilih jika ada spesimen.'];
            }
            if ($specPreservative === null) {
                $errors['preservative'] = ['Pengawet spesimen wajib dipilih jika ada spesimen.'];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use (
            $case,
            $actor,
            $hasComplication,
            $compDesc,
            $compHandle,
            $canEditSpecimen,
            $hasSpecimen,
            $specType,
            $specOrgan,
            $specPurpose,
            $specPreservative,
        ): IntraopRecord {
            return AuditContext::run(function () use (
                $case,
                $actor,
                $hasComplication,
                $compDesc,
                $compHandle,
                $canEditSpecimen,
                $hasSpecimen,
                $specType,
                $specOrgan,
                $specPurpose,
                $specPreservative,
            ): IntraopRecord {
                $compPayload = [
                    'has_complication' => $hasComplication,
                    'complication_description' => $hasComplication ? $compDesc : null,
                    'complication_handling' => $hasComplication ? $compHandle : null,
                ];

                $record = $case->intraopRecord;
                if ($record !== null) {
                    $record->update($compPayload);
                } else {
                    $record = IntraopRecord::query()->create([
                        ...$compPayload,
                        'case_id' => $case->id,
                        'created_by' => $actor->id,
                    ]);
                }

                if ($canEditSpecimen) {
                    $specimenPayload = [
                        'has_specimen' => $hasSpecimen,
                        'specimen_type' => $hasSpecimen ? $specType : null,
                        'origin_organ' => $hasSpecimen ? $specOrgan : null,
                        'purpose' => $hasSpecimen ? $specPurpose : null,
                        'preservative' => $hasSpecimen ? $specPreservative : null,
                    ];

                    $existingSpecimen = $case->specimens()->first();
                    if ($existingSpecimen !== null) {
                        $existingSpecimen->update($specimenPayload);
                    } else {
                        Specimen::query()->create([
                            ...$specimenPayload,
                            'case_id' => $case->id,
                            'created_by' => $actor->id,
                        ]);
                    }
                }

                return $record->fresh() ?? $record;
            }, 'Memperbarui pengelolaan spesimen dan catatan kejadian khusus intra-operasi', $case->id, $actor);
        });
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
