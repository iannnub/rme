<?php

namespace App\Http\Controllers;

use App\Actions\CorrectPostopByDoctor;
use App\Actions\ManageCaseNursingDiagnosis;
use App\Actions\SavePostopSummary;
use App\Actions\StorePostopObservation;
use App\Actions\VerifyPostopCase;
use App\Enums\AirwayPatency;
use App\Enums\AnalgesicResponse;
use App\Enums\BreathingPattern;
use App\Enums\Consciousness;
use App\Enums\DressingCondition;
use App\Enums\FluidColor;
use App\Enums\FluidOdor;
use App\Enums\GeneralCondition;
use App\Enums\NursingPhase;
use App\Enums\OxygenDevice;
use App\Enums\PainCharacter;
use App\Enums\PatientResponse;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SutureCondition;
use App\Enums\VitalSignPhase;
use App\Http\Requests\Postop\CorrectPostopByDoctorRequest;
use App\Http\Requests\Postop\SavePostopSummaryRequest;
use App\Http\Requests\Postop\StorePostopObservationRequest;
use App\Http\Requests\Postop\VerifyPostopRequest;
use App\Http\Requests\Preop\AddCaseNursingDiagnosisRequest;
use App\Models\CaseInterventionItem;
use App\Models\CaseNursingDiagnosis;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\NursingDiagnosis;
use App\Models\PostopObservation;
use App\Models\SurgeryCase;
use App\Models\VitalSign;
use App\Support\PreopClinicalDefaults;
use App\Support\SurgeryCasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostopController extends Controller
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
            'postopObservations.creator',
            'postopSummary.assessor',
            'vitalSigns',
            'fluidRecords',
            'signoffs.user',
            'caseNursingDiagnoses.nursingDiagnosis.slkiCriteria',
            'caseNursingDiagnoses.nursingDiagnosis.sikiInterventions.items',
            'caseNursingDiagnoses.interventionItems.sikiItem',
        ]);

        $postVitals = $surgeryCase->vitalSigns
            ->where('phase', VitalSignPhase::Post)
            ->sortBy('recorded_at')
            ->values();

        $postFluids = $surgeryCase->fluidRecords
            ->where('phase', VitalSignPhase::Post)
            ->sortBy('recorded_at')
            ->values();

        $observations = $surgeryCase->postopObservations
            ->sortBy('recorded_at')
            ->values();

        $summary = $surgeryCase->postopSummary;

        $postSignoffs = $surgeryCase->signoffs
            ->filter(fn (CaseSignoff $s): bool => $s->section === SignoffSection::PostOp && $s->kind === SignoffKind::Verification);

        $activeVerifications = $postSignoffs
            ->filter(fn (CaseSignoff $s): bool => $s->revoked_at === null)
            ->map(fn (CaseSignoff $s): array => [
                'id' => $s->id,
                'user_name' => $s->user !== null ? $s->user->name : 'Dokter',
                'sip' => $s->sip_snapshot,
                'signed_at' => $s->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ])->values()->all();

        $revokedVerifications = $postSignoffs
            ->filter(fn (CaseSignoff $s): bool => $s->revoked_at !== null)
            ->map(fn (CaseSignoff $s): array => [
                'id' => $s->id,
                'user_name' => $s->user !== null ? $s->user->name : 'Dokter',
                'signed_at' => $s->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'revoked_at' => $s->revoked_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'revoke_reason' => $s->revoke_reason,
            ])->values()->all();

        $user = $request->user();

        return Inertia::render('cases/post-op', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'operationPlanActual' => $surgeryCase->operation_plan_actual ?? '',
            'observations' => $observations->map(fn (PostopObservation $o): array => [
                'id' => $o->id,
                'recorded_at' => $o->recorded_at->timezone('Asia/Jakarta')->format('H:i'),
                'recorded_at_full' => $o->recorded_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'gcs_total' => $o->gcsTotal(),
                'gcs_detail' => "E{$o->gcs_eye} V{$o->gcs_verbal} M{$o->gcs_motor}",
                'general_condition' => $o->general_condition->label(),
                'response' => $o->response->label(),
                'airway_patency' => $o->airway_patency->label(),
                'wound_summary' => implode(' • ', array_filter([
                    $o->wound_location,
                    $o->dressing?->label(),
                    $o->bleeding_seepage ? 'Ada rembesan' : null,
                    $o->swelling_hematoma ? 'Ada hematoma' : null,
                    $o->suture_condition?->label(),
                ])) ?: '-',
                'pain_nrs' => $o->pain_nrs,
                'pain_detail' => implode(' • ', array_filter([
                    $o->pain_location,
                    $o->pain_character?->label(),
                    $o->pain_duration,
                    $o->analgesic_response?->label(),
                ])) ?: '-',
                'anesthesia_recovery' => $o->anesthesia_recovery?->label() ?? '-',
                'nausea_vomiting' => $o->nausea_vomiting,
                'shivering_hypothermia' => $o->shivering_hypothermia,
                'complications' => $o->complications ?? [],
                'notes' => $o->notes,
                'clinical_details' => PreopClinicalDefaults::mergePostopClinical($o->clinical_details),
                'creator_name' => $o->creator !== null ? $o->creator->name : '-',
            ])->values()->all(),
            'vitalSigns' => $postVitals->map(fn (VitalSign $v): array => [
                'id' => $v->id,
                'recorded_at' => $v->recorded_at->timezone('Asia/Jakarta')->format('H:i'),
                'recorded_at_full' => $v->recorded_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'bp_systolic' => $v->bp_systolic,
                'bp_diastolic' => $v->bp_diastolic,
                'pulse' => $v->pulse,
                'temperature' => $v->temperature,
                'respiratory_rate' => $v->respiratory_rate,
                'spo2' => $v->spo2,
                'breathing_pattern' => $v->breathing_pattern?->label() ?? '-',
                'oxygen_device' => $v->oxygen_device?->label() ?? '-',
                'oxygen_flow_lpm' => $v->oxygen_flow_lpm,
            ])->values()->all(),
            'postFluids' => $postFluids->map(fn (FluidRecord $f): array => [
                'id' => $f->id,
                'recorded_at' => $f->recorded_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-',
                'kind_label' => $f->kind->label(),
                'volume_ml' => $f->volume_ml,
                'color' => $f->color?->label(),
                'odor' => $f->odor?->label(),
            ])->values()->all(),
            'summary' => $summary === null ? [
                'ready_to_transfer' => false,
                'pre_transfer_condition_notes' => '',
                'followup_plan' => PreopClinicalDefaults::mergePostopFollowup(null),
                'assessed_at' => now('Asia/Jakarta')->format('Y-m-d\TH:i'),
                'assessed_by_name' => null,
            ] : [
                'ready_to_transfer' => $summary->ready_to_transfer,
                'pre_transfer_condition_notes' => $summary->pre_transfer_condition_notes ?? '',
                'followup_plan' => PreopClinicalDefaults::mergePostopFollowup($summary->followup_plan),
                'assessed_at' => $summary->assessed_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? now('Asia/Jakarta')->format('Y-m-d\TH:i'),
                'assessed_by_name' => $summary->assessor !== null ? $summary->assessor->name : null,
            ],
            'caseNursingDiagnoses' => $surgeryCase->caseNursingDiagnoses
                ->where('phase', NursingPhase::Post)
                ->map(fn (CaseNursingDiagnosis $cd): array => [
                    'id' => $cd->id,
                    'name' => $cd->displayName(),
                    'code' => $cd->nursingDiagnosis?->code,
                    'slki' => $cd->nursingDiagnosis !== null ? $cd->nursingDiagnosis->slkiCriteria->map(fn ($c): array => [
                        'id' => $c->id,
                        'outcome_name' => $c->outcome_name,
                        'criterion_text' => $c->criterion_text,
                        'expectation' => $c->expectation->label(),
                        'target_score' => $c->target_score,
                    ])->values()->all() : [],
                    'siki' => $cd->nursingDiagnosis !== null ? $cd->nursingDiagnosis->sikiInterventions->map(fn ($int): array => [
                        'id' => $int->id,
                        'name' => "{$int->name} ({$int->code})",
                        'items' => $int->items->map(function ($item) use ($cd): array {
                            $ciItem = $cd->interventionItems->firstWhere('siki_item_id', $item->id);

                            return [
                                'id' => $ciItem instanceof CaseInterventionItem ? $ciItem->id : null,
                                'siki_item_id' => $item->id,
                                'section_label' => $item->section->label(),
                                'text' => $item->text,
                                'is_done' => $ciItem instanceof CaseInterventionItem ? $ciItem->is_done : false,
                                'done_at' => $ciItem instanceof CaseInterventionItem && $ciItem->done_at !== null
                                    ? $ciItem->done_at->timezone('Asia/Jakarta')->format('d/m H:i')
                                    : null,
                            ];
                        })->values()->all(),
                    ])->values()->all() : [],
                ])->values()->all(),
            'verificationState' => [
                'activeVerifications' => $activeVerifications,
                'revokedVerifications' => $revokedVerifications,
            ],
            'options' => [
                'generalConditions' => array_map(fn (GeneralCondition $e): array => ['value' => $e->value, 'label' => $e->label()], GeneralCondition::cases()),
                'patientResponses' => array_map(fn (PatientResponse $e): array => ['value' => $e->value, 'label' => $e->label()], PatientResponse::cases()),
                'airwayPatencies' => array_map(fn (AirwayPatency $e): array => ['value' => $e->value, 'label' => $e->label()], AirwayPatency::cases()),
                'breathingPatterns' => array_map(fn (BreathingPattern $e): array => ['value' => $e->value, 'label' => $e->label()], BreathingPattern::cases()),
                'oxygenDevices' => array_map(fn (OxygenDevice $e): array => ['value' => $e->value, 'label' => $e->label()], OxygenDevice::cases()),
                'dressingConditions' => array_map(fn (DressingCondition $e): array => ['value' => $e->value, 'label' => $e->label()], DressingCondition::cases()),
                'sutureConditions' => array_map(fn (SutureCondition $e): array => ['value' => $e->value, 'label' => $e->label()], SutureCondition::cases()),
                'painCharacters' => array_map(fn (PainCharacter $e): array => ['value' => $e->value, 'label' => $e->label()], PainCharacter::cases()),
                'analgesicResponses' => array_map(fn (AnalgesicResponse $e): array => ['value' => $e->value, 'label' => $e->label()], AnalgesicResponse::cases()),
                'consciousnessList' => array_map(fn (Consciousness $e): array => ['value' => $e->value, 'label' => $e->label()], Consciousness::cases()),
                'fluidColors' => array_map(fn (FluidColor $e): array => ['value' => $e->value, 'label' => $e->label()], FluidColor::cases()),
                'fluidOdors' => array_map(fn (FluidOdor $e): array => ['value' => $e->value, 'label' => $e->label()], FluidOdor::cases()),
                'masterNursingDiagnoses' => NursingDiagnosis::query()->active()->where('phase', NursingPhase::Post->value)->orderBy('id')->get(['id', 'code', 'name']),
            ],
            'permissions' => [
                'canEdit' => $user !== null && $user->can('updatePostop', $surgeryCase),
                'canVerify' => $user !== null && $user->can('verifyPostop', $surgeryCase),
                'canDoctorCorrect' => $user !== null && $user->can('correctMedical', $surgeryCase),
                'canAdvance' => $user !== null && $user->can('advanceStatus', $surgeryCase),
            ],
        ]);
    }

    public function storeObservation(
        StorePostopObservationRequest $request,
        SurgeryCase $surgeryCase,
        StorePostopObservation $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Observasi pasca-operasi berhasil dicatat.');
    }

    public function deleteObservation(
        Request $request,
        SurgeryCase $surgeryCase,
        PostopObservation $postopObservation,
        StorePostopObservation $action,
    ): RedirectResponse {
        $this->authorize('updatePostop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $action->delete($surgeryCase, $postopObservation, $user);

        return back()->with('success', 'Observasi pasca-operasi berhasil dihapus.');
    }

    public function saveSummary(
        SavePostopSummaryRequest $request,
        SurgeryCase $surgeryCase,
        SavePostopSummary $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Kondisi pasien sebelum dipindahkan berhasil disimpan.');
    }

    public function addDiagnosis(
        AddCaseNursingDiagnosisRequest $request,
        SurgeryCase $surgeryCase,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validated();
        $data['phase'] = NursingPhase::Post->value;

        $action->addDiagnosis($surgeryCase, $data, $user);

        return back()->with('success', 'Diagnosis keperawatan pasca-operasi berhasil ditambahkan.');
    }

    public function removeDiagnosis(
        Request $request,
        SurgeryCase $surgeryCase,
        CaseNursingDiagnosis $caseNursingDiagnosis,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $this->authorize('updatePostop', $surgeryCase);
        abort_if($caseNursingDiagnosis->case_id !== $surgeryCase->id || $caseNursingDiagnosis->phase !== NursingPhase::Post, 404);

        $user = $request->user();
        abort_if($user === null, 403);

        $action->removeDiagnosis($caseNursingDiagnosis, $user);

        return back()->with('success', 'Diagnosis keperawatan pasca-operasi berhasil dihapus.');
    }

    public function toggleIntervention(
        Request $request,
        SurgeryCase $surgeryCase,
        CaseInterventionItem $caseInterventionItem,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $this->authorize('updatePostop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $caseDiagnosis = $caseInterventionItem->caseNursingDiagnosis;
        abort_if($caseDiagnosis === null || $caseDiagnosis->case_id !== $surgeryCase->id || $caseDiagnosis->phase !== NursingPhase::Post, 404);

        $action->toggleIntervention($caseInterventionItem, $user);

        return back();
    }

    public function verify(
        VerifyPostopRequest $request,
        SurgeryCase $surgeryCase,
        VerifyPostopCase $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $user);

        return back()->with('success', 'Verifikasi pasca-operasi oleh dokter berhasil dicatat.');
    }

    public function doctorCorrection(
        CorrectPostopByDoctorRequest $request,
        SurgeryCase $surgeryCase,
        CorrectPostopByDoctor $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Koreksi medis oleh dokter berhasil disimpan dan tercatat di audit log.');
    }
}
