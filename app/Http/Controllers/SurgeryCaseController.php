<?php

namespace App\Http\Controllers;

use App\Actions\AdvanceCaseStatus;
use App\Actions\CreateSurgeryCase;
use App\Enums\CaseStatus;
use App\Enums\Gender;
use App\Enums\Role;
use App\Http\Requests\Cases\AdvanceCaseStatusRequest;
use App\Http\Requests\Cases\StoreSurgeryCaseRequest;
use App\Http\Requests\Cases\UpdateSurgeryCaseRequest;
use App\Models\AnesthesiaType;
use App\Models\MedicalDiagnosis;
use App\Models\Patient;
use App\Models\Room;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use App\Support\SurgeryCasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SurgeryCaseController extends Controller
{
    public function __construct(
        private readonly SurgeryCasePresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SurgeryCase::class);

        $search = trim((string) $request->string('q'));
        $statusFilter = (string) $request->string('status');
        $dateFilter = (string) $request->string('date');
        $roomFilter = (string) $request->string('room_id');
        $surgeonFilter = (string) $request->string('surgeon_id');

        $cases = SurgeryCase::query()
            ->with(['patient', 'room', 'surgeon', 'medicalDiagnosis', 'surgeryType'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('case_number', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery->where('rm_number', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(CaseStatus::tryFrom($statusFilter) !== null, function ($query) use ($statusFilter): void {
                $query->where('status', $statusFilter);
            })
            ->when($dateFilter !== '', function ($query) use ($dateFilter): void {
                $query->whereDate('scheduled_at', $dateFilter);
            })
            ->when(is_numeric($roomFilter), function ($query) use ($roomFilter): void {
                $query->where('room_id', (int) $roomFilter);
            })
            ->when(is_numeric($surgeonFilter), function ($query) use ($surgeonFilter): void {
                $query->where('surgeon_id', (int) $surgeonFilter);
            })
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SurgeryCase $case): array => [
                'id' => $case->id,
                'case_number' => $case->case_number,
                'status' => $case->status->value,
                'status_label' => $case->status->label(),
                'scheduled_at' => $case->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'patient_name' => $case->patient !== null ? $case->patient->name : '-',
                'patient_rm' => $case->patient !== null ? $case->patient->rm_number : '-',
                'diagnosis' => $case->diagnosisDisplay(),
                'surgery_type' => $case->surgeryTypeDisplay(),
                'room_code' => $case->room?->code,
                'surgeon_name' => $case->surgeon?->name,
            ]);

        return Inertia::render('cases/index', [
            'cases' => $cases,
            'filters' => [
                'q' => $search,
                'status' => $statusFilter,
                'date' => $dateFilter,
                'room_id' => $roomFilter,
                'surgeon_id' => $surgeonFilter,
            ],
            'statuses' => array_map(
                fn (CaseStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                CaseStatus::cases(),
            ),
            'rooms' => Room::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'surgeons' => User::query()
                ->where('role', Role::DokterOperator)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'canCreate' => $request->user()?->can('create', SurgeryCase::class) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', SurgeryCase::class);

        $selectedPatient = null;
        if (is_numeric($request->query('patient_id'))) {
            $patient = Patient::query()->find((int) $request->query('patient_id'));
            if ($patient !== null) {
                $selectedPatient = [
                    'id' => $patient->id,
                    'rm_number' => $patient->rm_number,
                    'name' => $patient->name,
                    'birth_date' => $patient->birth_date->format('d M Y'),
                    'age_label' => $patient->ageLabel(),
                    'gender_label' => $patient->gender->label(),
                    'phone' => $patient->phone,
                ];
            }
        }

        return Inertia::render('cases/create', [
            'selectedPatient' => $selectedPatient,
            'genders' => array_map(
                fn (Gender $gender): array => ['value' => $gender->value, 'label' => $gender->label()],
                Gender::cases(),
            ),
            'medicalDiagnoses' => MedicalDiagnosis::query()->active()->orderBy('name')->get(['id', 'name']),
            'surgeryTypes' => SurgeryType::query()->active()->orderBy('name')->get(['id', 'name']),
            'anesthesiaTypes' => AnesthesiaType::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreSurgeryCaseRequest $request, CreateSurgeryCase $action): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $case = $action->execute($request->validated(), $user);

        return to_route('cases.show', $case)->with('success', 'Kasus operasi berhasil dibuat.');
    }

    public function show(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('view', $surgeryCase);

        $surgeryCase->loadMissing(['anesthesiaType', 'medicalDiagnosis', 'surgeryType', 'creator']);

        return Inertia::render('cases/show', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'caseDetail' => [
                'id' => $surgeryCase->id,
                'admitted_at_input' => $surgeryCase->admitted_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
                'scheduled_at_input' => $surgeryCase->scheduled_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
                'origin_ward' => $surgeryCase->origin_ward,
                'origin_class' => $surgeryCase->origin_class,
                'medical_diagnosis_id' => $surgeryCase->medical_diagnosis_id,
                'medical_diagnosis_other' => $surgeryCase->medical_diagnosis_other,
                'surgery_type_id' => $surgeryCase->surgery_type_id,
                'surgery_type_other' => $surgeryCase->surgery_type_other,
                'surgery_site' => $surgeryCase->surgery_site,
                'anesthesia_type_id' => $surgeryCase->anesthesia_type_id,
                'ready_at' => $surgeryCase->ready_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'entered_or_at' => $surgeryCase->entered_or_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'surgery_end_at' => $surgeryCase->surgery_end_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'left_or_at' => $surgeryCase->left_or_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'completed_at' => $surgeryCase->completed_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'created_by_name' => $surgeryCase->creator !== null ? $surgeryCase->creator->name : '-',
            ],
            'medicalDiagnoses' => MedicalDiagnosis::query()->active()->orderBy('name')->get(['id', 'name']),
            'surgeryTypes' => SurgeryType::query()->active()->orderBy('name')->get(['id', 'name']),
            'anesthesiaTypes' => AnesthesiaType::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'canUpdate' => $request->user()?->can('update', $surgeryCase) ?? false,
            'canAdvance' => $request->user()?->can('advanceStatus', $surgeryCase) ?? false,
        ]);
    }

    public function update(UpdateSurgeryCaseRequest $request, SurgeryCase $surgeryCase): RedirectResponse
    {
        $surgeryCase->update($request->validated());

        return back()->with('success', 'Informasi kasus operasi berhasil diperbarui.');
    }

    public function advanceStatus(
        AdvanceCaseStatusRequest $request,
        SurgeryCase $surgeryCase,
        AdvanceCaseStatus $action,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 403);

        $validated = $request->validated();
        $target = CaseStatus::from($validated['target_status']);

        $updated = $action->execute(
            $surgeryCase,
            $target,
            $user,
            $validated['reason'] ?? null,
        );

        return back()->with('success', 'Status kasus berhasil dilanjutkan ke '.$updated->status->label().'.');
    }
}
