<?php

namespace App\Http\Controllers;

use App\Actions\SaveCasePreparation;
use App\Actions\SaveInstrumentChecks;
use App\Enums\CaseStatus;
use App\Enums\Role;
use App\Enums\SscStatus;
use App\Http\Requests\Preparation\SaveCasePreparationRequest;
use App\Http\Requests\Preparation\SaveInstrumentChecksRequest;
use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\Room;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\SurgeryCasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreparationController extends Controller
{
    public function __construct(
        private readonly SurgeryCasePresenter $presenter,
        private readonly SaveCasePreparation $savePreparation,
    ) {}

    public function show(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('view', $surgeryCase);

        $surgeryCase->loadMissing([
            'room',
            'surgeon',
            'anesthesiologist',
            'circulatingNurse',
            'scrubNurse',
            'anesthesiaNurse',
            'instrumentChecks.instrument',
            'instrumentChecks.checker',
            'sscSignIn',
        ]);

        $activeIntraOpRooms = SurgeryCase::query()
            ->with(['patient', 'room'])
            ->where('id', '!=', $surgeryCase->id)
            ->whereNotNull('room_id')
            ->where('status', CaseStatus::IntraOp)
            ->get()
            ->mapWithKeys(fn (SurgeryCase $c): array => [
                (string) $c->room_id => [
                    'case_id' => $c->id,
                    'case_number' => $c->case_number,
                    'patient_name' => $c->patient !== null ? $c->patient->name : '-',
                    'room_code' => $c->room !== null ? $c->room->code : '-',
                ],
            ])
            ->all();

        $instrumentChecks = $surgeryCase->instrumentChecks;
        $instrumentCheckComplete = $instrumentChecks->isNotEmpty()
            && $instrumentChecks->every(fn (InstrumentCheck $check): bool => $check->isResolved());

        $sscSignIn = $surgeryCase->sscSignIn;
        $signInComplete = $sscSignIn !== null
            && $sscSignIn->status === SscStatus::Selesai
            && (! $sscSignIn->hasProblematicItems() || trim((string) $sscSignIn->notes_followup) !== '');

        $user = $request->user();

        return Inertia::render('cases/preparation', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'preparation' => [
                'room_id' => $surgeryCase->room_id,
                'surgeon_id' => $surgeryCase->surgeon_id,
                'anesthesiologist_id' => $surgeryCase->anesthesiologist_id,
                'circulating_nurse_id' => $surgeryCase->circulating_nurse_id,
                'scrub_nurse_id' => $surgeryCase->scrub_nurse_id,
                'anesthesia_nurse_id' => $surgeryCase->anesthesia_nurse_id,
                'team_assigned_at' => $surgeryCase->team_assigned_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ],
            'roomConflict' => $this->savePreparation->findActiveRoomConflict($surgeryCase),
            'activeIntraOpRooms' => $activeIntraOpRooms,
            'instrumentChecks' => $instrumentChecks->map(fn (InstrumentCheck $check): array => [
                'id' => $check->id,
                'instrument_id' => $check->instrument_id,
                'instrument_name' => $check->instrument !== null ? $check->instrument->name : '-',
                'instrument_category' => $check->instrument?->category,
                'instrument_description' => $check->instrument?->description,
                'is_available' => $check->is_available,
                'is_sterile' => $check->is_sterile,
                'is_functional' => $check->is_functional,
                'is_rusty' => $check->is_rusty,
                'note' => $check->note ?? '',
                'has_issue' => $check->hasIssue(),
                'checked_by_name' => $check->checker !== null ? $check->checker->name : '-',
                'checked_at' => $check->checked_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
            ])->values()->all(),
            'progress' => [
                'room_and_team' => $surgeryCase->hasRoomAndTeamAssigned(),
                'instrument_checks' => $instrumentCheckComplete,
                'sign_in' => $signInComplete,
            ],
            'options' => [
                'rooms' => Room::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
                'surgeons' => User::query()
                    ->where('role', Role::DokterOperator)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'sip']),
                'anesthesiologists' => User::query()
                    ->where('role', Role::DokterAnestesi)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'sip']),
                'nurses' => User::query()
                    ->where('role', Role::Perawat)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'nurse_type', 'nip'])
                    ->map(fn (User $n): array => [
                        'id' => $n->id,
                        'name' => $n->name,
                        'nurse_type' => $n->nurse_type?->value,
                        'nurse_type_label' => $n->nurse_type?->label() ?? 'Perawat',
                    ])
                    ->values()
                    ->all(),
                'instruments' => Instrument::query()
                    ->active()
                    ->forProfile($surgeryCase->surgeryProfile())
                    ->orderBy('category')
                    ->orderBy('id')
                    ->get(['id', 'name', 'category', 'surgery_profile', 'description']),
            ],
            'permissions' => [
                'canEdit' => $user !== null && $user->can('managePreparation', $surgeryCase),
                'canAdvance' => $user !== null && $user->can('advanceStatus', $surgeryCase),
            ],
        ]);
    }

    public function updateTeam(
        SaveCasePreparationRequest $request,
        SurgeryCase $surgeryCase,
        SaveCasePreparation $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $result = $action->execute($surgeryCase, $request->validated(), $user);

        if ($result['room_conflict'] !== null) {
            $conflict = $result['room_conflict'];

            return back()
                ->with('success', 'Penetapan ruang operasi dan tim berhasil disimpan.')
                ->with(
                    'warning',
                    "Perhatian: Ruang {$conflict['room_code']} saat ini sedang dipakai kasus {$conflict['case_number']} ({$conflict['patient_name']}) yang berstatus Intra-Operasi."
                );
        }

        return back()->with('success', 'Penetapan ruang operasi dan tim berhasil disimpan.');
    }

    public function updateInstruments(
        SaveInstrumentChecksRequest $request,
        SurgeryCase $surgeryCase,
        SaveInstrumentChecks $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $validated = $request->validated();
        /** @var list<array<string, mixed>> $items */
        $items = $validated['items'];

        $action->execute($surgeryCase, $items, $user);

        return back()->with('success', 'Daftar cek ketersediaan dan kesterilan alat berhasil disimpan.');
    }
}
