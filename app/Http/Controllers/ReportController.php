<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use App\Services\CaseCompletenessCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly CaseCompletenessCalculator $completeness,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('reports.view');

        $now = now('Asia/Jakarta');
        $month = (int) ($request->integer('month') ?: $now->month);
        $year = (int) ($request->integer('year') ?: $now->year);

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }
        if ($year < 2020 || $year > 2100) {
            $year = $now->year;
        }

        $startOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $cases = SurgeryCase::query()
            ->with([
                'patient',
                'room',
                'medicalDiagnosis',
                'surgeryType',
                'anesthesiaType',
                'surgeon',
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
            ->whereBetween('scheduled_at', [
                $startOfMonth->copy()->timezone(is_string($tz = config('app.timezone')) && $tz !== '' ? $tz : 'Asia/Jakarta'),
                $endOfMonth->copy()->timezone(is_string($tz) && $tz !== '' ? $tz : 'Asia/Jakarta'),
            ])
            ->orderByDesc('scheduled_at')
            ->get();

        $statusCounts = [];
        foreach (CaseStatus::cases() as $st) {
            $statusCounts[] = [
                'status' => $st->value,
                'label' => $st->label(),
                'count' => $cases->where('status', $st)->count(),
            ];
        }

        $completenessTotal = 0;
        $sscIncompleteCount = 0;
        $pendingDoctorVerifyCount = 0;

        $caseRows = $cases->map(function (SurgeryCase $c) use (&$completenessTotal, &$sscIncompleteCount, &$pendingDoctorVerifyCount): array {
            $comp = $this->completeness->calculate($c);
            $sscInc = $this->completeness->hasIncompleteSsc($c);
            $pendingDoc = $this->completeness->hasPendingDoctorVerification($c);

            $completenessTotal += $comp['percentage'];
            if ($sscInc) {
                $sscIncompleteCount++;
            }
            if ($pendingDoc) {
                $pendingDoctorVerifyCount++;
            }

            return [
                'id' => $c->id,
                'case_number' => $c->case_number,
                'patient_name' => $c->patient !== null ? $c->patient->name : '-',
                'rm_number' => $c->patient !== null ? $c->patient->rm_number : '-',
                'scheduled_at' => $c->scheduled_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'room_code' => $c->room !== null ? $c->room->code : '-',
                'surgery_type' => $c->surgeryTypeDisplay(),
                'surgeon_name' => $c->surgeon !== null ? $c->surgeon->name : '-',
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'completeness_pct' => $comp['percentage'],
                'ssc_incomplete' => $sscInc,
                'pending_verification' => $pendingDoc,
            ];
        })->values()->all();

        $totalCases = $cases->count();
        $avgCompleteness = $totalCases > 0 ? (int) round($completenessTotal / $totalCases) : 0;

        $byRoom = $cases->groupBy(fn (SurgeryCase $c): string => $c->room !== null ? "{$c->room->code} - {$c->room->name}" : 'Belum ditetapkan')
            ->map(fn ($group, string $label): array => ['label' => $label, 'count' => $group->count()])
            ->values()
            ->all();

        $byAnesthesia = $cases->groupBy(fn (SurgeryCase $c): string => $c->anesthesiaType !== null ? $c->anesthesiaType->name : 'Belum diisi')
            ->map(fn ($group, string $label): array => ['label' => $label, 'count' => $group->count()])
            ->values()
            ->all();

        // Rekap 6 bulan terakhir (1 query tunggal, tanpa loop query)
        $trendStart = $startOfMonth->copy()->subMonths(5)->startOfMonth();
        $trendEnd = $endOfMonth->copy();

        $trendCases = SurgeryCase::query()
            ->whereBetween('scheduled_at', [
                $trendStart->copy()->timezone(is_string($tz) && $tz !== '' ? $tz : 'Asia/Jakarta'),
                $trendEnd->copy()->timezone(is_string($tz) && $tz !== '' ? $tz : 'Asia/Jakarta'),
            ])
            ->get(['scheduled_at', 'status']);

        $trendBuckets = [];
        for ($i = 5; $i >= 0; $i--) {
            $cursor = $startOfMonth->copy()->subMonths($i);
            $ym = $cursor->format('Y-m');
            $trendBuckets[$ym] = [
                'label' => $cursor->translatedFormat('M Y'),
                'total' => 0,
                'completed' => 0,
            ];
        }

        foreach ($trendCases as $tc) {
            $ym = $tc->scheduled_at->timezone('Asia/Jakarta')->format('Y-m');
            if (! isset($trendBuckets[$ym])) {
                continue;
            }
            $trendBuckets[$ym]['total']++;
            if ($tc->status === CaseStatus::Selesai) {
                $trendBuckets[$ym]['completed']++;
            }
        }

        $sixMonths = array_values($trendBuckets);

        return Inertia::render('reports/index', [
            'filters' => [
                'month' => $month,
                'year' => $year,
                'period_label' => $startOfMonth->translatedFormat('F Y'),
            ],
            'summary' => [
                'total_cases' => $totalCases,
                'completed_cases' => $cases->where('status', CaseStatus::Selesai)->count(),
                'average_completeness' => $avgCompleteness,
                'ssc_incomplete' => $sscIncompleteCount,
                'pending_doctor_verification' => $pendingDoctorVerifyCount,
            ],
            'statusCounts' => $statusCounts,
            'byRoom' => $byRoom,
            'byAnesthesia' => $byAnesthesia,
            'sixMonthTrend' => $sixMonths,
            'cases' => $caseRows,
        ]);
    }
}
