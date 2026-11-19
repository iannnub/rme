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

class VerifySscSignIn
{
    public function execute(SurgeryCase $case, User $actor): CaseSignoff
    {
        if (! $actor->isDokterAnestesi() || $case->anesthesiologist_id !== $actor->id) {
            throw ValidationException::withMessages([
                'auth' => ['Verifikasi SSC Sign In hanya dapat dilakukan oleh dokter anestesi yang ditetapkan pada kasus ini.'],
            ]);
        }

        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diverifikasi ulang.'],
            ]);
        }

        if ($case->sscSignIn === null) {
            throw ValidationException::withMessages([
                'sign_in' => ['Formulir SSC Sign In harus disimpan terlebih dahulu sebelum diverifikasi.'],
            ]);
        }

        return DB::transaction(function () use ($case, $actor): CaseSignoff {
            return AuditContext::run(function () use ($case, $actor): CaseSignoff {
                return CaseSignoff::query()->create([
                    'case_id' => $case->id,
                    'section' => SignoffSection::SignIn,
                    'kind' => SignoffKind::Verification,
                    'role_label' => $actor->role->value,
                    'user_id' => $actor->id,
                    'nip_snapshot' => $actor->nip,
                    'sip_snapshot' => $actor->sip,
                    'signed_at' => now(),
                ]);
            }, 'Verifikasi SSC Sign In oleh dokter anestesi', $case->id, $actor);
        });
    }
}
