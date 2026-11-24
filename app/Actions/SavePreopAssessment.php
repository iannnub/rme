<?php

namespace App\Actions;

use App\Enums\AllergyStatus;
use App\Enums\ReadinessStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\VitalSignPhase;
use App\Models\CaseSignoff;
use App\Models\PreopAssessment;
use App\Models\SupportingExam;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use App\Support\AuditContext;
use App\Support\OptimisticLock;
use App\Support\PreopClinicalDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePreopAssessment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(SurgeryCase $case, array $data, User $actor): PreopAssessment
    {
        OptimisticLock::assertFresh(
            $case->preopAssessment()->first(),
            $data['expected_updated_at'] ?? null,
        );

        $allergyStatus = $data['allergy_status'] ?? 'belum_diketahui';
        $allergyDetail = trim((string) ($data['allergy_detail'] ?? ''));
        if ($allergyStatus === AllergyStatus::Ada->value && $allergyDetail === '') {
            throw ValidationException::withMessages([
                'allergy_detail' => ['Detail alergi wajib diisi jika status alergi adalah Ada.'],
            ]);
        }

        $fastingStatus = (bool) ($data['fasting_status'] ?? false);
        $fastingSince = $data['fasting_since'] ?? null;
        if ($fastingStatus && (! is_string($fastingSince) || trim($fastingSince) === '')) {
            throw ValidationException::withMessages([
                'fasting_since' => ['Waktu mulai puasa wajib diisi jika status puasa aktif.'],
            ]);
        }

        $readinessStatus = $data['readiness_status'] ?? 'belum_siap';
        $preparation = is_array($data['preparation'] ?? null) ? $data['preparation'] : [];

        if ($readinessStatus === ReadinessStatus::Siap->value) {
            $requiredKeys = [
                'identity_verified',
                'informed_consent_surgery',
                'anesthesia_consent',
                'fasting_verified',
                'allergy_assessed',
                'lab_available',
                'iv_access',
                'operation_area_prepared',
                'jewelry_removed',
            ];

            $missing = [];
            foreach ($requiredKeys as $key) {
                if (($preparation[$key] ?? false) !== true) {
                    $missing[] = $key;
                }
            }

            if (array_key_exists('site_marked', $preparation) && $preparation['site_marked'] !== null) {
                if ($preparation['site_marked'] !== true) {
                    $missing[] = 'site_marked';
                }
            }

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'readiness_status' => ['Status kesiapan operasi hanya dapat dinyatakan "Siap" jika seluruh checklist persiapan wajib terpenuhi (tercentang).'],
                ]);
            }
        }

        return DB::transaction(function () use ($case, $data, $actor, $preparation): PreopAssessment {
            return AuditContext::run(function () use ($case, $data, $actor, $preparation): PreopAssessment {
                $assessmentData = [
                    'chief_complaint' => $data['chief_complaint'] ?? null,
                    'current_illness_history' => $data['current_illness_history'] ?? null,
                    'past_illness_history' => $data['past_illness_history'] ?? null,
                    'surgery_history' => $data['surgery_history'] ?? null,
                    'allergy_status' => $data['allergy_status'] ?? 'belum_diketahui',
                    'allergy_detail' => ($data['allergy_status'] ?? '') === AllergyStatus::Ada->value ? ($data['allergy_detail'] ?? null) : null,
                    'medication_history' => $data['medication_history'] ?? null,
                    'anesthesia_history' => $data['anesthesia_history'] ?? null,
                    'fasting_status' => (bool) ($data['fasting_status'] ?? false),
                    'fasting_since' => ! empty($data['fasting_since']) ? $data['fasting_since'] : null,
                    'skin_condition' => $data['skin_condition'] ?? null,
                    'anxiety_level' => $data['anxiety_level'] ?? 'tidak_cemas',
                    'surgery_knowledge' => $data['surgery_knowledge'] ?? null,
                    'family_support' => $data['family_support'] ?? null,
                    'mental_readiness' => $data['mental_readiness'] ?? null,
                    'preparation' => $preparation,
                    'implementation_actions' => $data['implementation_actions'] ?? null,
                    'implementation_education' => $data['implementation_education'] ?? null,
                    'implementation_monitoring' => $data['implementation_monitoring'] ?? null,
                    'implementation_response' => $data['implementation_response'] ?? null,
                    'evaluation_condition' => $data['evaluation_condition'] ?? null,
                    'evaluation_completeness' => $data['evaluation_completeness'] ?? null,
                    'readiness_status' => $data['readiness_status'] ?? 'belum_siap',
                    'evaluation_notes' => $data['evaluation_notes'] ?? null,
                    'clinical_details' => array_key_exists('clinical_details', $data)
                        ? PreopClinicalDefaults::merge(is_array($data['clinical_details']) ? $data['clinical_details'] : null)
                        : ($case->preopAssessment?->clinical_details),
                ];

                $assessment = $case->preopAssessment;
                if ($assessment !== null) {
                    $assessment->update($assessmentData);
                } else {
                    $assessment = PreopAssessment::query()->create([
                        ...$assessmentData,
                        'case_id' => $case->id,
                        'created_by' => $actor->id,
                    ]);
                }

                if (isset($data['vital_signs']) && is_array($data['vital_signs'])) {
                    $vsData = $data['vital_signs'];
                    $recordedAt = ! empty($vsData['recorded_at']) ? $vsData['recorded_at'] : now();

                    $vitalSign = $case->vitalSigns()->where('phase', VitalSignPhase::Pre)->latest('id')->first();
                    $vitalSignPayload = [
                        'phase' => VitalSignPhase::Pre,
                        'recorded_at' => $recordedAt,
                        'bp_systolic' => isset($vsData['bp_systolic']) && is_numeric($vsData['bp_systolic']) ? (int) $vsData['bp_systolic'] : null,
                        'bp_diastolic' => isset($vsData['bp_diastolic']) && is_numeric($vsData['bp_diastolic']) ? (int) $vsData['bp_diastolic'] : null,
                        'pulse' => isset($vsData['pulse']) && is_numeric($vsData['pulse']) ? (int) $vsData['pulse'] : null,
                        'temperature' => isset($vsData['temperature']) && is_numeric($vsData['temperature']) ? (float) $vsData['temperature'] : null,
                        'respiratory_rate' => isset($vsData['respiratory_rate']) && is_numeric($vsData['respiratory_rate']) ? (int) $vsData['respiratory_rate'] : null,
                        'spo2' => isset($vsData['spo2']) && is_numeric($vsData['spo2']) ? (int) $vsData['spo2'] : null,
                        'weight_kg' => isset($vsData['weight_kg']) && is_numeric($vsData['weight_kg']) ? (float) $vsData['weight_kg'] : null,
                        'height_cm' => isset($vsData['height_cm']) && is_numeric($vsData['height_cm']) ? (float) $vsData['height_cm'] : null,
                        'consciousness' => $vsData['consciousness'] ?? 'compos_mentis',
                        'notes' => $vsData['notes'] ?? null,
                    ];

                    if ($vitalSign !== null) {
                        $vitalSign->update($vitalSignPayload);
                    } else {
                        VitalSign::query()->create([
                            ...$vitalSignPayload,
                            'case_id' => $case->id,
                            'created_by' => $actor->id,
                        ]);
                    }
                }

                if (isset($data['supporting_exams']) && is_array($data['supporting_exams'])) {
                    $existingIds = [];
                    foreach ($data['supporting_exams'] as $exam) {
                        if (empty($exam['exam_type']) || empty($exam['result'])) {
                            continue;
                        }

                        $examId = isset($exam['id']) && is_numeric($exam['id']) ? (int) $exam['id'] : null;
                        $examRecord = $examId !== null ? SupportingExam::query()->where('case_id', $case->id)->find($examId) : null;

                        $payload = [
                            'exam_type' => $exam['exam_type'],
                            'result' => trim((string) $exam['result']),
                            'exam_date' => ! empty($exam['exam_date']) ? $exam['exam_date'] : null,
                        ];

                        if ($examRecord !== null) {
                            $examRecord->update($payload);
                            $existingIds[] = $examRecord->id;
                        } else {
                            $created = SupportingExam::query()->create([
                                ...$payload,
                                'case_id' => $case->id,
                                'created_by' => $actor->id,
                            ]);
                            $existingIds[] = $created->id;
                        }
                    }

                    if ($existingIds !== []) {
                        SupportingExam::query()->where('case_id', $case->id)->whereNotIn('id', $existingIds)->delete();
                    }
                }

                $casePlanUpdates = [];
                if (array_key_exists('medical_diagnosis_id', $data)) {
                    $casePlanUpdates['medical_diagnosis_id'] = is_numeric($data['medical_diagnosis_id']) ? (int) $data['medical_diagnosis_id'] : null;
                }
                if (array_key_exists('medical_diagnosis_other', $data)) {
                    $casePlanUpdates['medical_diagnosis_other'] = $data['medical_diagnosis_other'];
                }
                if (array_key_exists('surgery_type_id', $data)) {
                    $casePlanUpdates['surgery_type_id'] = is_numeric($data['surgery_type_id']) ? (int) $data['surgery_type_id'] : null;
                }
                if (array_key_exists('surgery_type_other', $data)) {
                    $casePlanUpdates['surgery_type_other'] = $data['surgery_type_other'];
                }
                if (array_key_exists('surgery_site', $data)) {
                    $casePlanUpdates['surgery_site'] = $data['surgery_site'];
                }
                if (array_key_exists('anesthesia_type_id', $data)) {
                    $casePlanUpdates['anesthesia_type_id'] = is_numeric($data['anesthesia_type_id']) ? (int) $data['anesthesia_type_id'] : null;
                }
                if (array_key_exists('surgeon_id', $data)) {
                    $casePlanUpdates['surgeon_id'] = is_numeric($data['surgeon_id']) ? (int) $data['surgeon_id'] : null;
                }
                if (array_key_exists('anesthesiologist_id', $data)) {
                    $casePlanUpdates['anesthesiologist_id'] = is_numeric($data['anesthesiologist_id']) ? (int) $data['anesthesiologist_id'] : null;
                }

                if ($casePlanUpdates !== []) {
                    $case->update($casePlanUpdates);
                }

                // Reset verifikasi dokter jika data pra-operasi diperbarui
                $activeDoctorVerifications = CaseSignoff::query()
                    ->where('case_id', $case->id)
                    ->where('section', SignoffSection::PreOp)
                    ->where('kind', SignoffKind::Verification)
                    ->whereNull('revoked_at')
                    ->get();

                foreach ($activeDoctorVerifications as $verification) {
                    $verification->update([
                        'revoked_at' => now(),
                        'revoke_reason' => 'Data pre-operasi diperbarui setelah verifikasi dokter.',
                    ]);
                }

                return $assessment->fresh() ?? $assessment;
            }, null, $case->id, $actor);
        });
    }
}
