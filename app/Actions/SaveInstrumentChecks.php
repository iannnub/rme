<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\InstrumentCheck;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveInstrumentChecks
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function execute(SurgeryCase $case, array $items, User $actor): void
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah data cek alatnya.'],
            ]);
        }

        $errors = [];
        $normalized = [];
        $seenInstruments = [];

        foreach ($items as $index => $item) {
            $instrumentId = is_numeric($item['instrument_id'] ?? null) ? (int) $item['instrument_id'] : 0;
            if ($instrumentId <= 0) {
                $errors["items.{$index}.instrument_id"] = ['Alat dari master data wajib dipilih.'];

                continue;
            }

            if (in_array($instrumentId, $seenInstruments, true)) {
                $errors["items.{$index}.instrument_id"] = ['Alat yang sama tidak boleh ditambahkan lebih dari satu kali.'];

                continue;
            }
            $seenInstruments[] = $instrumentId;

            $isAvailable = (bool) ($item['is_available'] ?? false);
            $isSterile = (bool) ($item['is_sterile'] ?? false);
            $isFunctional = (bool) ($item['is_functional'] ?? false);
            $isRusty = (bool) ($item['is_rusty'] ?? false);
            $note = is_string($item['note'] ?? null) ? trim($item['note']) : '';

            $hasIssue = ! $isAvailable || ! $isSterile || ! $isFunctional || $isRusty;
            if ($hasIssue && $note === '') {
                $errors["items.{$index}.note"] = [
                    'Alat bermasalah (tidak tersedia/tidak steril/tidak berfungsi/berkarat) wajib dilengkapi catatan tindak lanjut.',
                ];
            }

            $normalized[] = [
                'instrument_id' => $instrumentId,
                'is_available' => $isAvailable,
                'is_sterile' => $isSterile,
                'is_functional' => $isFunctional,
                'is_rusty' => $isRusty,
                'note' => $note !== '' ? $note : null,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($case, $normalized, $actor): void {
            AuditContext::run(function () use ($case, $normalized, $actor): void {
                $keptIds = [];
                $now = now();

                foreach ($normalized as $row) {
                    $existing = InstrumentCheck::query()
                        ->where('case_id', $case->id)
                        ->where('instrument_id', $row['instrument_id'])
                        ->first();

                    if ($existing !== null) {
                        $existing->update([
                            'is_available' => $row['is_available'],
                            'is_sterile' => $row['is_sterile'],
                            'is_functional' => $row['is_functional'],
                            'is_rusty' => $row['is_rusty'],
                            'note' => $row['note'],
                            'checked_by' => $actor->id,
                            'checked_at' => $now,
                        ]);
                        $keptIds[] = $existing->id;
                    } else {
                        $created = InstrumentCheck::query()->create([
                            'case_id' => $case->id,
                            'instrument_id' => $row['instrument_id'],
                            'is_available' => $row['is_available'],
                            'is_sterile' => $row['is_sterile'],
                            'is_functional' => $row['is_functional'],
                            'is_rusty' => $row['is_rusty'],
                            'note' => $row['note'],
                            'checked_by' => $actor->id,
                            'checked_at' => $now,
                        ]);
                        $keptIds[] = $created->id;
                    }
                }

                $toDeleteQuery = InstrumentCheck::query()->where('case_id', $case->id);
                if ($keptIds !== []) {
                    $toDeleteQuery->whereNotIn('id', $keptIds);
                }

                foreach ($toDeleteQuery->get() as $obsolete) {
                    $obsolete->delete();
                }
            }, 'Memperbarui cek ketersediaan dan kesterilan alat', $case->id, $actor);
        });
    }
}
