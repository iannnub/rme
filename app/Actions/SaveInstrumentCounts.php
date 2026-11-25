<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\CountVerification;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Models\InstrumentCount;
use App\Models\IntraopRecord;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveInstrumentCounts
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): void
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah hitungan instrumennya.'],
            ]);
        }

        $rawCounts = is_array($data['counts'] ?? null) ? $data['counts'] : [];
        $verification = is_string($data['count_verification'] ?? null) && $data['count_verification'] !== ''
            ? CountVerification::tryFrom($data['count_verification'])
            : null;
        $discrepancyActions = array_values(array_filter(
            is_array($data['count_discrepancy_actions'] ?? null) ? $data['count_discrepancy_actions'] : [],
            fn (mixed $v): bool => is_string($v) && in_array($v, IntraopRecord::COUNT_DISCREPANCY_ACTIONS, true)
        ));

        $activeItems = InstrumentCountItem::casesForProfile($case->surgeryProfile());
        $computedRows = [];
        $hasTs = false;
        $allFinalFilled = true;

        foreach ($activeItems as $enumItem) {
            $row = $rawCounts[$enumItem->value] ?? [];
            $initial = is_numeric($row['initial_count'] ?? null) ? max(0, (int) $row['initial_count']) : 0;
            $additional = is_numeric($row['additional_count'] ?? null) ? max(0, (int) $row['additional_count']) : 0;
            $final = isset($row['final_count']) && $row['final_count'] !== '' && is_numeric($row['final_count'])
                ? max(0, (int) $row['final_count'])
                : null;

            if ($final === null) {
                $allFinalFilled = false;
            }

            $status = InstrumentCount::computeStatus($initial, $additional, $final);
            if ($status === InstrumentCountStatus::TidakSesuai) {
                $hasTs = true;
            }

            $computedRows[] = [
                'item' => $enumItem,
                'initial_count' => $initial,
                'additional_count' => $additional,
                'final_count' => $final,
                'status' => $status,
            ];
        }

        $errors = [];

        if ($hasTs && $verification === CountVerification::LengkapSesuai) {
            $errors['count_verification'] = [
                'Verifikasi akhir tidak dapat dinyatakan Lengkap & Sesuai karena terdapat item hitungan berstatus Tidak Sesuai (TS).',
            ];
        }

        if (($hasTs || $verification === CountVerification::TidakSesuai) && $discrepancyActions === []) {
            $errors['count_discrepancy_actions'] = [
                'Terdapat ketidaksesuaian hitungan instrumen/kasa/jarum. Wajib memilih minimal satu tindakan tindak lanjut SOP (Pencarian Ulang, Lapor Operator, atau Foto X-Ray).',
            ];
        }

        if ($verification === CountVerification::LengkapSesuai && ! $allFinalFilled) {
            $requiredTotal = count($activeItems);
            $errors['count_verification'] = [
                "Seluruh {$requiredTotal} item hitungan akhir wajib diisi sebelum verifikasi akhir dinyatakan Lengkap & Sesuai.",
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($case, $computedRows, $verification, $discrepancyActions, $actor): void {
            AuditContext::run(function () use ($case, $computedRows, $verification, $discrepancyActions, $actor): void {
                foreach ($computedRows as $row) {
                    $existing = InstrumentCount::query()
                        ->where('case_id', $case->id)
                        ->where('item', $row['item']->value)
                        ->first();

                    $payload = [
                        'initial_count' => $row['initial_count'],
                        'additional_count' => $row['additional_count'],
                        'final_count' => $row['final_count'],
                        'status' => $row['status']?->value,
                    ];

                    if ($existing !== null) {
                        $existing->update($payload);
                    } else {
                        InstrumentCount::query()->create([
                            ...$payload,
                            'case_id' => $case->id,
                            'item' => $row['item']->value,
                            'created_by' => $actor->id,
                        ]);
                    }
                }

                $record = $case->intraopRecord;
                $recordPayload = [
                    'count_verification' => $verification?->value,
                    'count_discrepancy_actions' => $discrepancyActions,
                ];

                if ($record !== null) {
                    $record->update($recordPayload);
                } else {
                    IntraopRecord::query()->create([
                        ...$recordPayload,
                        'case_id' => $case->id,
                        'created_by' => $actor->id,
                    ]);
                }
            }, 'Memperbarui penghitungan instrumen, kasa, dan jarum intra-operasi', $case->id, $actor);
        });
    }
}
