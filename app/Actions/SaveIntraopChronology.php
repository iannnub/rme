<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\SscStatus;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\OptimisticLock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveIntraopChronology
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): SurgeryCase
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah kronologi waktunya.'],
            ]);
        }

        OptimisticLock::assertFresh(
            $case->fresh(),
            $data['expected_updated_at'] ?? null,
        );

        $case->loadMissing(['sscSignIn', 'sscTimeOut']);

        $entered = $this->parseTime($data['entered_or_at'] ?? null);
        $anesthesia = $this->parseTime($data['anesthesia_start_at'] ?? null);
        $incision = $this->parseTime($data['incision_at'] ?? null);
        $surgeryEnd = $this->parseTime($data['surgery_end_at'] ?? null);
        $leftOr = $this->parseTime($data['left_or_at'] ?? null);

        $errors = [];

        // Aturan wajib 01-alur-sistem: Sign In wajib Selesai sebelum waktu mulai anestesi diisi
        $signInDone = $case->sscSignIn !== null && $case->sscSignIn->status === SscStatus::Selesai;
        if ($anesthesia !== null && ! $signInDone) {
            $errors['anesthesia_start_at'] = [
                'Waktu mulai anestesi terkunci hingga SSC Sign In berstatus Selesai.',
            ];
        }

        // Aturan wajib 01-alur-sistem: Time Out wajib Selesai sebelum waktu mulai insisi diisi
        $timeOutDone = $case->sscTimeOut !== null && $case->sscTimeOut->status === SscStatus::Selesai;
        if ($incision !== null && ! $timeOutDone) {
            $errors['incision_at'] = [
                'Waktu mulai insisi terkunci hingga SSC Time Out berstatus Selesai.',
            ];
        }

        // Validasi urutan waktu menaik: entered ≤ anesthesia ≤ incision ≤ surgery_end ≤ left_or
        $sequence = [
            ['key' => 'entered_or_at', 'label' => 'Masuk Kamar Operasi', 'time' => $entered],
            ['key' => 'anesthesia_start_at', 'label' => 'Mulai Anestesi', 'time' => $anesthesia],
            ['key' => 'incision_at', 'label' => 'Mulai Insisi', 'time' => $incision],
            ['key' => 'surgery_end_at', 'label' => 'Selesai Operasi', 'time' => $surgeryEnd],
            ['key' => 'left_or_at', 'label' => 'Keluar Kamar Operasi', 'time' => $leftOr],
        ];

        $prev = null;
        foreach ($sequence as $step) {
            if ($step['time'] === null) {
                continue;
            }
            if ($prev !== null && $step['time']->lt($prev['time'])) {
                $errors[$step['key']] = [
                    "Waktu {$step['label']} tidak boleh lebih awal dari waktu {$prev['label']}.",
                ];
            }
            $prev = $step;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($case, $data, $entered, $anesthesia, $incision, $surgeryEnd, $leftOr, $actor): SurgeryCase {
            return AuditContext::run(function () use ($case, $data, $entered, $anesthesia, $incision, $surgeryEnd, $leftOr): SurgeryCase {
                $updates = [
                    'entered_or_at' => $entered,
                    'anesthesia_start_at' => $anesthesia,
                    'incision_at' => $incision,
                    'surgery_end_at' => $surgeryEnd,
                    'left_or_at' => $leftOr,
                ];

                if (array_key_exists('anesthesia_type_id', $data)) {
                    $updates['anesthesia_type_id'] = is_numeric($data['anesthesia_type_id'])
                        ? (int) $data['anesthesia_type_id']
                        : null;
                }

                if (array_key_exists('operation_plan_actual', $data)) {
                    $updates['operation_plan_actual'] = is_string($data['operation_plan_actual']) && trim($data['operation_plan_actual']) !== ''
                        ? trim($data['operation_plan_actual'])
                        : null;
                }

                $case->update($updates);

                return $case->fresh() ?? $case;
            }, 'Memperbarui kronologi waktu dan informasi tindakan intra-operasi', $case->id, $actor);
        });
    }

    private function parseTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value, 'Asia/Jakarta');
    }
}
