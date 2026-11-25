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
use App\Enums\SurgeryProfile;
use App\Enums\VitalSignPhase;
use App\Models\CaseSignoff;
use App\Models\InstrumentCheck;
use App\Models\InstrumentCount;
use App\Models\SurgeryCase;
use App\Models\VitalSign;

class CaseTransitionChecker
{
    /**
     * @return list<string>
     */
    public function missingRequirements(SurgeryCase $case, CaseStatus $target): array
    {
        if ($case->status === CaseStatus::Selesai) {
            return ['Kasus yang sudah selesai tidak dapat diubah statusnya.'];
        }

        if ($target->order() < $case->status->order()) {
            return ['Status kasus tidak boleh mundur ke tahap sebelumnya.'];
        }

        if ($target === $case->status) {
            return ['Status tujuan harus berbeda dari status saat ini.'];
        }

        $next = $case->status->next();
        if ($next === null || $target !== $next) {
            return ['Tahap tidak boleh dilewati; status berikutnya harus '.($next?->label() ?? '-').'.'];
        }

        $missing = [];
        foreach ($this->requirementsFor($case, $target) as $item) {
            if (! $item['met']) {
                $missing[] = $item['label'];
            }
        }

        return $missing;
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    public function stageChecklist(SurgeryCase $case): array
    {
        $next = $case->status->next();
        if ($next === null) {
            return [];
        }

        return $this->requirementsFor($case, $next);
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    private function requirementsFor(SurgeryCase $case, CaseStatus $target): array
    {
        $case->loadMissing([
            'patient',
            'signoffs',
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
        ]);

        return match ($target) {
            CaseStatus::SiapOperasi => $this->siapOperasiRequirements($case),
            CaseStatus::IntraOp => $this->intraOpRequirements($case),
            CaseStatus::PascaOp => $this->pascaOpRequirements($case),
            CaseStatus::SerahTerima => $this->serahTerimaRequirements($case),
            CaseStatus::Selesai => $this->selesaiRequirements($case),
            CaseStatus::PreOp => [],
        };
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    private function siapOperasiRequirements(SurgeryCase $case): array
    {
        $patient = $case->patient;
        $identityComplete = $patient !== null
            && trim($patient->rm_number) !== ''
            && trim($patient->name) !== ''
            && trim($case->origin_ward) !== '';

        $hasPreopNurseSignoff = $case->signoffs->contains(function (CaseSignoff $signoff): bool {
            return $signoff->section === SignoffSection::PreOp
                && $signoff->kind === SignoffKind::Signoff
                && $signoff->revoked_at === null
                && in_array($signoff->role_label, ['perawat', 'perawat_sirkuler', 'perawat_instrumen', 'perawat_anestesi'], true);
        });

        $teamAndRoomAssigned = $case->hasRoomAndTeamAssigned();

        $preopAssessmentComplete = $case->readinessFlag('preop_assessment_completed');
        if (! $preopAssessmentComplete && $case->preopAssessment !== null) {
            $assessment = $case->preopAssessment;
            $hasVital = $case->vitalSigns->contains(fn (VitalSign $v): bool => $v->phase === VitalSignPhase::Pre);
            $preopAssessmentComplete = $assessment->chief_complaint !== null
                && $assessment->chief_complaint !== ''
                && $hasVital;
        }

        $preopChecklistReady = $case->readinessFlag('preop_checklist_ready');
        if (! $preopChecklistReady && $case->preopAssessment !== null) {
            $preopChecklistReady = $case->preopAssessment->readiness_status === ReadinessStatus::Siap;
        }

        $instrumentChecks = $case->instrumentChecks;
        if ($instrumentChecks->isNotEmpty()) {
            $instrumentCheckComplete = $instrumentChecks->every(
                fn (InstrumentCheck $check): bool => $check->isResolved()
            );
        } else {
            $instrumentCheckComplete = $case->readinessFlag('instrument_check_completed');
        }

        $sscSignIn = $case->sscSignIn;
        if ($sscSignIn !== null) {
            $signInComplete = $sscSignIn->status === SscStatus::Selesai
                && (! $sscSignIn->hasProblematicItems() || trim((string) $sscSignIn->notes_followup) !== '');
        } else {
            $signInComplete = $case->readinessFlag('sign_in_completed');
        }

        return [
            [
                'label' => 'Identitas pasien dan data dasar kasus lengkap',
                'met' => $identityComplete,
            ],
            [
                'label' => 'Asesmen pra-operasi tersimpan (riwayat, alergi, puasa, TTV)',
                'met' => $preopAssessmentComplete,
            ],
            [
                'label' => 'Checklist persiapan pra-operasi terisi dan status kesiapan Siap',
                'met' => $preopChecklistReady,
            ],
            [
                'label' => 'Pengesahan pra-operasi oleh perawat tercatat',
                'met' => $hasPreopNurseSignoff,
            ],
            [
                'label' => 'Ruang operasi dan seluruh tim (5 petugas) ditetapkan',
                'met' => $teamAndRoomAssigned,
            ],
            [
                'label' => 'Cek ketersediaan dan kesterilan alat selesai',
                'met' => $instrumentCheckComplete,
            ],
            [
                'label' => 'SSC Sign In berstatus Selesai',
                'met' => $signInComplete,
            ],
        ];
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    private function intraOpRequirements(SurgeryCase $case): array
    {
        return [
            [
                'label' => 'Waktu masuk kamar operasi harus diisi',
                'met' => $case->entered_or_at !== null,
            ],
        ];
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    private function pascaOpRequirements(SurgeryCase $case): array
    {
        $isNeuro = $case->surgeryProfile() === SurgeryProfile::BedahSaraf;
        $closingTime = $isNeuro ? ($case->anesthesia_end_at ?? $case->left_or_at) : $case->left_or_at;
        $timesValid = $case->entered_or_at !== null
            && $case->surgery_end_at !== null
            && $closingTime !== null
            && $case->entered_or_at->lte($case->surgery_end_at)
            && $case->surgery_end_at->lte($closingTime);

        $activeIntraopLabels = $case->signoffs
            ->filter(fn (CaseSignoff $s): bool => $s->section === SignoffSection::IntraOp && $s->kind === SignoffKind::Signoff && $s->revoked_at === null)
            ->pluck('role_label')
            ->all();

        $requiredLabels = ['perawat_sirkuler', 'perawat_instrumen', 'dokter_anestesi', 'dokter_operator'];
        $allFourSigned = count(array_intersect($requiredLabels, $activeIntraopLabels)) === count($requiredLabels);

        $counts = $case->instrumentCounts;
        $intraopRecord = $case->intraopRecord;
        $requiredItems = InstrumentCountItem::casesForProfile($case->surgeryProfile());
        if ($counts->isNotEmpty() || $intraopRecord?->count_verification !== null) {
            $allRequiredFinal = collect($requiredItems)->every(function (InstrumentCountItem $enumItem) use ($counts): bool {
                $row = $counts->firstWhere('item', $enumItem);

                return $row instanceof InstrumentCount && $row->final_count !== null;
            });
            $hasTs = $counts->contains(fn (InstrumentCount $c): bool => $c->status === InstrumentCountStatus::TidakSesuai);
            $verification = $intraopRecord !== null ? $intraopRecord->count_verification : null;
            $discrepancyActions = $intraopRecord !== null ? ($intraopRecord->count_discrepancy_actions ?? []) : [];

            $finalCountComplete = $allRequiredFinal
                && $verification !== null
                && (
                    (! $hasTs && $verification === CountVerification::LengkapSesuai)
                    || ($verification === CountVerification::TidakSesuai && $discrepancyActions !== [])
                );
        } else {
            $finalCountComplete = $case->readinessFlag('final_count_completed');
        }

        $signOut = $case->sscSignOut;
        if ($signOut !== null) {
            $signOutVerified = $signOut->status === SignOutStatus::Terverifikasi;
        } else {
            $signOutVerified = $case->readinessFlag('sign_out_verified');
        }

        $timeRequirementLabel = $isNeuro
            ? 'Waktu masuk OK, selesai operasi, dan selesai anestesi terisi dengan urutan kronologi menaik'
            : 'Waktu selesai operasi dan keluar kamar operasi terisi dengan urutan kronologi menaik';

        return [
            [
                'label' => $timeRequirementLabel,
                'met' => $timesValid,
            ],
            [
                'label' => 'Hitungan akhir instrumen, kasa, dan jarum tercatat',
                'met' => $finalCountComplete,
            ],
            [
                'label' => 'SSC Sign Out berstatus Terverifikasi',
                'met' => $signOutVerified,
            ],
            [
                'label' => 'Pengesahan intra-operasi oleh 4 pihak (sirkuler, instrumen, dokter anestesi, dokter operator) lengkap',
                'met' => $allFourSigned,
            ],
        ];
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    private function serahTerimaRequirements(SurgeryCase $case): array
    {
        $hasObservation = $case->postopObservations->isNotEmpty()
            || $case->readinessFlag('postop_observation_completed');

        $summary = $case->postopSummary;
        if ($summary !== null) {
            $preTransferRecorded = $summary->ready_to_transfer
                && $summary->pre_transfer_condition_notes !== null
                && trim($summary->pre_transfer_condition_notes) !== '';
        } else {
            $preTransferRecorded = $case->readinessFlag('pre_transfer_condition_recorded');
        }

        return [
            [
                'label' => 'Minimal 1 observasi pasca-operasi lengkap tercatat',
                'met' => $hasObservation,
            ],
            [
                'label' => 'Kondisi pasien sebelum dipindahkan tercatat',
                'met' => $preTransferRecorded,
            ],
        ];
    }

    /**
     * @return list<array{label: string, met: bool}>
     */
    private function selesaiRequirements(SurgeryCase $case): array
    {
        $handover = $case->handover;
        if ($handover !== null) {
            $handoverComplete = trim($handover->received_by_name) !== '';
        } else {
            $handoverComplete = $case->readinessFlag('handover_completed');
        }

        return [
            [
                'label' => 'Form serah terima pasien lengkap',
                'met' => $handoverComplete,
            ],
        ];
    }
}
