<?php

namespace App\Http\Controllers;

use App\Actions\SaveSscSignIn;
use App\Actions\SaveSscSignOut;
use App\Actions\SaveSscTimeOut;
use App\Actions\VerifySscSignIn;
use App\Actions\VerifySscSignOut;
use App\Enums\AllergyStatus;
use App\Enums\BloodReadiness;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Enums\IvAccessStatus;
use App\Enums\RiskPresence;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SiteMarkingStatus;
use App\Enums\SscConsentStatus;
use App\Enums\SscMatchStatus;
use App\Enums\SscReadiness;
use App\Enums\SscStatus;
use App\Http\Requests\Ssc\SaveSscSignInRequest;
use App\Http\Requests\Ssc\SaveSscSignOutRequest;
use App\Http\Requests\Ssc\SaveSscTimeOutRequest;
use App\Http\Requests\Ssc\VerifySscSignInRequest;
use App\Http\Requests\Ssc\VerifySscSignOutRequest;
use App\Models\CaseSignoff;
use App\Models\InstrumentCount;
use App\Models\SscSignIn;
use App\Models\SurgeryCase;
use App\Support\OptimisticLock;
use App\Support\SurgeryCasePresenter;
use BackedEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SscController extends Controller
{
    public function __construct(
        private readonly SurgeryCasePresenter $presenter,
    ) {}

    public function index(SurgeryCase $surgeryCase): RedirectResponse
    {
        $this->authorize('view', $surgeryCase);

        return redirect()->route('cases.ssc.sign-in', $surgeryCase);
    }

    public function showSignIn(Request $request, SurgeryCase $surgeryCase): Response
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
            'preopAssessment',
            'sscSignIn.filler',
            'signoffs.user',
        ]);

        $signIn = $surgeryCase->sscSignIn;
        $preop = $surgeryCase->preopAssessment;

        $defaultReadiness = [];
        foreach (SscSignIn::READINESS_KEYS as $key) {
            $defaultReadiness[$key] = '';
        }

        $signInData = $signIn === null ? [
            'id' => null,
            'updated_at' => null,
            'status' => SscStatus::BelumDimulai->value,
            'identity_confirmed' => '',
            'procedure_confirmed' => '',
            'site_confirmed' => '',
            'consent_surgery' => '',
            'consent_anesthesia' => '',
            'site_marking' => '',
            'readiness' => $defaultReadiness,
            'pulse_ox_attached' => null,
            'pulse_ox_functional' => null,
            'spo2' => '',
            'pulse' => '',
            'pulse_ox_note' => '',
            'allergy_status' => $preop !== null ? $preop->allergy_status->value : '',
            'allergy_type' => '',
            'allergy_agent' => $preop !== null && $preop->allergy_status === AllergyStatus::Ada ? ($preop->allergy_detail ?? '') : '',
            'allergy_reaction' => '',
            'allergy_followup' => '',
            'difficult_airway_risk' => '',
            'aspiration_risk' => '',
            'anesthesiologist_assessment' => '',
            'anticipation_plan' => '',
            'airway_equipment_ready' => '',
            'bleeding_risk' => '',
            'estimated_blood_loss_ml' => '',
            'iv_access' => '',
            'fluids_ready' => '',
            'blood_ready' => '',
            'bleeding_note' => '',
            'notes_followup' => '',
            'signed_at' => null,
            'filled_by_name' => null,
        ] : [
            'id' => $signIn->id,
            'updated_at' => OptimisticLock::token($signIn),
            'status' => $signIn->status->value,
            'identity_confirmed' => $this->enumValue($signIn->getAttribute('identity_confirmed')),
            'procedure_confirmed' => $this->enumValue($signIn->getAttribute('procedure_confirmed')),
            'site_confirmed' => $this->enumValue($signIn->getAttribute('site_confirmed')),
            'consent_surgery' => $this->enumValue($signIn->getAttribute('consent_surgery')),
            'consent_anesthesia' => $this->enumValue($signIn->getAttribute('consent_anesthesia')),
            'site_marking' => $this->enumValue($signIn->getAttribute('site_marking')),
            'readiness' => array_merge($defaultReadiness, array_map(
                fn (mixed $v): string => is_string($v) ? $v : '',
                $signIn->readiness ?? []
            )),
            'pulse_ox_attached' => $signIn->pulse_ox_attached,
            'pulse_ox_functional' => $signIn->pulse_ox_functional,
            'spo2' => $signIn->spo2 ?? '',
            'pulse' => $signIn->pulse ?? '',
            'pulse_ox_note' => $signIn->pulse_ox_note ?? '',
            'allergy_status' => $this->enumValue($signIn->getAttribute('allergy_status')),
            'allergy_type' => $signIn->allergy_type ?? '',
            'allergy_agent' => $signIn->allergy_agent ?? '',
            'allergy_reaction' => $signIn->allergy_reaction ?? '',
            'allergy_followup' => $signIn->allergy_followup ?? '',
            'difficult_airway_risk' => $this->enumValue($signIn->getAttribute('difficult_airway_risk')),
            'aspiration_risk' => $this->enumValue($signIn->getAttribute('aspiration_risk')),
            'anesthesiologist_assessment' => $signIn->anesthesiologist_assessment ?? '',
            'anticipation_plan' => $signIn->anticipation_plan ?? '',
            'airway_equipment_ready' => $this->enumValue($signIn->getAttribute('airway_equipment_ready')),
            'bleeding_risk' => $this->enumValue($signIn->getAttribute('bleeding_risk')),
            'estimated_blood_loss_ml' => $signIn->estimated_blood_loss_ml ?? '',
            'iv_access' => $this->enumValue($signIn->getAttribute('iv_access')),
            'fluids_ready' => $this->enumValue($signIn->getAttribute('fluids_ready')),
            'blood_ready' => $this->enumValue($signIn->getAttribute('blood_ready')),
            'bleeding_note' => $signIn->bleeding_note ?? '',
            'notes_followup' => $signIn->notes_followup ?? '',
            'signed_at' => $signIn->signed_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
            'filled_by_name' => $signIn->filler !== null ? $signIn->filler->name : null,
        ];

        $signInSignoffs = $surgeryCase->signoffs->filter(
            fn (CaseSignoff $s): bool => $s->section === SignoffSection::SignIn && $s->kind === SignoffKind::Verification
        );

        $activeVerification = $signInSignoffs->first(fn (CaseSignoff $s): bool => $s->revoked_at === null);
        $revokedVerifications = $signInSignoffs->filter(fn (CaseSignoff $s): bool => $s->revoked_at !== null)
            ->map(fn (CaseSignoff $s): array => [
                'id' => $s->id,
                'user_name' => $s->user !== null ? $s->user->name : 'Dokter Anestesi',
                'signed_at' => $s->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'revoked_at' => $s->revoked_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'revoke_reason' => $s->revoke_reason,
            ])->values()->all();

        $user = $request->user();

        return Inertia::render('cases/ssc/sign-in', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'roomAndTeamAssigned' => $surgeryCase->hasRoomAndTeamAssigned(),
            'signIn' => $signInData,
            'verificationState' => [
                'activeVerification' => $activeVerification !== null ? [
                    'id' => $activeVerification->id,
                    'user_name' => $activeVerification->user !== null ? $activeVerification->user->name : 'Dokter Anestesi',
                    'sip' => $activeVerification->sip_snapshot,
                    'signed_at' => $activeVerification->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                ] : null,
                'revokedVerifications' => $revokedVerifications,
            ],
            'options' => [
                'sscStatuses' => array_map(fn (SscStatus $e): array => ['value' => $e->value, 'label' => $e->label()], SscStatus::cases()),
                'matchStatuses' => array_map(fn (SscMatchStatus $e): array => ['value' => $e->value, 'label' => $e->label()], SscMatchStatus::cases()),
                'consentStatuses' => array_map(fn (SscConsentStatus $e): array => ['value' => $e->value, 'label' => $e->label()], SscConsentStatus::cases()),
                'siteMarkingStatuses' => array_map(fn (SiteMarkingStatus $e): array => ['value' => $e->value, 'label' => $e->label()], SiteMarkingStatus::cases()),
                'readinessOptions' => array_map(fn (SscReadiness $e): array => ['value' => $e->value, 'label' => $e->label()], SscReadiness::cases()),
                'allergyStatuses' => array_map(fn (AllergyStatus $e): array => ['value' => $e->value, 'label' => $e->label()], AllergyStatus::cases()),
                'riskPresences' => array_map(fn (RiskPresence $e): array => ['value' => $e->value, 'label' => $e->label()], RiskPresence::cases()),
                'ivAccessStatuses' => array_map(fn (IvAccessStatus $e): array => ['value' => $e->value, 'label' => $e->label()], IvAccessStatus::cases()),
                'bloodReadinessOptions' => array_map(fn (BloodReadiness $e): array => ['value' => $e->value, 'label' => $e->label()], BloodReadiness::cases()),
            ],
            'permissions' => [
                'canEditAll' => $user !== null && $user->isPerawat() && $user->can('updateSignIn', $surgeryCase),
                'canEditAnesthesiaRisk' => $user !== null && $user->can('updateSignIn', $surgeryCase),
                'canVerify' => $user !== null && $user->can('verifySignIn', $surgeryCase),
                'canAdvance' => $user !== null && $user->can('advanceStatus', $surgeryCase),
            ],
        ]);
    }

    public function updateSignIn(
        SaveSscSignInRequest $request,
        SurgeryCase $surgeryCase,
        SaveSscSignIn $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Data SSC Sign In berhasil disimpan.');
    }

    public function verifySignIn(
        VerifySscSignInRequest $request,
        SurgeryCase $surgeryCase,
        VerifySscSignIn $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $user);

        return back()->with('success', 'Verifikasi SSC Sign In oleh dokter anestesi berhasil dicatat.');
    }

    public function showTimeOut(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('view', $surgeryCase);

        $surgeryCase->loadMissing([
            'patient',
            'room',
            'surgeon',
            'anesthesiologist',
            'circulatingNurse',
            'scrubNurse',
            'anesthesiaNurse',
            'sscTimeOut.filler',
        ]);

        $timeOut = $surgeryCase->sscTimeOut;
        $user = $request->user();

        $timeOutData = $timeOut === null ? [
            'id' => null,
            'updated_at' => null,
            'status' => SscStatus::BelumDimulai->value,
            'intro_circulating' => false,
            'intro_scrub' => false,
            'intro_anesthesia_nurse' => false,
            'intro_surgeon' => false,
            'intro_anesthesiologist' => false,
            'identity_procedure_site_confirmed' => false,
            'antibiotic_prophylaxis' => '',
            'antibiotic_name' => '',
            'antibiotic_given_at' => '',
            'antibiotic_confirmed_by' => '',
            'surgeon_critical_steps' => '',
            'surgeon_critical_steps_text' => '',
            'surgeon_estimated_duration_min' => '',
            'surgeon_estimated_blood_loss_ml' => '',
            'anesthesia_special_condition' => '',
            'anesthesia_risk' => '',
            'anesthesia_note' => '',
            'nurse_sterility_confirmed' => false,
            'nurse_indicator_checked' => false,
            'equipment_problem' => '',
            'other_concern' => '',
            'nurse_note' => '',
            'imaging_needed' => '',
            'imaging_displayed' => '',
            'imaging_matches' => '',
            'signed_at' => null,
            'filled_by_name' => null,
        ] : [
            'id' => $timeOut->id,
            'updated_at' => OptimisticLock::token($timeOut),
            'status' => $timeOut->status->value,
            'intro_circulating' => $timeOut->intro_circulating,
            'intro_scrub' => $timeOut->intro_scrub,
            'intro_anesthesia_nurse' => $timeOut->intro_anesthesia_nurse,
            'intro_surgeon' => $timeOut->intro_surgeon,
            'intro_anesthesiologist' => $timeOut->intro_anesthesiologist,
            'identity_procedure_site_confirmed' => $timeOut->identity_procedure_site_confirmed,
            'antibiotic_prophylaxis' => $timeOut->antibiotic_prophylaxis ?? '',
            'antibiotic_name' => $timeOut->antibiotic_name ?? '',
            'antibiotic_given_at' => $timeOut->antibiotic_given_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '',
            'antibiotic_confirmed_by' => $timeOut->antibiotic_confirmed_by ?? '',
            'surgeon_critical_steps' => $timeOut->surgeon_critical_steps ?? '',
            'surgeon_critical_steps_text' => $timeOut->surgeon_critical_steps_text ?? '',
            'surgeon_estimated_duration_min' => $timeOut->surgeon_estimated_duration_min ?? '',
            'surgeon_estimated_blood_loss_ml' => $timeOut->surgeon_estimated_blood_loss_ml ?? '',
            'anesthesia_special_condition' => $timeOut->anesthesia_special_condition ?? '',
            'anesthesia_risk' => $timeOut->anesthesia_risk ?? '',
            'anesthesia_note' => $timeOut->anesthesia_note ?? '',
            'nurse_sterility_confirmed' => $timeOut->nurse_sterility_confirmed,
            'nurse_indicator_checked' => $timeOut->nurse_indicator_checked,
            'equipment_problem' => $timeOut->equipment_problem ?? '',
            'other_concern' => $timeOut->other_concern ?? '',
            'nurse_note' => $timeOut->nurse_note ?? '',
            'imaging_needed' => $timeOut->imaging_needed ?? '',
            'imaging_displayed' => $timeOut->imaging_displayed ?? '',
            'imaging_matches' => $timeOut->imaging_matches ?? '',
            'signed_at' => $timeOut->signed_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
            'filled_by_name' => $timeOut->filler !== null ? $timeOut->filler->name : null,
        ];

        $canUpdate = $user !== null && $user->can('updateTimeOut', $surgeryCase);

        return Inertia::render('cases/ssc/time-out', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'timeOut' => $timeOutData,
            'options' => [
                'sscStatuses' => array_map(fn (SscStatus $e): array => ['value' => $e->value, 'label' => $e->label()], SscStatus::cases()),
            ],
            'permissions' => [
                'canEditNurseBlocks' => $canUpdate && $user->isPerawat(),
                'canEditSurgeonBlock' => $canUpdate && $user->isDokterOperator() && $surgeryCase->surgeon_id === $user->id,
                'canEditAnesthesiaBlock' => $canUpdate && $user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id,
            ],
        ]);
    }

    public function updateTimeOut(
        SaveSscTimeOutRequest $request,
        SurgeryCase $surgeryCase,
        SaveSscTimeOut $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Data SSC Time Out berhasil disimpan.');
    }

    public function showSignOut(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('view', $surgeryCase);

        $surgeryCase->loadMissing([
            'patient',
            'room',
            'surgeon',
            'anesthesiologist',
            'circulatingNurse',
            'scrubNurse',
            'anesthesiaNurse',
            'sscSignOut.filler',
            'instrumentCounts',
            'intraopRecord',
            'specimens',
            'signoffs.user',
        ]);

        $signOut = $surgeryCase->sscSignOut;
        $record = $surgeryCase->intraopRecord;
        $counts = $surgeryCase->instrumentCounts;
        $specimen = $surgeryCase->specimens->first();

        $signOutData = $signOut === null ? [
            'id' => null,
            'updated_at' => null,
            'status' => SignOutStatus::Draf->value,
            'q1_procedure_confirmed' => null,
            'q2_counts_correct' => null,
            'q3_specimen_labeled' => '',
            'q4_equipment_problem' => null,
            'q5_recovery_discussed' => null,
            'extra_6_procedure_matches' => '',
            'extra_7_changes_recorded' => '',
            'extra_8_discrepancy_followup' => '',
            'extra_9_specimen_matches' => '',
            'extra_10_equipment_issue_recorded' => '',
            'extra_11_complication_communicated' => '',
            'extra_12_postop_monitoring_discussed' => '',
            'extra_13_devices_instructions' => '',
            'recovery_notes' => '',
            'issues_and_actions' => '',
            'signed_at' => null,
            'filled_by_name' => null,
        ] : [
            'id' => $signOut->id,
            'updated_at' => OptimisticLock::token($signOut),
            'status' => $signOut->status->value,
            'q1_procedure_confirmed' => $signOut->q1_procedure_confirmed,
            'q2_counts_correct' => $signOut->q2_counts_correct,
            'q3_specimen_labeled' => $signOut->q3_specimen_labeled ?? '',
            'q4_equipment_problem' => $signOut->q4_equipment_problem,
            'q5_recovery_discussed' => $signOut->q5_recovery_discussed,
            'extra_6_procedure_matches' => $signOut->extra_6_procedure_matches ?? '',
            'extra_7_changes_recorded' => $signOut->extra_7_changes_recorded ?? '',
            'extra_8_discrepancy_followup' => $signOut->extra_8_discrepancy_followup ?? '',
            'extra_9_specimen_matches' => $signOut->extra_9_specimen_matches ?? '',
            'extra_10_equipment_issue_recorded' => $signOut->extra_10_equipment_issue_recorded ?? '',
            'extra_11_complication_communicated' => $signOut->extra_11_complication_communicated ?? '',
            'extra_12_postop_monitoring_discussed' => $signOut->extra_12_postop_monitoring_discussed ?? '',
            'extra_13_devices_instructions' => $signOut->extra_13_devices_instructions ?? '',
            'recovery_notes' => $signOut->recovery_notes ?? '',
            'issues_and_actions' => $signOut->issues_and_actions ?? '',
            'signed_at' => $signOut->signed_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
            'filled_by_name' => $signOut->filler !== null ? $signOut->filler->name : null,
        ];

        $signOutVerifications = $surgeryCase->signoffs
            ->filter(fn (CaseSignoff $s): bool => $s->section === SignoffSection::SignOut && $s->kind === SignoffKind::Verification && $s->revoked_at === null)
            ->map(fn (CaseSignoff $s): array => [
                'id' => $s->id,
                'role_label' => $s->role_label,
                'user_name' => $s->user !== null ? $s->user->name : 'Dokter',
                'sip' => $s->sip_snapshot,
                'signed_at' => $s->signed_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ])->values()->all();

        $allFiveFinal = $counts->count() >= count(InstrumentCountItem::cases())
            && $counts->every(fn (InstrumentCount $c): bool => $c->final_count !== null);
        $hasTs = $counts->contains(fn (InstrumentCount $c): bool => $c->status === InstrumentCountStatus::TidakSesuai);

        $user = $request->user();

        return Inertia::render('cases/ssc/sign-out', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'signOut' => $signOutData,
            'intraopSummary' => [
                'all_five_final' => $allFiveFinal,
                'has_ts' => $hasTs,
                'count_verification' => $record !== null ? $this->enumValue($record->getAttribute('count_verification')) : '',
                'count_verification_label' => $record !== null && $record->count_verification !== null ? $record->count_verification->label() : 'Belum diverifikasi',
                'discrepancy_actions' => $record !== null ? ($record->count_discrepancy_actions ?? []) : [],
                'has_specimen' => $specimen !== null && $specimen->has_specimen,
                'specimen_summary' => $specimen !== null && $specimen->has_specimen
                    ? "{$specimen->specimen_type} ({$specimen->origin_organ}) - {$specimen->purpose?->label()}"
                    : 'Tidak ada spesimen',
                'has_complication' => $record !== null && $record->has_complication,
                'complication_description' => $record !== null ? $record->complication_description : null,
            ],
            'verifications' => $signOutVerifications,
            'options' => [
                'signOutStatuses' => array_map(fn (SignOutStatus $e): array => ['value' => $e->value, 'label' => $e->label()], SignOutStatus::cases()),
            ],
            'permissions' => [
                'canEdit' => $user !== null && $user->can('updateSignOut', $surgeryCase),
                'canVerify' => $user !== null && $user->can('verifySignOut', $surgeryCase),
            ],
        ]);
    }

    public function updateSignOut(
        SaveSscSignOutRequest $request,
        SurgeryCase $surgeryCase,
        SaveSscSignOut $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $request->validated(), $user);

        return back()->with('success', 'Data SSC Sign Out berhasil disimpan.');
    }

    public function verifySignOut(
        VerifySscSignOutRequest $request,
        SurgeryCase $surgeryCase,
        VerifySscSignOut $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $action->execute($surgeryCase, $user);

        return back()->with('success', 'Verifikasi SSC Sign Out oleh dokter berhasil dicatat.');
    }

    private function enumValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($value) ? $value : '';
    }
}
