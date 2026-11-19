<?php

namespace App\Services;

use App\Enums\FluidDirection;
use App\Enums\NursingPhase;
use App\Enums\VitalSignPhase;
use App\Models\Setting;
use App\Models\SurgeryCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;

class PdfExporter
{
    public function __construct(
        private readonly HandoverSummaryBuilder $handoverSummaryBuilder,
        private readonly CaseCompletenessCalculator $completenessCalculator,
    ) {}

    public function fullCaseRecord(SurgeryCase $case): DomPdfWrapper
    {
        $data = $this->fullCaseViewData($case);

        return Pdf::loadView('pdf.case-full', $data)
            ->setPaper('a4', 'portrait');
    }

    public function handoverSheet(SurgeryCase $case): DomPdfWrapper
    {
        $data = $this->handoverViewData($case);

        return Pdf::loadView('pdf.case-handover', $data)
            ->setPaper('a4', 'portrait');
    }

    /**
     * @return array<string, mixed>
     */
    public function fullCaseViewData(SurgeryCase $case): array
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
            'preopAssessment',
            'supportingExams',
            'vitalSigns',
            'instrumentChecks.instrument',
            'instrumentChecks.checker',
            'sscSignIn.filler',
            'sscTimeOut.filler',
            'sscSignOut.filler',
            'intraopRecord',
            'medications.creator',
            'fluidRecords',
            'nursingActions.creator',
            'instrumentCounts',
            'specimens',
            'postopObservations.creator',
            'postopSummary.assessor',
            'handover.handedBy',
            'caseNursingDiagnoses.nursingDiagnosis.slkiCriteria',
            'caseNursingDiagnoses.interventionItems.sikiItem',
            'signoffs.user',
        ]);

        $intraFluids = $case->fluidRecords->where('phase', VitalSignPhase::Intra);

        $handover = $case->handover;
        $summarySnapshot = ($handover !== null && is_array($handover->summary_snapshot) && $handover->summary_snapshot !== [])
            ? $handover->summary_snapshot
            : $this->handoverSummaryBuilder->build($case, $handover?->ongoing_issues, $handover?->follow_up_instructions);

        return [
            'hospital' => Setting::hospitalProfile(),
            'printedAt' => now('Asia/Jakarta')->format('d M Y H:i').' WIB',
            'case' => $case,
            'completeness' => $this->completenessCalculator->calculate($case),
            'preVitals' => $case->vitalSigns->where('phase', VitalSignPhase::Pre)->sortBy('recorded_at')->values(),
            'intraVitals' => $case->vitalSigns->where('phase', VitalSignPhase::Intra)->sortBy('recorded_at')->values(),
            'postVitals' => $case->vitalSigns->where('phase', VitalSignPhase::Post)->sortBy('recorded_at')->values(),
            'intraTotalIntake' => (int) $intraFluids->where('direction', FluidDirection::Intake)->sum('volume_ml'),
            'intraTotalOutput' => (int) $intraFluids->where('direction', FluidDirection::Output)->sum('volume_ml'),
            'preDiagnoses' => $case->caseNursingDiagnoses->where('phase', NursingPhase::Pre)->values(),
            'intraDiagnoses' => $case->caseNursingDiagnoses->where('phase', NursingPhase::Intra)->values(),
            'postDiagnoses' => $case->caseNursingDiagnoses->where('phase', NursingPhase::Post)->values(),
            'handoverSummary' => $summarySnapshot,
            'handoverLabels' => HandoverSummaryBuilder::itemLabels(),
            'activeSignoffs' => $case->signoffs->whereNull('revoked_at')->sortBy('signed_at')->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handoverViewData(SurgeryCase $case): array
    {
        $case->loadMissing([
            'patient',
            'medicalDiagnosis',
            'surgeryType',
            'anesthesiaType',
            'room',
            'surgeon',
            'anesthesiologist',
            'handover.handedBy',
            'postopSummary.assessor',
        ]);

        $handover = $case->handover;
        $summarySnapshot = ($handover !== null && is_array($handover->summary_snapshot) && $handover->summary_snapshot !== [])
            ? $handover->summary_snapshot
            : $this->handoverSummaryBuilder->build($case, $handover?->ongoing_issues, $handover?->follow_up_instructions);

        return [
            'hospital' => Setting::hospitalProfile(),
            'printedAt' => now('Asia/Jakarta')->format('d M Y H:i').' WIB',
            'case' => $case,
            'handover' => $handover,
            'summaryItems' => $summarySnapshot,
            'summaryLabels' => HandoverSummaryBuilder::itemLabels(),
        ];
    }
}
