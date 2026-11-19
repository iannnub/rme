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
use App\Models\VitalSign;
use App\Support\SurgeryCasePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SurgeryCaseHistoryController extends Controller
{
    public function __construct(
        private readonly SurgeryCasePresenter $presenter,
    ) {}

    public function index(Request $request, SurgeryCase $surgeryCase): Response
    {
        $this->authorize('viewHistory', $surgeryCase);

        $eventFilter = (string) $request->string('event');
        $search = trim((string) $request->string('q'));

        $logs = AuditActivity::query()
            ->with('causer')
            ->where('case_id', $surgeryCase->id)
            ->when(in_array($eventFilter, ['created', 'updated', 'deleted', 'restored'], true), function ($query) use ($eventFilter): void {
                $query->where('event', $eventFilter);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('description', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('properties', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($causerQuery) use ($search): void {
                            $causerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditActivity $activity): array => [
                'id' => $activity->id,
                'created_at' => $activity->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-',
                'causer_name' => $activity->causer?->getAttribute('name') ?? 'Sistem',
                'event' => $activity->event ?? 'updated',
                'event_label' => $this->eventLabel($activity->event),
                'module_label' => $this->moduleLabel($activity->subject_type),
                'reason' => $activity->reason,
                'changes' => $this->formatChanges($activity),
            ]);

        return Inertia::render('cases/history', [
            'caseHeader' => $this->presenter->header($surgeryCase),
            'logs' => $logs,
            'filters' => [
                'event' => $eventFilter,
                'q' => $search,
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
            CaseSignoff::class => 'Pengesahan',
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
            FluidRecord::class => 'Cairan Intra-Op',
            NursingAction::class => 'Tindakan Keperawatan',
            InstrumentCount::class => 'Hitung Instrumen',
            Specimen::class => 'Spesimen Operasi',
            PostopObservation::class => 'Observasi Pasca-Op',
            PostopSummary::class => 'Kondisi Sebelum Pindah',
            Handover::class => 'Serah Terima Pasien',
            default => class_basename((string) $subjectType) ?: 'Klinis',
        };
    }

    /**
     * @return list<array{field: string, label: string, old: string|null, new: string|null}>
     */
    private function formatChanges(AuditActivity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $attributes = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];

        $keys = array_values(array_unique(array_merge(array_keys($attributes), array_keys($old))));
        $formatted = [];

        foreach ($keys as $key) {
            $oldVal = array_key_exists($key, $old) ? $this->stringifyValue($key, $old[$key]) : null;
            $newVal = array_key_exists($key, $attributes) ? $this->stringifyValue($key, $attributes[$key]) : null;

            $formatted[] = [
                'field' => (string) $key,
                'label' => $this->fieldLabel((string) $key),
                'old' => $oldVal,
                'new' => $newVal,
            ];
        }

        return $formatted;
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'case_number' => 'Nomor Kasus',
            'status' => 'Status Kasus',
            'admitted_at' => 'Tanggal Masuk',
            'origin_ward' => 'Ruang Asal',
            'origin_class' => 'Kelas Perawatan',
            'scheduled_at' => 'Jadwal Operasi',
            'medical_diagnosis_id' => 'ID Diagnosis Medis',
            'medical_diagnosis_other' => 'Diagnosis Medis Lainnya',
            'surgery_type_id' => 'ID Jenis Operasi',
            'surgery_type_other' => 'Jenis Operasi Lainnya',
            'surgery_site' => 'Lokasi Operasi',
            'anesthesia_type_id' => 'ID Jenis Anestesi',
            'room_id' => 'ID Ruang Operasi',
            'surgeon_id' => 'ID Dokter Operator',
            'anesthesiologist_id' => 'ID Dokter Anestesi',
            'circulating_nurse_id' => 'ID Perawat Sirkuler',
            'scrub_nurse_id' => 'ID Perawat Instrumen',
            'anesthesia_nurse_id' => 'ID Perawat Anestesi',
            'ready_at' => 'Siap Operasi Pada',
            'entered_or_at' => 'Masuk Kamar Operasi',
            'anesthesia_start_at' => 'Mulai Anestesi',
            'incision_at' => 'Mulai Insisi',
            'surgery_end_at' => 'Selesai Operasi',
            'left_or_at' => 'Keluar Kamar Operasi',
            'completed_at' => 'Selesai Pada',
            'section' => 'Bagian Pengesahan',
            'kind' => 'Jenis Pengesahan',
            'role_label' => 'Peran Pengesah',
            'signed_at' => 'Waktu Pengesahan',
            'revoked_at' => 'Waktu Pencabutan',
            'revoke_reason' => 'Alasan Pencabutan',
            'stage_readiness_flags' => 'Indikator Kesiapan Tahap',
            'team_assigned_at' => 'Waktu Penetapan Tim',
            'instrument_id' => 'ID Alat Operasi',
            'is_available' => 'Alat Tersedia',
            'is_sterile' => 'Alat Steril',
            'is_functional' => 'Alat Berfungsi',
            'is_rusty' => 'Alat Berkarat',
            'note' => 'Catatan Alat',
            'identity_confirmed' => 'Konfirmasi Identitas (Sign In)',
            'procedure_confirmed' => 'Konfirmasi Prosedur (Sign In)',
            'site_confirmed' => 'Konfirmasi Lokasi (Sign In)',
            'consent_surgery' => 'Consent Operasi (Sign In)',
            'consent_anesthesia' => 'Consent Anestesi (Sign In)',
            'site_marking' => 'Penandaan Lokasi (Sign In)',
            'readiness' => 'Kesiapan Alat & Obat Anestesi',
            'pulse_ox_attached' => 'Pulse Oximeter Terpasang',
            'pulse_ox_functional' => 'Pulse Oximeter Berfungsi',
            'difficult_airway_risk' => 'Risiko Jalan Napas Sulit',
            'aspiration_risk' => 'Risiko Aspirasi',
            'bleeding_risk' => 'Risiko Perdarahan',
            'iv_access' => 'Akses Intravena',
            'fluids_ready' => 'Kesiapan Cairan',
            'blood_ready' => 'Kesiapan Darah',
            'notes_followup' => 'Catatan Tindak Lanjut (Sign In)',
            default => $field,
        };
    }

    private function stringifyValue(string $key, mixed $value): ?string
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
