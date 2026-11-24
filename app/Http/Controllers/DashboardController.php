<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Models\AnesthesiaType;
use App\Models\CaseSignoff;
use App\Models\Instrument;
use App\Models\MedicalDiagnosis;
use App\Models\Room;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use App\Services\CaseCompletenessCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CaseCompletenessCalculator $completeness,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $adminStats = null;
        $nurseDashboard = null;
        $doctorDashboard = null;
        $headNurseDashboard = null;

        if ($user !== null && $user->isAdmin()) {
            $adminStats = [
                'users_total' => User::query()->count(),
                'users_active' => User::query()->where('is_active', true)->count(),
                'users_inactive' => User::query()->where('is_active', false)->count(),
                'rooms_active' => Room::query()->active()->count(),
                'instruments_active' => Instrument::query()->active()->count(),
                'surgery_types_active' => SurgeryType::query()->active()->count(),
                'anesthesia_types_active' => AnesthesiaType::query()->active()->count(),
                'medical_diagnoses_active' => MedicalDiagnosis::query()->active()->count(),
            ];
        } elseif ($user !== null && $user->isPerawat()) {
            $nurseDashboard = $this->buildNurseDashboard($user);
        } elseif ($user !== null && $user->isDokter()) {
            $doctorDashboard = $this->buildDoctorDashboard($user);
        } elseif ($user !== null && $user->isKepalaRuangan()) {
            $headNurseDashboard = $this->buildHeadNurseDashboard();
        }

        return Inertia::render('dashboard', [
            'adminStats' => $adminStats,
            'nurseDashboard' => $nurseDashboard,
            'doctorDashboard' => $doctorDashboard,
            'headNurseDashboard' => $headNurseDashboard,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNurseDashboard(User $user): array
    {
        $tz = config('app.timezone');
        $appTimezone = is_string($tz) && $tz !== '' ? $tz : 'Asia/Jakarta';
        $startToday = now('Asia/Jakarta')->startOfDay()->timezone($appTimezone);
        $endToday = now('Asia/Jakarta')->endOfDay()->timezone($appTimezone);

        $myActiveCases = SurgeryCase::query()
            ->with(['patient', 'room', 'sscSignIn', 'sscTimeOut', 'sscSignOut', 'signoffs'])
            ->where(function ($q) use ($user): void {
                $q->where('circulating_nurse_id', $user->id)
                    ->orWhere('scrub_nurse_id', $user->id)
                    ->orWhere('anesthesia_nurse_id', $user->id)
                    ->orWhere('created_by', $user->id);
            })
            ->orderByDesc('scheduled_at')
            ->get();

        $myTodayCases = $myActiveCases->filter(
            fn (SurgeryCase $c): bool => $c->scheduled_at->between($startToday, $endToday)
        );

        $pendingTasks = $myActiveCases
            ->filter(fn (SurgeryCase $c): bool => $c->status !== CaseStatus::Selesai)
            ->map(function (SurgeryCase $c): array {
                $tasks = [];
                if ($this->completeness->hasIncompleteSsc($c)) {
                    $tasks[] = 'SSC belum selesai';
                }
                $hasPreopSign = $c->signoffs->contains(
                    fn (CaseSignoff $s): bool => $s->section === SignoffSection::PreOp && $s->kind === SignoffKind::Signoff && $s->revoked_at === null
                );
                if (! $hasPreopSign) {
                    $tasks[] = 'Pengesahan Pre-Op belum ada';
                }

                return [
                    'id' => $c->id,
                    'case_number' => $c->case_number,
                    'patient_name' => $c->patient !== null ? $c->patient->name : '-',
                    'status' => $c->status->value,
                    'status_label' => $c->status->label(),
                    'scheduled_at' => $c->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                    'room_code' => $c->room !== null ? $c->room->code : '-',
                    'pending_items' => $tasks,
                ];
            })
            ->filter(fn (array $row): bool => $row['pending_items'] !== [])
            ->values()
            ->all();

        return [
            'stats' => [
                'today_total' => SurgeryCase::query()->whereBetween('scheduled_at', [$startToday, $endToday])->count(),
                'my_today_count' => $myTodayCases->count(),
                'my_active_count' => $myActiveCases->where('status', '!=', CaseStatus::Selesai)->count(),
                'pending_tasks_count' => count($pendingTasks),
            ],
            'my_today_cases' => $myTodayCases->map(fn (SurgeryCase $c): array => [
                'id' => $c->id,
                'case_number' => $c->case_number,
                'patient_name' => $c->patient !== null ? $c->patient->name : '-',
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'scheduled_at' => $c->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'room_code' => $c->room !== null ? $c->room->code : '-',
            ])->values()->all(),
            'pending_tasks' => array_slice($pendingTasks, 0, 10),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDoctorDashboard(User $user): array
    {
        $myCases = SurgeryCase::query()
            ->with(['patient', 'room', 'signoffs'])
            ->where(function ($q) use ($user): void {
                $q->where('surgeon_id', $user->id)
                    ->orWhere('anesthesiologist_id', $user->id);
            })
            ->orderByDesc('scheduled_at')
            ->get();

        $waitingList = $myCases->map(function (SurgeryCase $c) use ($user): array {
            $activeSignoffs = $c->signoffs->whereNull('revoked_at');
            $needs = [];

            $hasMyVerification = $activeSignoffs->contains(
                fn (CaseSignoff $s): bool => $s->kind === SignoffKind::Verification && $s->user_id === $user->id
            );
            if (! $hasMyVerification && $c->status !== CaseStatus::Selesai) {
                $needs[] = 'Menunggu verifikasi dokter';
            }

            $myIntraRole = $c->surgeon_id === $user->id ? 'dokter_operator' : 'dokter_anestesi';
            $hasMyIntraSignoff = $activeSignoffs->contains(
                fn (CaseSignoff $s): bool => $s->section === SignoffSection::IntraOp
                    && $s->kind === SignoffKind::Signoff
                    && $s->role_label === $myIntraRole
            );
            if ($c->status === CaseStatus::IntraOp && ! $hasMyIntraSignoff) {
                $needs[] = 'Menunggu pengesahan Intra-Op';
            }

            return [
                'id' => $c->id,
                'case_number' => $c->case_number,
                'patient_name' => $c->patient !== null ? $c->patient->name : '-',
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'scheduled_at' => $c->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'room_code' => $c->room !== null ? $c->room->code : '-',
                'needs' => $needs,
            ];
        })->filter(fn (array $row): bool => $row['needs'] !== [])->values()->all();

        return [
            'stats' => [
                'my_cases_total' => $myCases->count(),
                'my_active_cases' => $myCases->where('status', '!=', CaseStatus::Selesai)->count(),
                'waiting_verification_count' => count($waitingList),
                'completed_cases' => $myCases->where('status', CaseStatus::Selesai)->count(),
            ],
            'waiting_cases' => array_slice($waitingList, 0, 10),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHeadNurseDashboard(): array
    {
        $allCases = SurgeryCase::query()
            ->with([
                'patient',
                'room',
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
            ])
            ->orderByDesc('scheduled_at')
            ->get();

        $statusCounts = [];
        foreach (CaseStatus::cases() as $st) {
            $statusCounts[$st->value] = [
                'status' => $st->value,
                'label' => $st->label(),
                'count' => $allCases->where('status', $st)->count(),
            ];
        }

        $totalPct = 0;
        $sscIncomplete = 0;
        $pendingDoctor = 0;

        $caseRows = $allCases->map(function (SurgeryCase $c) use (&$totalPct, &$sscIncomplete, &$pendingDoctor): array {
            $comp = $this->completeness->calculate($c);
            $sscInc = $this->completeness->hasIncompleteSsc($c);
            $pendDoc = $this->completeness->hasPendingDoctorVerification($c);

            $totalPct += $comp['percentage'];
            if ($sscInc) {
                $sscIncomplete++;
            }
            if ($pendDoc) {
                $pendingDoctor++;
            }

            return [
                'id' => $c->id,
                'case_number' => $c->case_number,
                'patient_name' => $c->patient !== null ? $c->patient->name : '-',
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'scheduled_at' => $c->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'room_code' => $c->room !== null ? $c->room->code : '-',
                'completeness_pct' => $comp['percentage'],
                'ssc_incomplete' => $sscInc,
                'pending_verification' => $pendDoc,
            ];
        })->values()->all();

        $totalCases = $allCases->count();

        return [
            'stats' => [
                'total_cases' => $totalCases,
                'average_completeness' => $totalCases > 0 ? (int) round($totalPct / $totalCases) : 0,
                'ssc_incomplete' => $sscIncomplete,
                'pending_doctor_verification' => $pendingDoctor,
            ],
            'status_counts' => array_values($statusCounts),
            'recent_cases' => array_slice($caseRows, 0, 15),
        ];
    }
}
