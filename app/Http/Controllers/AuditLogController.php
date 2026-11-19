<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Models\AuditActivity;
use App\Models\CaseNursingDiagnosis;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\Handover;
use App\Models\InstrumentCheck;
use App\Models\InstrumentCount;
use App\Models\IntraopRecord;
use App\Models\Medication;
use App\Models\NursingAction;
use App\Models\Patient;
use App\Models\PostopObservation;
use App\Models\PostopSummary;
use App\Models\PreopAssessment;
use App\Models\Specimen;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SupportingExam;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('audit.view');

        $caseId = $request->integer('case_id') ?: null;
        $userId = $request->integer('user_id') ?: null;
        $event = trim((string) $request->string('event'));
        $dateFrom = trim((string) $request->string('date_from'));
        $dateTo = trim((string) $request->string('date_to'));
        $search = trim((string) $request->string('q'));

        $logs = AuditActivity::query()
            ->with(['causer', 'surgeryCase'])
            ->when($caseId !== null, fn ($q) => $q->where('case_id', $caseId))
            ->when($userId !== null, fn ($q) => $q->where('causer_id', $userId))
            ->when(in_array($event, ['created', 'updated', 'deleted', 'restored'], true), fn ($q) => $q->where('event', $event))
            ->when($dateFrom !== '', function ($q) use ($dateFrom): void {
                $q->where('created_at', '>=', Carbon::parse($dateFrom, 'Asia/Jakarta')->startOfDay()->utc());
            })
            ->when($dateTo !== '', function ($q) use ($dateTo): void {
                $q->where('created_at', '<=', Carbon::parse($dateTo, 'Asia/Jakarta')->endOfDay()->utc());
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('properties', 'like', "%{$search}%")
                        ->orWhereHas('causer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('surgeryCase', fn ($sq) => $sq->where('case_number', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditActivity $activity): array => [
                'id' => $activity->id,
                'created_at' => $activity->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-',
                'case_id' => $activity->case_id,
                'case_number' => $activity->surgeryCase !== null ? $activity->surgeryCase->case_number : null,
                'causer_name' => $activity->causer?->getAttribute('name') ?? 'Sistem',
                'event' => $activity->event ?? 'updated',
                'event_label' => $this->eventLabel($activity->event),
                'module_label' => $this->moduleLabel($activity->subject_type),
                'reason' => $activity->reason,
                'ip_address' => $activity->ip_address ?? '-',
                'changes' => $this->formatChanges($activity),
            ]);

        return Inertia::render('audit/index', [
            'logs' => $logs,
            'filters' => [
                'case_id' => $caseId !== null ? (string) $caseId : '',
                'user_id' => $userId !== null ? (string) $userId : '',
                'event' => $event,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'q' => $search,
            ],
            'options' => [
                'cases' => SurgeryCase::query()->orderByDesc('id')->limit(100)->get(['id', 'case_number']),
                'users' => User::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    private function eventLabel(?string $event): string
    {
        return match ($event) {
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            default => $event ?? 'Aktivitas',
        };
    }

    private function moduleLabel(?string $subjectType): string
    {
        return match ($subjectType) {
            SurgeryCase::class => 'Kasus Operasi',
            CaseSignoff::class => 'Pengesahan / Verifikasi',
            Patient::class => 'Pasien',
            PreopAssessment::class => 'Asesmen Pra-Op',
            SupportingExam::class => 'Pemeriksaan Penunjang',
            VitalSign::class => 'Tanda Vital',
            CaseNursingDiagnosis::class => 'Diagnosis Keperawatan',
            InstrumentCheck::class => 'Cek Alat Operasi',
            SscSignIn::class => 'SSC Sign In',
            SscTimeOut::class => 'SSC Time Out',
            SscSignOut::class => 'SSC Sign Out',
            IntraopRecord::class => 'Catatan Intra-Op',
            Medication::class => 'Obat Intra-Op',
            FluidRecord::class => 'Cairan',
            NursingAction::class => 'Tindakan Keperawatan',
            InstrumentCount::class => 'Hitung Instrumen',
            Specimen::class => 'Spesimen Operasi',
            PostopObservation::class => 'Observasi Pasca-Op',
            PostopSummary::class => 'Kondisi Sebelum Pindah',
            Handover::class => 'Serah Terima Pasien',
            User::class => 'Pengguna',
            default => class_basename((string) $subjectType) ?: 'Sistem',
        };
    }

    /**
     * @return list<array{field: string, old: string|null, new: string|null}>
     */
    private function formatChanges(AuditActivity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $attributes = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];

        $keys = array_values(array_unique(array_merge(array_keys($attributes), array_keys($old))));
        $formatted = [];

        foreach ($keys as $key) {
            $formatted[] = [
                'field' => (string) $key,
                'old' => array_key_exists($key, $old) ? $this->stringify((string) $key, $old[$key]) : null,
                'new' => array_key_exists($key, $attributes) ? $this->stringify((string) $key, $attributes[$key]) : null,
            ];
        }

        return $formatted;
    }

    private function stringify(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($key === 'status' && is_string($value)) {
            return CaseStatus::tryFrom($value)?->label() ?? $value;
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: null;
        }

        return (string) $value;
    }
}
