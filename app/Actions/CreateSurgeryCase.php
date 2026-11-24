<?php

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\Patient;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
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
                        'identity_number' => $data['identity_number'] ?? null,
                        'address' => $data['address'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'guardian_name' => $data['guardian_name'] ?? null,
                        'guardian_relation' => $data['guardian_relation'] ?? null,
                        'guardian_phone' => $data['guardian_phone'] ?? null,
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

                $surgeryTypeId = $data['surgery_type_id'] ?? null;
                $medicalDiagnosisId = $data['medical_diagnosis_id'] ?? null;
                $medicalDiagnosisOther = $data['medical_diagnosis_other'] ?? null;

                if ($surgeryTypeId !== null && $medicalDiagnosisId === null && $medicalDiagnosisOther === null) {
                    $medicalDiagnosisId = SurgeryType::query()->find($surgeryTypeId)?->default_medical_diagnosis_id;
                }

                $case = new SurgeryCase([
                    'patient_id' => $patient->id,
                    'case_number' => $caseNumber,
                    'igd_visit_number' => $data['igd_visit_number'] ?? null,
                    'admitted_at' => $data['admitted_at'],
                    'igd_admitted_at' => $data['igd_admitted_at'] ?? null,
                    'origin_ward' => $data['origin_ward'],
                    'origin_class' => $data['origin_class'] ?? null,
                    'urgency_status' => $data['urgency_status'] ?? 'emergency',
                    'scheduled_at' => $data['scheduled_at'],
                    'medical_diagnosis_id' => $medicalDiagnosisId,
                    'medical_diagnosis_other' => $medicalDiagnosisOther,
                    'surgery_type_id' => $surgeryTypeId,
                    'surgery_type_other' => $data['surgery_type_other'] ?? null,
                    'surgery_site' => $data['surgery_site'] ?? null,
                    'anesthesia_type_id' => $data['anesthesia_type_id'] ?? null,
                    'dpjp_name' => $data['dpjp_name'] ?? null,
                    'created_by' => $actor->id,
                ]);

                $case->forceFill(['status' => CaseStatus::PreOp]);
                $case->save();

                return $case;
            }, null, null, $actor);
        });
    }
}
