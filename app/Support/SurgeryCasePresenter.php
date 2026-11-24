<?php

namespace App\Support;

use App\Enums\AllergyStatus;
use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use App\Services\CaseTransitionChecker;

class SurgeryCasePresenter
{
    public function __construct(
        private readonly CaseTransitionChecker $transitionChecker,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function header(SurgeryCase $case): array
    {
        $case->loadMissing([
            'patient',
            'medicalDiagnosis',
            'surgeryType',
            'anesthesiaType',
            'room',
            'surgeon',
            'anesthesiologist',
            'circulatingNurse',
            'scrubNurse',
            'anesthesiaNurse',
            'signoffs',
            'preopAssessment',
        ]);

        $patient = $case->patient;
        $nextStatus = $case->status->next();
        $missingNext = $nextStatus ? $this->transitionChecker->missingRequirements($case, $nextStatus) : [];

        $allergy = null;
        if ($case->preopAssessment !== null && $case->preopAssessment->allergy_status === AllergyStatus::Ada) {
            $allergy = $case->preopAssessment->allergy_detail ?: 'Ada Alergi';
        }

        $profile = $case->surgeryProfile();
        $urgencyLabel = match ($case->urgency_status) {
            'emergency' => 'Operasi Darurat (Emergency)',
            'elektif' => 'Operasi Terencana (Elektif)',
            default => null,
        };

        return [
            'id' => $case->id,
            'case_number' => $case->case_number,
            'igd_visit_number' => $case->igd_visit_number,
            'status' => $case->status->value,
            'status_label' => $case->status->label(),
            'status_order' => $case->status->order(),
            'admitted_at' => $case->admitted_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            'igd_admitted_at' => $case->igd_admitted_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
            'scheduled_at' => $case->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            'origin_ward' => $case->origin_ward,
            'origin_class' => $case->origin_class,
            'urgency_status' => $case->urgency_status,
            'urgency_label' => $urgencyLabel,
            'diagnosis' => $case->diagnosisDisplay(),
            'postop_diagnosis' => $case->postop_diagnosis,
            'surgery_type' => $case->surgeryTypeDisplay(),
            'surgery_profile' => $profile?->value,
            'surgery_profile_label' => $profile?->label(),
            'surgery_site' => $case->surgery_site ?? '-',
            'anesthesia_type' => $case->anesthesiaType !== null ? $case->anesthesiaType->name : '-',
            'dpjp_name' => $case->dpjp_name ?? $case->surgeon?->name,
            'allergy' => $allergy,
            'room' => $case->room ? [
                'id' => $case->room->id,
                'code' => $case->room->code,
                'name' => $case->room->name,
            ] : null,
            'patient' => [
                'id' => $patient?->id,
                'rm_number' => $patient !== null ? $patient->rm_number : '-',
                'name' => $patient !== null ? $patient->name : '-',
                'birth_date' => $patient !== null ? $patient->birth_date->format('d M Y') : '-',
                'age_label' => $patient !== null ? $patient->ageLabel() : '-',
                'gender' => $patient !== null ? $patient->gender->value : '-',
                'gender_label' => $patient !== null ? $patient->gender->label() : '-',
                'identity_number' => $patient?->identity_number,
                'guardian_name' => $patient?->guardian_name,
                'guardian_relation' => $patient?->guardian_relation,
                'guardian_phone' => $patient?->guardian_phone,
            ],
            'team' => [
                'surgeon' => $case->surgeon?->name,
                'anesthesiologist' => $case->anesthesiologist?->name,
                'circulating_nurse' => $case->circulatingNurse?->name,
                'scrub_nurse' => $case->scrubNurse?->name,
                'anesthesia_nurse' => $case->anesthesiaNurse?->name,
            ],
            'stages' => [
                [
                    'key' => 'pre-op',
                    'label' => 'Pre-op',
                    'href' => route('cases.preop', $case),
                    'completed' => $case->status->isAtLeast(CaseStatus::SiapOperasi),
                    'active' => $case->status === CaseStatus::PreOp,
                    'locked' => false,
                ],
                [
                    'key' => 'persiapan',
                    'label' => 'Persiapan & SSC',
                    'href' => route('cases.preparation', $case),
                    'completed' => $case->status->isAtLeast(CaseStatus::SiapOperasi),
                    'active' => $case->status === CaseStatus::PreOp || $case->status === CaseStatus::SiapOperasi,
                    'locked' => false,
                ],
                [
                    'key' => 'intra-op',
                    'label' => 'Intra-op',
                    'href' => route('cases.intraop', $case),
                    'completed' => $case->status->isAtLeast(CaseStatus::PascaOp),
                    'active' => $case->status === CaseStatus::IntraOp,
                    'locked' => ! $case->status->isAtLeast(CaseStatus::IntraOp),
                ],
                [
                    'key' => 'pasca-op',
                    'label' => 'Pasca-op',
                    'href' => route('cases.postop', $case),
                    'completed' => $case->status->isAtLeast(CaseStatus::SerahTerima),
                    'active' => $case->status === CaseStatus::PascaOp,
                    'locked' => ! $case->status->isAtLeast(CaseStatus::PascaOp),
                ],
                [
                    'key' => 'serah-terima',
                    'label' => 'Serah terima',
                    'href' => route('cases.handover', $case),
                    'completed' => $case->status === CaseStatus::Selesai,
                    'active' => $case->status === CaseStatus::SerahTerima,
                    'locked' => ! $case->status->isAtLeast(CaseStatus::SerahTerima),
                ],
            ],
            'next_transition' => $nextStatus ? [
                'target' => $nextStatus->value,
                'target_label' => $nextStatus->label(),
                'checklist' => $this->transitionChecker->stageChecklist($case),
                'can_advance' => $missingNext === [],
            ] : null,
        ];
    }
}
