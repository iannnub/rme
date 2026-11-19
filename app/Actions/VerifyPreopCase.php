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

class VerifyPreopCase
{
    public function execute(SurgeryCase $case, User $actor): CaseSignoff
    {
        if (! $actor->isDokter()) {
            throw ValidationException::withMessages([
                'auth' => ['Verifikasi pra-operasi hanya dapat dilakukan oleh dokter.'],
            ]);
        }

        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diverifikasi ulang.'],
            ]);
        }

        return DB::transaction(function () use ($case, $actor): CaseSignoff {
            return AuditContext::run(function () use ($case, $actor): CaseSignoff {
                return CaseSignoff::query()->create([
                    'case_id' => $case->id,
                    'section' => SignoffSection::PreOp,
                    'kind' => SignoffKind::Verification,
                    'role_label' => $actor->role->value,
                    'user_id' => $actor->id,
                    'nip_snapshot' => $actor->nip,
                    'sip_snapshot' => $actor->sip,
                    'signed_at' => now(),
                ]);
            }, 'Verifikasi pra-operasi oleh dokter', $case->id, $actor);
        });
    }
}
