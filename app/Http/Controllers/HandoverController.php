<?php

namespace App\Http\Controllers;

use App\Actions\CompleteHandover;
use App\Enums\HandoverDestination;
use App\Http\Requests\Handover\SaveHandoverRequest;
use App\Models\SurgeryCase;
use App\Services\HandoverSummaryBuilder;
use App\Support\SurgeryCasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HandoverController extends Controller
{
    public function __construct(
        private readonly SurgeryCasePresenter $presenter,
        private readonly HandoverSummaryBuilder $summaryBuilder,
    ) {}

    public function show(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('view', $surgeryCase);

        $surgeryCase->loadMissing([
            'patient',
            'medicalDiagnosis',
            'surgeryType',
            'anesthesiaType',
            'postopObservations',
            'postopSummary',
            'handover.handedBy',
        ]);

        $handover = $surgeryCase->handover;
        $summary = $surgeryCase->postopSummary;
        $canHandoverNow = $surgeryCase->postopObservations->isNotEmpty()
            && $summary !== null
            && $summary->ready_to_transfer
            && $summary->pre_transfer_condition_notes !== null
            && trim($summary->pre_transfer_condition_notes) !== '';

        $liveSummary = $this->summaryBuilder->build(
            $surgeryCase,
            $handover?->ongoing_issues,
            $handover?->follow_up_instructions,
        );

        $displaySummary = ($handover !== null && is_array($handover->summary_snapshot) && $handover->summary_snapshot !== [])
            ? $handover->summary_snapshot
            : $liveSummary;

        $summaryItems = [];
        foreach (HandoverSummaryBuilder::itemLabels() as $key => $label) {
            $summaryItems[] = [
                'key' => $key,
                'label' => $label,
                'value' => $displaySummary[$key] ?? '-',
            ];
        }

        $user = $request->user();

        return Inertia::render('cases/handover', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'canHandoverNow' => $canHandoverNow,
            'summaryItems' => $summaryItems,
            'handover' => $handover === null ? [
                'id' => null,
                'destination' => HandoverDestination::Pacu->value,
                'destination_detail' => '',
                'documents_complete' => true,
                'handed_by_name' => $user !== null ? $user->name : '-',
                'received_by_name' => '',
                'handed_over_at' => now('Asia/Jakarta')->format('Y-m-d\TH:i'),
                'ongoing_issues' => '',
                'follow_up_instructions' => '',
            ] : [
                'id' => $handover->id,
                'destination' => $handover->destination->value,
                'destination_detail' => $handover->destination_detail ?? '',
                'documents_complete' => $handover->documents_complete,
                'handed_by_name' => $handover->handedBy !== null ? $handover->handedBy->name : '-',
                'received_by_name' => $handover->received_by_name,
                'handed_over_at' => $handover->handed_over_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
                'ongoing_issues' => $handover->ongoing_issues ?? '',
                'follow_up_instructions' => $handover->follow_up_instructions ?? '',
            ],
            'options' => [
                'destinations' => array_map(fn (HandoverDestination $d): array => ['value' => $d->value, 'label' => $d->label()], HandoverDestination::cases()),
            ],
            'permissions' => [
                'canEdit' => $user !== null && $user->can('updateHandover', $surgeryCase),
            ],
        ]);
    }

    public function store(
        SaveHandoverRequest $request,
        SurgeryCase $surgeryCase,
        CompleteHandover $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $validated = $request->validated();
        $completeCase = (bool) ($validated['complete_case'] ?? false);

        $action->execute($surgeryCase, $validated, $user, $completeCase);

        return back()->with('success', $completeCase
            ? 'Serah terima pasien berhasil disimpan dan kasus operasi dinyatakan Selesai.'
            : 'Formulir serah terima pasien berhasil disimpan.');
    }
}
