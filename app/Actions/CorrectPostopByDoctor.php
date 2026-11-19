<?php

namespace App\Actions;

use App\Models\PostopSummary;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectPostopByDoctor
{
    /**
     * Koreksi dokter (berlaku bahkan setelah kasus berstatus `selesai`, wajib alasan & tercatat audit).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): PostopSummary
    {
        $isAssignedDoctor = ($actor->isDokterOperator() && $case->surgeon_id === $actor->id)
            || ($actor->isDokterAnestesi() && $case->anesthesiologist_id === $actor->id);

        if (! $isAssignedDoctor) {
            throw ValidationException::withMessages([
                'auth' => ['Koreksi medis hanya dapat dilakukan oleh dokter operator atau dokter anestesi yang ditetapkan pada kasus ini.'],
            ]);
        }

        $reason = is_string($data['reason'] ?? null) ? trim($data['reason']) : '';
        if ($reason === '' || mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan koreksi dokter wajib diisi minimal 5 karakter untuk dicatat di audit log.'],
            ]);
        }

        $notes = is_string($data['pre_transfer_condition_notes'] ?? null)
            ? trim($data['pre_transfer_condition_notes'])
            : '';

        if ($notes === '') {
            throw ValidationException::withMessages([
                'pre_transfer_condition_notes' => ['Catatan kondisi pasca-operasi hasil koreksi tidak boleh kosong.'],
            ]);
        }

        return DB::transaction(function () use ($case, $notes, $data, $reason, $actor): PostopSummary {
            return AuditContext::run(function () use ($case, $notes, $data, $actor): PostopSummary {
                $summary = $case->postopSummary;
                if ($summary !== null) {
                    $summary->update([
                        'pre_transfer_condition_notes' => $notes,
                    ]);
                } else {
                    $summary = PostopSummary::query()->create([
                        'case_id' => $case->id,
                        'ready_to_transfer' => true,
                        'pre_transfer_condition_notes' => $notes,
                        'assessed_at' => now('Asia/Jakarta'),
                        'assessed_by' => $actor->id,
                        'created_by' => $actor->id,
                    ]);
                }

                if (isset($data['operation_plan_actual']) && is_string($data['operation_plan_actual']) && trim($data['operation_plan_actual']) !== '') {
                    $case->update([
                        'operation_plan_actual' => trim($data['operation_plan_actual']),
                    ]);
                }

                return $summary->fresh() ?? $summary;
            }, $reason, $case->id, $actor);
        });
    }
}
