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

class SignPreopCase
{
    public function execute(SurgeryCase $case, User $actor): CaseSignoff
    {
        if (! $actor->isPerawat()) {
            throw ValidationException::withMessages([
                'auth' => ['Pengesahan pra-operasi hanya dapat dilakukan oleh perawat.'],
            ]);
        }

        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah atau disahkan ulang.'],
            ]);
        }

        return DB::transaction(function () use ($case, $actor): CaseSignoff {
            return AuditContext::run(function () use ($case, $actor): CaseSignoff {
                return CaseSignoff::query()->create([
                    'case_id' => $case->id,
                    'section' => SignoffSection::PreOp,
                    'kind' => SignoffKind::Signoff,
                    'role_label' => 'perawat',
                    'user_id' => $actor->id,
                    'nip_snapshot' => $actor->nip,
                    'sip_snapshot' => null,
                    'signed_at' => now(),
                ]);
            }, 'Pengesahan pra-operasi oleh perawat', $case->id, $actor);
        });
    }
}
