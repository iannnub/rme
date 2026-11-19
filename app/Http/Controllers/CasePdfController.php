<?php

namespace App\Http\Controllers;

use App\Models\SurgeryCase;
use App\Services\PdfExporter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CasePdfController extends Controller
{
    public function __construct(
        private readonly PdfExporter $exporter,
    ) {}

    public function full(SurgeryCase $surgeryCase): Response
    {
        Gate::authorize('clinical.export_pdf');
        $this->authorize('view', $surgeryCase);

        $pdf = $this->exporter->fullCaseRecord($surgeryCase);

        return $pdf->download("rekam-keperawatan-{$surgeryCase->case_number}.pdf");
    }

    public function handover(SurgeryCase $surgeryCase): Response
    {
        Gate::authorize('clinical.export_pdf');
        $this->authorize('view', $surgeryCase);

        $pdf = $this->exporter->handoverSheet($surgeryCase);

        return $pdf->download("serah-terima-{$surgeryCase->case_number}.pdf");
    }
}
