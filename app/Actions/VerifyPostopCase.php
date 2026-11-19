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

class VerifyPostopCase
{
    public function execute(SurgeryCase $case, User $actor): CaseSignoff
    {
        $isAssignedDoctor = ($actor->isDokterOperator() && $case->surgeon_id === $actor->id)
            || ($actor->isDokterAnestesi() && $case->anesthesiologist_id === $actor->id);

        if (! $isAssignedDoctor) {
            throw ValidationException::withMessages([
                'auth' => ['Verifikasi pasca-operasi hanya dapat dilakukan oleh dokter operator atau dokter anestesi yang ditetapkan pada kasus ini.'],
            ]);
        }

        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diverifikasi ulang; gunakan menu Koreksi Dokter jika ada perbaikan medis.'],
            ]);
        }

        return DB::transaction(function () use ($case, $actor): CaseSignoff {
            return AuditContext::run(function () use ($case, $actor): CaseSignoff {
                return CaseSignoff::query()->create([
                    'case_id' => $case->id,
                    'section' => SignoffSection::PostOp,
                    'kind' => SignoffKind::Verification,
                    'role_label' => $actor->role->value,
                    'user_id' => $actor->id,
                    'nip_snapshot' => $actor->nip,
                    'sip_snapshot' => $actor->sip,
                    'signed_at' => now(),
                ]);
            }, 'Verifikasi pasca-operasi oleh dokter', $case->id, $actor);
        });
    }
}
