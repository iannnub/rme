<?php

namespace App\Http\Controllers;

use App\Actions\ManageCaseNursingDiagnosis;
use App\Actions\ManageIntraopMonitoring;
use App\Actions\SaveInstrumentCounts;
use App\Actions\SaveIntraopChronology;
use App\Actions\SaveIntraopRecord;
use App\Actions\SignIntraopCase;
use App\Enums\BreathingPattern;
use App\Enums\Consciousness;
use App\Enums\CountVerification;
use App\Enums\FluidDirection;
use App\Enums\FluidKind;
use App\Enums\InstrumentCountItem;
use App\Enums\NursingPhase;
use App\Enums\OxygenDevice;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SkinIntegrity;
use App\Enums\SpecimenPreservative;
use App\Enums\SpecimenPurpose;
use App\Enums\SscStatus;
use App\Enums\SurgicalPosition;
use App\Enums\VitalSignPhase;
use App\Http\Requests\Intraop\SaveInstrumentCountsRequest;
use App\Http\Requests\Intraop\SaveIntraopChronologyRequest;
use App\Http\Requests\Intraop\SaveIntraopPositionRequest;
use App\Http\Requests\Intraop\SaveIntraopSpecimenComplicationRequest;
use App\Http\Requests\Intraop\SignIntraopRequest;
use App\Http\Requests\Intraop\StoreIntraopFluidRequest;
use App\Http\Requests\Intraop\StoreIntraopMedicationRequest;
use App\Http\Requests\Intraop\StoreIntraopNursingActionRequest;
use App\Http\Requests\Intraop\StoreIntraopVitalSignRequest;
use App\Http\Requests\Preop\AddCaseNursingDiagnosisRequest;
use App\Models\AnesthesiaType;
use App\Models\CaseInterventionItem;
use App\Models\CaseNursingDiagnosis;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\InstrumentCount;
use App\Models\Medication;
use App\Models\NursingAction;
use App\Models\NursingDiagnosis;
use App\Models\SurgeryCase;
use App\Models\VitalSign;
use App\Support\OptimisticLock;
use App\Support\SurgeryCasePresenter;
use BackedEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntraopController extends Controller
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
            'circulatingNurse',
            'scrubNurse',
            'anesthesiaNurse',
            'sscSignIn',
            'sscTimeOut',
            'sscSignOut',
            'intraopRecord',
            'vitalSigns',
            'medications.creator',
            'fluidRecords.creator',
            'nursingActions.creator',
            'instrumentCounts',
            'specimens',
            'signoffs.user',
            'caseNursingDiagnoses.nursingDiagnosis.slkiCriteria',
            'caseNursingDiagnoses.nursingDiagnosis.sikiInterventions.items',
            'caseNursingDiagnoses.interventionItems.sikiItem',
        ]);

        $record = $surgeryCase->intraopRecord;
        $specimen = $surgeryCase->specimens->first();

        $intraVitals = $surgeryCase->vitalSigns
            ->where('phase', VitalSignPhase::Intra)
            ->sortBy('recorded_at')
            ->values();

        $intraMeds = $surgeryCase->medications
            ->where('phase', VitalSignPhase::Intra)
            ->sortBy('given_at')
            ->values();

        $intraFluids = $surgeryCase->fluidRecords
            ->where('phase', VitalSignPhase::Intra)
            ->sortBy('recorded_at')
            ->values();

        $totalIntake = $intraFluids
            ->where('direction', FluidDirection::Intake)
            ->sum('volume_ml');
        $totalOutput = $intraFluids
            ->where('direction', FluidDirection::Output)
            ->sum('volume_ml');

        $intraActions = $surgeryCase->nursingActions
            ->where('phase', NursingPhase::Intra)
            ->sortBy('performed_at')
            ->values();

        $countsByItem = $surgeryCase->instrumentCounts->keyBy(fn (InstrumentCount $c): string => $c->item->value);
        $countRows = [];
        foreach (InstrumentCountItem::cases() as $enumItem) {
            $existing = $countsByItem->get($enumItem->value);
            $countRows[$enumItem->value] = [
                'item' => $enumItem->value,
                'label' => $enumItem->label(),
                'initial_count' => $existing instanceof InstrumentCount ? $existing->initial_count : 0,
                'additional_count' => $existing instanceof InstrumentCount ? $existing->additional_count : 0,
                'final_count' => $existing instanceof InstrumentCount && $existing->final_count !== null ? $existing->final_count : '',
                'status' => $existing instanceof InstrumentCount ? $this->enumValue($existing->getAttribute('status')) : '',
            ];
        }

        $activeIntraSignoffs = $surgeryCase->signoffs
            ->filter(fn (CaseSignoff $s): bool => $s->section === SignoffSection::IntraOp && $s->kind === SignoffKind::Signoff && $s->revoked_at === null)
            ->keyBy('role_label');

        $user = $request->user();

        $signoffParties = [
            [
                'role_label' => 'perawat_sirkuler',
                'title' => 'Perawat Sirkuler',
                'assigned_user_id' => $surgeryCase->circulating_nurse_id,
                'assigned_name' => $surgeryCase->circulatingNurse !== null ? $surgeryCase->circulatingNurse->name : 'Belum ditetapkan',
                'id_label' => 'NIP',
            ],
            [
                'role_label' => 'perawat_instrumen',
                'title' => 'Perawat Instrumen',
                'assigned_user_id' => $surgeryCase->scrub_nurse_id,
                'assigned_name' => $surgeryCase->scrubNurse !== null ? $surgeryCase->scrubNurse->name : 'Belum ditetapkan',
                'id_label' => 'NIP',
            ],
            [
                'role_label' => 'dokter_anestesi',
                'title' => 'Dokter Anestesi',
                'assigned_user_id' => $surgeryCase->anesthesiologist_id,
                'assigned_name' => $surgeryCase->anesthesiologist !== null ? $surgeryCase->anesthesiologist->name : 'Belum ditetapkan',
                'id_label' => 'SIP',
            ],
            [
                'role_label' => 'dokter_operator',
                'title' => 'Dokter Operator',
                'assigned_user_id' => $surgeryCase->surgeon_id,
                'assigned_name' => $surgeryCase->surgeon !== null ? $surgeryCase->surgeon->name : 'Belum ditetapkan',
                'id_label' => 'SIP',
            ],
        ];

        $signoffCards = array_map(function (array $party) use ($activeIntraSignoffs, $user, $surgeryCase): array {
            $signed = $activeIntraSignoffs->get($party['role_label']);

            return [
                'role_label' => $party['role_label'],
                'title' => $party['title'],
                'assigned_name' => $party['assigned_name'],
                'id_label' => $party['id_label'],
                'signed' => $signed instanceof CaseSignoff ? [
                    'user_name' => $signed->user !== null ? $signed->user->name : $party['assigned_name'],
                    'identifier' => $party['id_label'] === 'SIP' ? $signed->sip_snapshot : $signed->nip_snapshot,
                    'signed_at' => $signed->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                ] : null,
                'can_sign' => $user !== null
                    && $user->can('signIntraop', $surgeryCase)
                    && $party['assigned_user_id'] === $user->id
                    && ! ($signed instanceof CaseSignoff),
            ];
        }, $signoffParties);

        $signInCompleted = $surgeryCase->sscSignIn !== null && $surgeryCase->sscSignIn->status === SscStatus::Selesai;
        $timeOutCompleted = $surgeryCase->sscTimeOut !== null && $surgeryCase->sscTimeOut->status === SscStatus::Selesai;

        return Inertia::render('cases/intra-op', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'chronology' => [
                'updated_at' => OptimisticLock::token($surgeryCase),
                'anesthesia_type_id' => $surgeryCase->anesthesia_type_id,
                'operation_plan_actual' => $surgeryCase->operation_plan_actual ?? '',
                'entered_or_at' => $surgeryCase->entered_or_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
                'anesthesia_start_at' => $surgeryCase->anesthesia_start_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
                'incision_at' => $surgeryCase->incision_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
                'surgery_end_at' => $surgeryCase->surgery_end_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
                'left_or_at' => $surgeryCase->left_or_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
                'sign_in_completed' => $signInCompleted,
                'time_out_completed' => $timeOutCompleted,
            ],
            'positionArea' => $record === null ? [
                'updated_at' => null,
                'position' => '',
                'pressure_protection' => [],
                'skin_antiseptic' => [],
                'grounding_plate_site' => '',
                'skin_condition_pre' => '',
                'skin_condition_post' => '',
                'draping_done' => false,
                'warming' => [],
                'room_temperature_c' => '',
            ] : [
                'updated_at' => OptimisticLock::token($record),
                'position' => $this->enumValue($record->getAttribute('position')),
                'pressure_protection' => $record->pressure_protection ?? [],
                'skin_antiseptic' => $record->skin_antiseptic ?? [],
                'grounding_plate_site' => $record->grounding_plate_site ?? '',
                'skin_condition_pre' => $this->enumValue($record->getAttribute('skin_condition_pre')),
                'skin_condition_post' => $this->enumValue($record->getAttribute('skin_condition_post')),
                'draping_done' => $record->draping_done,
                'warming' => $record->warming ?? [],
                'room_temperature_c' => $record->room_temperature_c ?? '',
            ],
            'vitalSigns' => $intraVitals->map(fn (VitalSign $v): array => [
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
                'notes' => $v->notes,
            ])->values()->all(),
            'medications' => $intraMeds->map(fn (Medication $m): array => [
                'id' => $m->id,
                'name' => $m->name,
                'dose' => $m->dose,
                'route' => $m->route ?? '-',
                'given_at' => $m->given_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'note' => $m->note,
                'creator_name' => $m->creator !== null ? $m->creator->name : '-',
            ])->values()->all(),
            'fluids' => [
                'items' => $intraFluids->map(fn (FluidRecord $f): array => [
                    'id' => $f->id,
                    'recorded_at' => $f->recorded_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-',
                    'direction' => $f->direction->value,
                    'direction_label' => $f->direction->label(),
                    'kind' => $f->kind->value,
                    'kind_label' => $f->kind->label(),
                    'volume_ml' => $f->volume_ml,
                    'note' => $f->note,
                ])->values()->all(),
                'total_intake_ml' => (int) $totalIntake,
                'total_output_ml' => (int) $totalOutput,
                'balance_ml' => (int) ($totalIntake - $totalOutput),
            ],
            'nursingActions' => $intraActions->map(fn (NursingAction $a): array => [
                'id' => $a->id,
                'performed_at' => $a->performed_at->timezone('Asia/Jakarta')->format('H:i'),
                'performed_at_full' => $a->performed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'performed_by_role' => $a->performed_by_role,
                'performed_by_role_label' => $a->performed_by_role === 'instrumen' ? 'Perawat Instrumen' : 'Perawat Sirkuler',
                'action' => $a->action,
                'evaluation' => $a->evaluation ?? '-',
                'creator_name' => $a->creator !== null ? $a->creator->name : '-',
            ])->values()->all(),
            'instrumentCounts' => [
                'rows' => $countRows,
                'count_verification' => $record !== null ? $this->enumValue($record->getAttribute('count_verification')) : '',
                'count_discrepancy_actions' => $record !== null ? ($record->count_discrepancy_actions ?? []) : [],
            ],
            'specimenComplication' => [
                'updated_at' => OptimisticLock::token($record),
                'has_specimen' => $specimen !== null && $specimen->has_specimen,
                'specimen_type' => $specimen !== null ? ($specimen->specimen_type ?? '') : '',
                'origin_organ' => $specimen !== null ? ($specimen->origin_organ ?? '') : '',
                'purpose' => $specimen !== null ? $this->enumValue($specimen->getAttribute('purpose')) : '',
                'preservative' => $specimen !== null ? $this->enumValue($specimen->getAttribute('preservative')) : '',
                'has_complication' => $record !== null && $record->has_complication,
                'complication_description' => $record !== null ? ($record->complication_description ?? '') : '',
                'complication_handling' => $record !== null ? ($record->complication_handling ?? '') : '',
            ],
            'caseNursingDiagnoses' => $surgeryCase->caseNursingDiagnoses
                ->where('phase', NursingPhase::Intra)
                ->map(fn (CaseNursingDiagnosis $cd): array => [
                    'id' => $cd->id,
                    'name' => $cd->displayName(),
                    'code' => $cd->nursingDiagnosis?->code,
                    'custom_text' => $cd->custom_text,
                    'notes' => $cd->notes,
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
                                'section' => $item->section->value,
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
            'signoffCards' => $signoffCards,
            'options' => [
                'anesthesiaTypes' => AnesthesiaType::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
                'positions' => array_map(fn (SurgicalPosition $e): array => ['value' => $e->value, 'label' => $e->label()], SurgicalPosition::cases()),
                'skinIntegrities' => array_map(fn (SkinIntegrity $e): array => ['value' => $e->value, 'label' => $e->label()], SkinIntegrity::cases()),
                'breathingPatterns' => array_map(fn (BreathingPattern $e): array => ['value' => $e->value, 'label' => $e->label()], BreathingPattern::cases()),
                'oxygenDevices' => array_map(fn (OxygenDevice $e): array => ['value' => $e->value, 'label' => $e->label()], OxygenDevice::cases()),
                'consciousnessList' => array_map(fn (Consciousness $e): array => ['value' => $e->value, 'label' => $e->label()], Consciousness::cases()),
                'fluidKinds' => array_map(fn (FluidKind $e): array => [
                    'value' => $e->value,
                    'label' => $e->label(),
                    'direction' => $e->direction()->value,
                ], FluidKind::cases()),
                'countVerifications' => array_map(fn (CountVerification $e): array => ['value' => $e->value, 'label' => $e->label()], CountVerification::cases()),
                'specimenPurposes' => array_map(fn (SpecimenPurpose $e): array => ['value' => $e->value, 'label' => $e->label()], SpecimenPurpose::cases()),
                'specimenPreservatives' => array_map(fn (SpecimenPreservative $e): array => ['value' => $e->value, 'label' => $e->label()], SpecimenPreservative::cases()),
                'masterNursingDiagnoses' => NursingDiagnosis::query()->active()->where('phase', NursingPhase::Intra->value)->orderBy('id')->get(['id', 'code', 'name']),
            ],
            'permissions' => [
                'canEditIntraop' => $user !== null && $user->can('updateIntraop', $surgeryCase),
                'canEditAnesthesia' => $user !== null && $user->can('updateIntraopAnesthesia', $surgeryCase),
                'canEditComplication' => $user !== null && $user->can('updateIntraopComplication', $surgeryCase),
                'canAdvance' => $user !== null && $user->can('advanceStatus', $surgeryCase),
            ],
        ]);
    }

    public function updateChronology(
        SaveIntraopChronologyRequest $request,
        SurgeryCase $surgeryCase,
        SaveIntraopChronology $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Kronologi waktu intra-operasi berhasil disimpan.');
    }

    public function updatePosition(
        SaveIntraopPositionRequest $request,
        SurgeryCase $surgeryCase,
        SaveIntraopRecord $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->savePositionAndArea($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Persiapan posisi dan area operasi berhasil disimpan.');
    }

    public function addVitalSign(
        StoreIntraopVitalSignRequest $request,
        SurgeryCase $surgeryCase,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->addVitalSign($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Pemantauan TTV intra-operasi berhasil ditambahkan.');
    }

    public function deleteVitalSign(
        Request $request,
        SurgeryCase $surgeryCase,
        VitalSign $vitalSign,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $this->authorize('updateIntraop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $action->deleteVitalSign($surgeryCase, $vitalSign, $user);

        return back()->with('success', 'Entri TTV intra-operasi berhasil dihapus.');
    }

    public function addMedication(
        StoreIntraopMedicationRequest $request,
        SurgeryCase $surgeryCase,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->addMedication($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Pemberian obat intra-operasi berhasil ditambahkan.');
    }

    public function deleteMedication(
        Request $request,
        SurgeryCase $surgeryCase,
        Medication $medication,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $this->authorize('updateIntraopAnesthesia', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $action->deleteMedication($surgeryCase, $medication, $user);

        return back()->with('success', 'Entri obat berhasil dihapus.');
    }

    public function addFluid(
        StoreIntraopFluidRequest $request,
        SurgeryCase $surgeryCase,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->addFluid($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Catatan cairan intra-operasi berhasil ditambahkan.');
    }

    public function deleteFluid(
        Request $request,
        SurgeryCase $surgeryCase,
        FluidRecord $fluidRecord,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $this->authorize('updateIntraop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $action->deleteFluid($surgeryCase, $fluidRecord, $user);

        return back()->with('success', 'Catatan cairan berhasil dihapus.');
    }

    public function addNursingAction(
        StoreIntraopNursingActionRequest $request,
        SurgeryCase $surgeryCase,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->addNursingAction($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Tindakan keperawatan intra-operasi berhasil dicatat.');
    }

    public function applyTemplates(
        Request $request,
        SurgeryCase $surgeryCase,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $this->authorize('updateIntraop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $added = $action->applyTemplates($surgeryCase, $user);

        return back()->with('success', $added > 0
            ? "{$added} tindakan dari template standar intra-op berhasil ditambahkan."
            : 'Semua tindakan template standar sudah ada di timeline.');
    }

    public function deleteNursingAction(
        Request $request,
        SurgeryCase $surgeryCase,
        NursingAction $nursingAction,
        ManageIntraopMonitoring $action,
    ): RedirectResponse {
        $this->authorize('updateIntraop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $action->deleteNursingAction($surgeryCase, $nursingAction, $user);

        return back()->with('success', 'Tindakan keperawatan berhasil dihapus.');
    }

    public function saveInstrumentCounts(
        SaveInstrumentCountsRequest $request,
        SurgeryCase $surgeryCase,
        SaveInstrumentCounts $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Penghitungan instrumen, kasa, dan jarum berhasil disimpan.');
    }

    public function updateSpecimenComplication(
        SaveIntraopSpecimenComplicationRequest $request,
        SurgeryCase $surgeryCase,
        SaveIntraopRecord $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->saveSpecimenAndComplication($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Data spesimen dan kejadian khusus intra-operasi berhasil disimpan.');
    }

    public function addDiagnosis(
        AddCaseNursingDiagnosisRequest $request,
        SurgeryCase $surgeryCase,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $data = $request->validated();
        $data['phase'] = NursingPhase::Intra->value;

        $action->addDiagnosis($surgeryCase, $data, $user);

        return back()->with('success', 'Diagnosis keperawatan intra-operasi berhasil ditambahkan.');
    }

    public function removeDiagnosis(
        Request $request,
        SurgeryCase $surgeryCase,
        CaseNursingDiagnosis $caseNursingDiagnosis,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $this->authorize('updateIntraop', $surgeryCase);
        abort_if($caseNursingDiagnosis->case_id !== $surgeryCase->id, 404);

        $user = $request->user();
        abort_if($user === null, 403);

        $action->removeDiagnosis($caseNursingDiagnosis, $user);

        return back()->with('success', 'Diagnosis keperawatan intra-operasi berhasil dihapus.');
    }

    public function toggleIntervention(
        Request $request,
        SurgeryCase $surgeryCase,
        CaseInterventionItem $caseInterventionItem,
        ManageCaseNursingDiagnosis $action,
    ): RedirectResponse {
        $this->authorize('updateIntraop', $surgeryCase);
        $user = $request->user();
        abort_if($user === null, 403);

        $action->toggleIntervention($caseInterventionItem, $user);

        return back();
    }

    public function sign(
        SignIntraopRequest $request,
        SurgeryCase $surgeryCase,
        SignIntraopCase $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $validated = $request->validated();
        $action->execute($surgeryCase, (string) $validated['role_label'], $user);

        return back()->with('success', 'Pengesahan dokumentasi intra-operasi berhasil dicatat.');
    }

    private function enumValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($value) ? $value : '';
    }
}
