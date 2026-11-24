<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\NursingPhase;
use App\Models\CaseInterventionItem;
use App\Models\CaseNursingDiagnosis;
use App\Models\NursingDiagnosis;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageCaseNursingDiagnosis
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function addDiagnosis(SurgeryCase $case, array $data, User $actor): CaseNursingDiagnosis
    {
        if ($case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat ditambahkan diagnosis keperawatan.'],
            ]);
        }

        $diagnosisId = isset($data['nursing_diagnosis_id']) && is_numeric($data['nursing_diagnosis_id'])
            ? (int) $data['nursing_diagnosis_id']
            : null;
        $customText = trim((string) ($data['custom_text'] ?? ''));
        $phase = $data['phase'] ?? NursingPhase::Pre->value;

        if ($diagnosisId === null && $customText === '') {
            throw ValidationException::withMessages([
                'nursing_diagnosis_id' => ['Pilih diagnosis keperawatan dari master atau tuliskan teks diagnosis.'],
            ]);
        }

        return DB::transaction(function () use ($case, $diagnosisId, $customText, $phase, $data, $actor): CaseNursingDiagnosis {
            return AuditContext::run(function () use ($case, $diagnosisId, $customText, $phase, $data, $actor): CaseNursingDiagnosis {
                $caseDiagnosis = CaseNursingDiagnosis::query()->create([
                    'case_id' => $case->id,
                    'nursing_diagnosis_id' => $diagnosisId,
                    'phase' => $phase,
                    'custom_text' => $customText !== '' ? $customText : null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $actor->id,
                ]);

                if ($diagnosisId !== null) {
                    $diagnosis = NursingDiagnosis::query()->with('sikiInterventions.items')->find($diagnosisId);
                    if ($diagnosis !== null) {
                        foreach ($diagnosis->sikiInterventions as $intervention) {
                            foreach ($intervention->items as $item) {
                                CaseInterventionItem::query()->create([
                                    'case_nursing_diagnosis_id' => $caseDiagnosis->id,
                                    'siki_item_id' => $item->id,
                                    'is_done' => false,
                                ]);
                            }
                        }
                    }
                }

                return $caseDiagnosis;
            }, 'Penambahan diagnosis keperawatan kasus', $case->id, $actor);
        });
    }

    public function removeDiagnosis(CaseNursingDiagnosis $caseDiagnosis, User $actor): void
    {
        $case = $caseDiagnosis->surgeryCase;
        if ($case !== null && $case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah diagnosis keperawatannya.'],
            ]);
        }

        AuditContext::run(function () use ($caseDiagnosis): void {
            $caseDiagnosis->delete();
        }, 'Penghapusan diagnosis keperawatan kasus', $case?->id, $actor);
    }

    public function toggleIntervention(CaseInterventionItem $item, User $actor): CaseInterventionItem
    {
        $caseDiagnosis = $item->caseNursingDiagnosis;
        $case = $caseDiagnosis?->surgeryCase;

        if ($case !== null && $case->status === CaseStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => ['Kasus yang sudah selesai tidak dapat diubah intervensinya.'],
            ]);
        }

        $newDone = ! $item->is_done;

        AuditContext::run(function () use ($item, $newDone, $actor): void {
            $item->update([
                'is_done' => $newDone,
                'done_at' => $newDone ? now() : null,
                'done_by' => $newDone ? $actor->id : null,
            ]);
        }, 'Pembaruan status intervensi keperawatan SIKI', $case?->id, $actor);

        return $item;
    }
}
