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

class VerifySscSignOut
{
    public function execute(SurgeryCase $case, User $actor): CaseSignoff
    {
        $isAssignedDoctor = ($actor->isDokterOperator() && $case->surgeon_id === $actor->id)
            || ($actor->isDokterAnestesi() && $case->anesthesiologist_id === $actor->id);

        if (! $isAssignedDoctor) {
            throw ValidationException::withMessages([
                'auth' => ['Verifikasi SSC Sign Out hanya dapat dilakukan oleh dokter operator atau dokter anestesi yang ditetapkan pada kasus ini.'],
            ]);
        }

        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diverifikasi ulang.'],
            ]);
        }

        if ($case->sscSignOut === null) {
            throw ValidationException::withMessages([
                'sign_out' => ['Formulir SSC Sign Out harus disimpan terlebih dahulu sebelum diverifikasi.'],
            ]);
        }

        return DB::transaction(function () use ($case, $actor): CaseSignoff {
            return AuditContext::run(function () use ($case, $actor): CaseSignoff {
                return CaseSignoff::query()->create([
                    'case_id' => $case->id,
                    'section' => SignoffSection::SignOut,
                    'kind' => SignoffKind::Verification,
                    'role_label' => $actor->role->value,
                    'user_id' => $actor->id,
                    'nip_snapshot' => $actor->nip,
                    'sip_snapshot' => $actor->sip,
                    'signed_at' => now(),
                ]);
            }, 'Verifikasi SSC Sign Out oleh dokter', $case->id, $actor);
        });
    }
}
