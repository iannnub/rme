<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Models\CaseSignoff;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SignIntraopCase
{
    /** @var list<string> */
    public const VALID_ROLE_LABELS = [
        'perawat_sirkuler',
        'perawat_instrumen',
        'dokter_anestesi',
        'dokter_operator',
    ];

    public function execute(SurgeryCase $case, string $roleLabel, User $actor): CaseSignoff
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat disahkan ulang.'],
            ]);
        }

        if (! in_array($roleLabel, self::VALID_ROLE_LABELS, true)) {
            throw ValidationException::withMessages([
                'role_label' => ['Peran pengesahan intra-operasi tidak valid.'],
            ]);
        }

        $expectedUserId = match ($roleLabel) {
            'perawat_sirkuler' => $case->circulating_nurse_id,
            'perawat_instrumen' => $case->scrub_nurse_id,
            'dokter_anestesi' => $case->anesthesiologist_id,
            'dokter_operator' => $case->surgeon_id,
        };

        if ($expectedUserId === null || $expectedUserId !== $actor->id) {
            throw ValidationException::withMessages([
                'auth' => ['Anda bukan petugas yang ditetapkan pada posisi pengesahan ini untuk kasus tersebut.'],
            ]);
        }

        return DB::transaction(function () use ($case, $roleLabel, $actor): CaseSignoff {
            return AuditContext::run(function () use ($case, $roleLabel, $actor): CaseSignoff {
                $existing = CaseSignoff::query()
                    ->where('case_id', $case->id)
                    ->where('section', SignoffSection::IntraOp)
                    ->where('kind', SignoffKind::Signoff)
                    ->where('role_label', $roleLabel)
                    ->whereNull('revoked_at')
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }

                return CaseSignoff::query()->create([
                    'case_id' => $case->id,
                    'section' => SignoffSection::IntraOp,
                    'kind' => SignoffKind::Signoff,
                    'role_label' => $roleLabel,
                    'user_id' => $actor->id,
                    'nip_snapshot' => $actor->nip,
                    'sip_snapshot' => $actor->sip,
                    'signed_at' => now(),
                ]);
            }, "Pengesahan intra-operasi sebagai {$roleLabel}", $case->id, $actor);
        });
    }
}
