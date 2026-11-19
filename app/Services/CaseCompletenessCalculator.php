<?php

namespace App\Services;

use App\Enums\CaseStatus;
use App\Enums\CountVerification;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Enums\ReadinessStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SscStatus;
use App\Enums\VitalSignPhase;
use App\Models\CaseSignoff;
use App\Models\InstrumentCheck;
use App\Models\InstrumentCount;
use App\Models\SurgeryCase;
use App\Models\VitalSign;

class CaseCompletenessCalculator
{
    /**
     * Menghitung persentase kelengkapan dokumentasi kasus dari 10 bagian wajib.
     *
     * @return array{
     *     percentage: int,
     *     completed: int,
     *     total: int,
     *     parts: list<array{key: string, label: string, complete: bool}>
     * }
     */
    public function calculate(SurgeryCase $case): array
    {
        $case->loadMissing([
            'patient',
            'preopAssessment',
            'vitalSigns',
            'instrumentChecks',
            'sscSignIn',
            'sscTimeOut',
            'sscSignOut',
            'intraopRecord',
            'instrumentCounts',
            'postopObservations',
            'postopSummary',
            'handover',
            'signoffs',
        ]);

        $patient = $case->patient;
        $preop = $case->preopAssessment;
        $signoffs = $case->signoffs->filter(fn (CaseSignoff $s): bool => $s->revoked_at === null);

        $identityAndPlan = $patient !== null
            && trim($patient->rm_number) !== ''
            && trim($patient->name) !== ''
            && ($case->medical_diagnosis_id !== null || ($case->medical_diagnosis_other !== null && trim($case->medical_diagnosis_other) !== ''))
            && ($case->surgery_type_id !== null || ($case->surgery_type_other !== null && trim($case->surgery_type_other) !== ''));

        $hasPreVital = $case->vitalSigns->contains(fn (VitalSign $v): bool => $v->phase === VitalSignPhase::Pre);
        $preopAssessmentDone = $preop !== null
            && $preop->chief_complaint !== null
            && trim($preop->chief_complaint) !== ''
            && $hasPreVital;

        $hasPreopSignoff = $signoffs->contains(
            fn (CaseSignoff $s): bool => $s->section === SignoffSection::PreOp && $s->kind === SignoffKind::Signoff
        );
        $preopReadySigned = $preop !== null
            && $preop->readiness_status === ReadinessStatus::Siap
            && $hasPreopSignoff;

        $teamAssigned = $case->hasRoomAndTeamAssigned();

        $instrumentChecksDone = $case->instrumentChecks->isNotEmpty()
            && $case->instrumentChecks->every(fn (InstrumentCheck $c): bool => $c->isResolved());

        $signInDone = $case->sscSignIn !== null && $case->sscSignIn->status === SscStatus::Selesai;
        $timeOutDone = $case->sscTimeOut !== null && $case->sscTimeOut->status === SscStatus::Selesai;
        $signOutDone = $case->sscSignOut !== null && $case->sscSignOut->status === SignOutStatus::Terverifikasi;

        $intraLabels = $signoffs
            ->filter(fn (CaseSignoff $s): bool => $s->section === SignoffSection::IntraOp && $s->kind === SignoffKind::Signoff)
            ->pluck('role_label')
            ->all();
        $requiredIntraLabels = ['perawat_sirkuler', 'perawat_instrumen', 'dokter_anestesi', 'dokter_operator'];
        $fourSigned = count(array_intersect($requiredIntraLabels, $intraLabels)) === count($requiredIntraLabels);

        $counts = $case->instrumentCounts;
        $record = $case->intraopRecord;
        $allFiveCounts = $counts->count() >= count(InstrumentCountItem::cases())
            && $counts->every(fn (InstrumentCount $c): bool => $c->final_count !== null);
        $hasTs = $counts->contains(fn (InstrumentCount $c): bool => $c->status === InstrumentCountStatus::TidakSesuai);
        $verification = $record !== null ? $record->count_verification : null;
        $discrepancyActions = $record !== null ? ($record->count_discrepancy_actions ?? []) : [];
        $countsValid = $allFiveCounts
            && $verification !== null
            && ((! $hasTs && $verification === CountVerification::LengkapSesuai) || ($verification === CountVerification::TidakSesuai && $discrepancyActions !== []));

        $intraopComplete = $case->entered_or_at !== null
            && $case->surgery_end_at !== null
            && $case->left_or_at !== null
            && $countsValid
            && $fourSigned;

        $summary = $case->postopSummary;
        $postopAndHandoverDone = $case->postopObservations->isNotEmpty()
            && $summary !== null
            && $summary->ready_to_transfer
            && $case->handover !== null;

        $parts = [
            ['key' => 'identity_plan', 'label' => 'Identitas & Rencana Tindakan', 'complete' => $identityAndPlan],
            ['key' => 'preop_assessment', 'label' => 'Asesmen & TTV Pre-Op', 'complete' => $preopAssessmentDone],
            ['key' => 'preop_signoff', 'label' => 'Kesiapan & Pengesahan Pre-Op', 'complete' => $preopReadySigned],
            ['key' => 'room_team', 'label' => 'Penetapan Ruang & Tim', 'complete' => $teamAssigned],
            ['key' => 'instrument_checks', 'label' => 'Cek Alat Operasi', 'complete' => $instrumentChecksDone],
            ['key' => 'ssc_sign_in', 'label' => 'SSC Sign In', 'complete' => $signInDone],
            ['key' => 'ssc_time_out', 'label' => 'SSC Time Out', 'complete' => $timeOutDone],
            ['key' => 'intraop_core', 'label' => 'Intra-Op, Hitungan & 4 Pengesahan', 'complete' => $intraopComplete],
            ['key' => 'ssc_sign_out', 'label' => 'SSC Sign Out', 'complete' => $signOutDone],
            ['key' => 'postop_handover', 'label' => 'Pasca-Op & Serah Terima', 'complete' => $postopAndHandoverDone],
        ];

        $completed = count(array_filter($parts, fn (array $p): bool => $p['complete']));
        $total = count($parts);
        $percentage = (int) round(($completed / $total) * 100);

        return [
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => $total,
            'parts' => $parts,
        ];
    }

    public function hasIncompleteSsc(SurgeryCase $case): bool
    {
        $case->loadMissing(['sscSignIn', 'sscTimeOut', 'sscSignOut']);

        $signInDone = $case->sscSignIn !== null && $case->sscSignIn->status === SscStatus::Selesai;
        $timeOutDone = $case->sscTimeOut !== null && $case->sscTimeOut->status === SscStatus::Selesai;
        $signOutDone = $case->sscSignOut !== null && $case->sscSignOut->status === SignOutStatus::Terverifikasi;

        if ($case->status === CaseStatus::PreOp) {
            return ! $signInDone;
        }

        if ($case->status === CaseStatus::SiapOperasi) {
            return ! $signInDone || ! $timeOutDone;
        }

        return ! $signInDone || ! $timeOutDone || ! $signOutDone;
    }

    public function hasPendingDoctorVerification(SurgeryCase $case): bool
    {
        $case->loadMissing(['signoffs']);

        $hasActiveDoctorVerification = $case->signoffs->contains(
            fn (CaseSignoff $s): bool => $s->kind === SignoffKind::Verification && $s->revoked_at === null
        );

        return ! $hasActiveDoctorVerification;
    }
}
