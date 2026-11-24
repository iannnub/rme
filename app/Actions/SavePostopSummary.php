<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Models\CaseSignoff;
use App\Models\PostopSummary;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use App\Support\PreopClinicalDefaults;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePostopSummary
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): PostopSummary
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah ringkasan kondisi pindah oleh perawat.'],
            ]);
        }

        $ready = (bool) ($data['ready_to_transfer'] ?? false);
        $notes = is_string($data['pre_transfer_condition_notes'] ?? null)
            ? trim($data['pre_transfer_condition_notes'])
            : '';

        if ($ready && $notes === '') {
            throw ValidationException::withMessages([
                'pre_transfer_condition_notes' => ['Catatan kondisi pasien sebelum dipindahkan wajib diisi jika pasien dinyatakan layak dipindahkan.'],
            ]);
        }

        $assessedAt = is_string($data['assessed_at'] ?? null) && trim($data['assessed_at']) !== ''
            ? Carbon::parse($data['assessed_at'], 'Asia/Jakarta')
            : now('Asia/Jakarta');

        return DB::transaction(function () use ($case, $ready, $notes, $assessedAt, $data, $actor): PostopSummary {
            return AuditContext::run(function () use ($case, $ready, $notes, $assessedAt, $data, $actor): PostopSummary {
                $existing = $case->postopSummary;
                $payload = [
                    'ready_to_transfer' => $ready,
                    'pre_transfer_condition_notes' => $notes !== '' ? $notes : null,
                    'followup_plan' => array_key_exists('followup_plan', $data) && is_array($data['followup_plan'])
                        ? PreopClinicalDefaults::mergePostopFollowup($data['followup_plan'])
                        : ($existing?->followup_plan),
                    'assessed_at' => $assessedAt,
                    'assessed_by' => $actor->id,
                ];

                if ($existing !== null) {
                    $existing->update($payload);
                    $summary = $existing->fresh() ?? $existing;
                } else {
                    $summary = PostopSummary::query()->create([
                        ...$payload,
                        'case_id' => $case->id,
                        'created_by' => $actor->id,
                    ]);
                }

                $verifications = CaseSignoff::query()
                    ->where('case_id', $case->id)
                    ->where('section', SignoffSection::PostOp)
                    ->where('kind', SignoffKind::Verification)
                    ->whereNull('revoked_at')
                    ->get();

                foreach ($verifications as $v) {
                    $v->update([
                        'revoked_at' => now(),
                        'revoke_reason' => 'Kondisi sebelum pindah diperbarui setelah verifikasi dokter.',
                    ]);
                }

                return $summary;
            }, 'Memperbarui kondisi pasien sebelum dipindahkan', $case->id, $actor);
        });
    }
}
