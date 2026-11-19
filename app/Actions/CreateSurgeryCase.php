<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\Patient;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;

class CreateSurgeryCase
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): SurgeryCase
    {
        return DB::transaction(function () use ($data, $actor): SurgeryCase {
            return AuditContext::run(function () use ($data, $actor): SurgeryCase {
                if (($data['patient_mode'] ?? 'existing') === 'new') {
                    $patient = Patient::query()->create([
                        'rm_number' => $data['rm_number'],
                        'name' => $data['patient_name'],
                        'birth_date' => $data['birth_date'],
                        'gender' => $data['gender'],
                        'address' => $data['address'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'created_by' => $actor->id,
                    ]);
                } else {
                    $patient = Patient::query()->findOrFail($data['patient_id']);
                }

                $nowWib = now('Asia/Jakarta');
                $prefix = 'OK-'.$nowWib->format('Ymd').'-';

                $latest = SurgeryCase::withTrashed()
                    ->where('case_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderByDesc('case_number')
                    ->value('case_number');

                $nextNumber = $latest ? ((int) substr((string) $latest, strlen($prefix))) + 1 : 1;
                $caseNumber = $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

                $case = new SurgeryCase([
                    'patient_id' => $patient->id,
                    'case_number' => $caseNumber,
                    'admitted_at' => $data['admitted_at'],
                    'origin_ward' => $data['origin_ward'],
                    'origin_class' => $data['origin_class'] ?? null,
                    'scheduled_at' => $data['scheduled_at'],
                    'medical_diagnosis_id' => $data['medical_diagnosis_id'] ?? null,
                    'medical_diagnosis_other' => $data['medical_diagnosis_other'] ?? null,
                    'surgery_type_id' => $data['surgery_type_id'] ?? null,
                    'surgery_type_other' => $data['surgery_type_other'] ?? null,
                    'surgery_site' => $data['surgery_site'] ?? null,
                    'anesthesia_type_id' => $data['anesthesia_type_id'] ?? null,
                    'created_by' => $actor->id,
                ]);

                $case->forceFill(['status' => CaseStatus::PreOp]);
                $case->save();

                return $case;
            }, null, null, $actor);
        });
    }
}
