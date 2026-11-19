<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Services\CaseTransitionChecker;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvanceCaseStatus
{
    public function __construct(
        private readonly CaseTransitionChecker $checker,
    ) {}

    public function execute(SurgeryCase $case, CaseStatus $target, User $actor, ?string $reason = null): SurgeryCase
    {
        $missing = $this->checker->missingRequirements($case, $target);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'transition' => $missing,
            ]);
        }

        return DB::transaction(function () use ($case, $target, $actor, $reason): SurgeryCase {
            return AuditContext::run(function () use ($case, $target): SurgeryCase {
                $updates = ['status' => $target];

                if ($target === CaseStatus::SiapOperasi && $case->ready_at === null) {
                    $updates['ready_at'] = now();
                }

                if ($target === CaseStatus::Selesai && $case->completed_at === null) {
                    $updates['completed_at'] = now();
                }

                $case->forceFill($updates)->save();

                return $case->fresh() ?? $case;
            }, $reason, $case->id, $actor);
        });
    }
}
