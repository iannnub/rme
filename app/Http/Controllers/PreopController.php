<?php

namespace App\Http\Controllers;

use App\Actions\ManageCaseNursingDiagnosis;
use App\Actions\SavePreopAssessment;
use App\Actions\SignPreopCase;
use App\Actions\VerifyPreopCase;
use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\ExamType;
use App\Enums\NursingPhase;
use App\Enums\ReadinessStatus;
use App\Enums\Role;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\VitalSignPhase;
use App\Http\Requests\Preop\AddCaseNursingDiagnosisRequest;
use App\Http\Requests\Preop\SavePreopAssessmentRequest;
use App\Http\Requests\Preop\SignPreopRequest;
use App\Http\Requests\Preop\VerifyPreopRequest;
use App\Models\AnesthesiaType;
use App\Models\CaseInterventionItem;
use App\Models\CaseNursingDiagnosis;
use App\Models\CaseSignoff;
use App\Models\MedicalDiagnosis;
use App\Models\NursingDiagnosis;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use App\Support\OptimisticLock;
use App\Support\PreopClinicalDefaults;
use App\Support\SurgeryCasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreopController extends Controller
{
    public function __construct(
        private readonly SurgeryCasePresenter $presenter,
    ) {}

    public function show(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('view', $surgeryCase);

        $surgeryCase->loadMissing([
            'patient',
            'medicalDiagnosis',
            'surgeryType',
            'anesthesiaType',
            'room',
            'surgeon',
            'anesthesiologist',
            'signoffs.user',
            'preopAssessment.creator',
            'vitalSigns',
            'supportingExams',
            'caseNursingDiagnoses.nursingDiagnosis.slkiCriteria',
            'caseNursingDiagnoses.nursingDiagnosis.sikiInterventions.items',
            'caseNursingDiagnoses.interventionItems.sikiItem',
        ]);

        $assessment = $surgeryCase->preopAssessment;
        $vitalSign = $surgeryCase->vitalSigns->firstWhere('phase', VitalSignPhase::Pre);

        $signoffs = $surgeryCase->signoffs->filter(fn (CaseSignoff $s): bool => $s->section === SignoffSection::PreOp);

        $nurseSignoff = $signoffs->first(fn (CaseSignoff $s): bool => $s->kind === SignoffKind::Signoff && $s->revoked_at === null);
        $doctorVerification = $signoffs->first(fn (CaseSignoff $s): bool => $s->kind === SignoffKind::Verification && $s->revoked_at === null);
        $revokedVerifications = $signoffs->filter(fn (CaseSignoff $s): bool => $s->kind === SignoffKind::Verification && $s->revoked_at !== null)
            ->map(fn (CaseSignoff $s): array => [
                'id' => $s->id,
                'user_name' => $s->user !== null ? $s->user->name : 'Dokter',
                'signed_at' => $s->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'revoked_at' => $s->revoked_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'revoke_reason' => $s->revoke_reason,
            ])->values()->all();

        $user = $request->user();

        $defaultAssessment = [
            'id' => null,
            'updated_at' => null,
            'chief_complaint' => '',
            'current_illness_history' => '',
            'past_illness_history' => '',
            'surgery_history' => '',
            'allergy_status' => AllergyStatus::BelumDiketahui->value,
            'allergy_detail' => '',
            'medication_history' => '',
            'anesthesia_history' => '',
            'fasting_status' => false,
            'fasting_since' => '',
            'skin_condition' => '',
            'anxiety_level' => AnxietyLevel::TidakCemas->value,
            'surgery_knowledge' => '',
            'family_support' => '',
            'mental_readiness' => '',
            'preparation' => [
                'identity_verified' => false,
                'informed_consent_surgery' => false,
                'anesthesia_consent' => false,
                'fasting_verified' => false,
                'allergy_assessed' => false,
                'lab_available' => false,
                'iv_access' => false,
                'operation_area_prepared' => false,
                'jewelry_removed' => false,
                'site_marked' => null,
            ],
            'implementation_actions' => '',
            'implementation_education' => '',
            'implementation_monitoring' => '',
            'implementation_response' => '',
            'evaluation_condition' => '',
            'evaluation_completeness' => '',
            'readiness_status' => ReadinessStatus::BelumSiap->value,
            'evaluation_notes' => '',
            'clinical_details' => PreopClinicalDefaults::merge(null),
        ];

        $assessmentData = $assessment === null ? $defaultAssessment : [
            'id' => $assessment->id,
            'updated_at' => OptimisticLock::token($assessment),
            'chief_complaint' => $assessment->chief_complaint ?? '',
            'current_illness_history' => $assessment->current_illness_history ?? '',
            'past_illness_history' => $assessment->past_illness_history ?? '',
            'surgery_history' => $assessment->surgery_history ?? '',
            'allergy_status' => $assessment->allergy_status->value,
            'allergy_detail' => $assessment->allergy_detail ?? '',
            'medication_history' => $assessment->medication_history ?? '',
            'anesthesia_history' => $assessment->anesthesia_history ?? '',
            'fasting_status' => $assessment->fasting_status,
            'fasting_since' => $assessment->fasting_since?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
            'skin_condition' => $assessment->skin_condition ?? '',
            'anxiety_level' => $assessment->anxiety_level->value,
            'surgery_knowledge' => $assessment->surgery_knowledge ?? '',
            'family_support' => $assessment->family_support ?? '',
            'mental_readiness' => $assessment->mental_readiness ?? '',
            'preparation' => [
                'identity_verified' => $assessment->preparation['identity_verified'] ?? false,
                'informed_consent_surgery' => $assessment->preparation['informed_consent_surgery'] ?? false,
                'anesthesia_consent' => $assessment->preparation['anesthesia_consent'] ?? false,
                'fasting_verified' => $assessment->preparation['fasting_verified'] ?? false,
                'allergy_assessed' => $assessment->preparation['allergy_assessed'] ?? false,
                'lab_available' => $assessment->preparation['lab_available'] ?? false,
                'iv_access' => $assessment->preparation['iv_access'] ?? false,
                'operation_area_prepared' => $assessment->preparation['operation_area_prepared'] ?? false,
                'jewelry_removed' => $assessment->preparation['jewelry_removed'] ?? false,
                'site_marked' => $assessment->preparation['site_marked'] ?? null,
            ],
            'implementation_actions' => $assessment->implementation_actions ?? '',
            'implementation_education' => $assessment->implementation_education ?? '',
            'implementation_monitoring' => $assessment->implementation_monitoring ?? '',
            'implementation_response' => $assessment->implementation_response ?? '',
            'evaluation_condition' => $assessment->evaluation_condition ?? '',
            'evaluation_completeness' => $assessment->evaluation_completeness ?? '',
            'readiness_status' => $assessment->readiness_status->value,
            'evaluation_notes' => $assessment->evaluation_notes ?? '',
            'clinical_details' => PreopClinicalDefaults::merge($assessment->clinical_details),
        ];

        $defaultVitalSign = [
            'id' => null,
            'recorded_at' => now('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'bp_systolic' => '',
            'bp_diastolic' => '',
            'pulse' => '',
            'temperature' => '',
            'respiratory_rate' => '',
            'spo2' => '',
            'weight_kg' => '',
            'height_cm' => '',
            'consciousness' => Consciousness::ComposMentis->value,
            'notes' => '',
        ];

        $vitalSignData = $vitalSign === null ? $defaultVitalSign : [
            'id' => $vitalSign->id,
            'recorded_at' => $vitalSign->recorded_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'bp_systolic' => $vitalSign->bp_systolic ?? '',
            'bp_diastolic' => $vitalSign->bp_diastolic ?? '',
            'pulse' => $vitalSign->pulse ?? '',
            'temperature' => $vitalSign->temperature ?? '',
            'respiratory_rate' => $vitalSign->respiratory_rate ?? '',
            'spo2' => $vitalSign->spo2 ?? '',
            'weight_kg' => $vitalSign->weight_kg ?? '',
            'height_cm' => $vitalSign->height_cm ?? '',
            'consciousness' => $vitalSign->consciousness->value,
            'notes' => $vitalSign->notes ?? '',
        ];

        return Inertia::render('cases/pre-op', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'casePlan' => [
                'medical_diagnosis_id' => $surgeryCase->medical_diagnosis_id,
                'medical_diagnosis_other' => $surgeryCase->medical_diagnosis_other,
                'surgery_type_id' => $surgeryCase->surgery_type_id,
                'surgery_type_other' => $surgeryCase->surgery_type_other,
                'surgery_site' => $surgeryCase->surgery_site,
                'anesthesia_type_id' => $surgeryCase->anesthesia_type_id,
                'surgeon_id' => $surgeryCase->surgeon_id,
                'anesthesiologist_id' => $surgeryCase->anesthesiologist_id,
            ],
            'assessment' => $assessmentData,
            'vitalSigns' => $vitalSignData,
            'supportingExams' => $surgeryCase->supportingExams->map(fn ($exam): array => [
                'id' => $exam->id,
                'exam_type' => $exam->exam_type->value,
                'result' => $exam->result,
                'exam_date' => $exam->exam_date?->format('Y-m-d') ?? '',
            ])->values()->all(),
            'caseNursingDiagnoses' => $surgeryCase->caseNursingDiagnoses
                ->where('phase', NursingPhase::Pre)
                ->map(fn (CaseNursingDiagnosis $cd): array => [
                    'id' => $cd->id,
                    'name' => $cd->displayName(),
                    'code' => $cd->nursingDiagnosis?->code,
                    'custom_text' => $cd->custom_text,
                    'notes' => $cd->notes,
                    'criteria' => $cd->nursingDiagnosis?->slkiCriteria->map(fn ($c): array => [
                        'id' => $c->id,
                        'outcome' => "{$c->outcome_name} ({$c->outcome_code})",
                        'text' => $c->criterion_text,
                        'target' => $c->target_score,
                    ])->values()->all() ?? [],
                    'interventions' => $cd->nursingDiagnosis?->sikiInterventions->map(fn ($int): array => [
                        'id' => $int->id,
                        'name' => "{$int->name} ({$int->code})",
                        'items' => $int->items->map(function ($item) use ($cd): array {
                            $ciItem = $cd->interventionItems->firstWhere('siki_item_id', $item->id);

                            return [
                                'id' => $ciItem instanceof CaseInterventionItem ? $ciItem->id : null,
                                'siki_item_id' => $item->id,
                                'section' => $item->section->value,
                                'section_label' => $item->section->label(),
                                'text' => $item->text,
                                'is_done' => $ciItem instanceof CaseInterventionItem ? $ciItem->is_done : false,
                                'done_at' => $ciItem instanceof CaseInterventionItem && $ciItem->done_at !== null
                                    ? $ciItem->done_at->timezone('Asia/Jakarta')->format('d/m H:i')
                                    : null,
                            ];
                        })->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            'signoffState' => [
                'nurseSignoff' => $nurseSignoff ? [
                    'id' => $nurseSignoff->id,
                    'user_name' => $nurseSignoff->user !== null ? $nurseSignoff->user->name : 'Perawat',
                    'nip' => $nurseSignoff->nip_snapshot,
                    'signed_at' => $nurseSignoff->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                ] : null,
                'doctorVerification' => $doctorVerification ? [
                    'id' => $doctorVerification->id,
                    'user_name' => $doctorVerification->user !== null ? $doctorVerification->user->name : 'Dokter',
                    'sip' => $doctorVerification->sip_snapshot,
                    'signed_at' => $doctorVerification->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                ] : null,
                'revokedVerifications' => $revokedVerifications,
            ],
            'options' => [
                'medicalDiagnoses' => MedicalDiagnosis::query()->active()->orderBy('name')->get(['id', 'name']),
                'surgeryTypes' => SurgeryType::query()->active()->orderBy('name')->get(['id', 'code', 'name', 'default_medical_diagnosis_id', 'default_procedure_name']),
                'anesthesiaTypes' => AnesthesiaType::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
                'surgeons' => User::query()->where('role', Role::DokterOperator)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'anesthesiologists' => User::query()->where('role', Role::DokterAnestesi)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'masterNursingDiagnoses' => NursingDiagnosis::query()->active()->where('phase', NursingPhase::Pre->value)->orderBy('code')->get(['id', 'code', 'name']),
                'examTypes' => array_map(fn (ExamType $t): array => ['value' => $t->value, 'label' => $t->label()], ExamType::cases()),
                'allergyStatuses' => array_map(fn (AllergyStatus $s): array => ['value' => $s->value, 'label' => $s->label()], AllergyStatus::cases()),
                'anxietyLevels' => array_map(fn (AnxietyLevel $a): array => ['value' => $a->value, 'label' => $a->label()], AnxietyLevel::cases()),
                'readinessStatuses' => array_map(fn (ReadinessStatus $r): array => ['value' => $r->value, 'label' => $r->label()], ReadinessStatus::cases()),
                'consciousnessList' => array_map(fn (Consciousness $c): array => ['value' => $c->value, 'label' => $c->label()], Consciousness::cases()),
            ],
            'canManage' => ($user?->isPerawat() ?? false) && $surgeryCase->status !== CaseStatus::Selesai,
            'canSign' => ($user?->isPerawat() ?? false) && $surgeryCase->status !== CaseStatus::Selesai,
            'canVerify' => ($user?->isDokter() ?? false) && $surgeryCase->status !== CaseStatus::Selesai,
        ]);
    }

    public function update(
        SavePreopAssessmentRequest $request,
        SurgeryCase $surgeryCase,
        SavePreopAssessment $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Asesmen pra-operasi berhasil disimpan.');
    }

    public function sign(
        SignPreopRequest $request,
        SurgeryCase $surgeryCase,
        SignPreopCase $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $user);

        return back()->with('success', 'Pengesahan pra-operasi oleh perawat berhasil disimpan.');
    }

    public function verify(
        VerifyPreopRequest $request,
        SurgeryCase $surgeryCase,
        VerifyPreopCase $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $user);

        return back()->with('success', 'Verifikasi pra-operasi oleh dokter berhasil disimpan.');
    }

    public function addDiagnosis(
        AddCaseNursingDiagnosisRequest $request,
        SurgeryCase $surgeryCase,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->addDiagnosis($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Diagnosis keperawatan pra-operasi berhasil ditambahkan.');
    }

    public function removeDiagnosis(
        Request $request,
        SurgeryCase $surgeryCase,
        CaseNursingDiagnosis $caseNursingDiagnosis,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $this->authorize('view', $surgeryCase);
        $user = $request->user();
        abort_if($user === null || ! $user->isPerawat(), 403);
        abort_if($caseNursingDiagnosis->case_id !== $surgeryCase->id || $caseNursingDiagnosis->phase !== NursingPhase::Pre, 404);

        $action->removeDiagnosis($caseNursingDiagnosis, $user);

        return back()->with('success', 'Diagnosis keperawatan berhasil dihapus.');
    }

    public function toggleIntervention(
        Request $request,
        SurgeryCase $surgeryCase,
        CaseInterventionItem $caseInterventionItem,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $this->authorize('view', $surgeryCase);
        $user = $request->user();
        abort_if($user === null || ! $user->isPerawat(), 403);

        $caseDiagnosis = $caseInterventionItem->caseNursingDiagnosis;
        abort_if($caseDiagnosis === null || $caseDiagnosis->case_id !== $surgeryCase->id || $caseDiagnosis->phase !== NursingPhase::Pre, 404);

        $action->toggleIntervention($caseInterventionItem, $user);

        return back()->with('success', 'Status intervensi keperawatan diperbarui.');
    }
}
