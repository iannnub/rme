<?php

use App\Enums\CaseStatus;
use App\Enums\CountVerification;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Enums\NurseType;
use App\Enums\ReadinessStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SscStatus;
use App\Enums\VitalSignPhase;
use App\Models\CaseSignoff;
use App\Models\Handover;
use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\InstrumentCount;
use App\Models\IntraopRecord;
use App\Models\Patient;
use App\Models\PostopObservation;
use App\Models\PostopSummary;
use App\Models\PreopAssessment;
use App\Models\Setting;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\CaseCompletenessCalculator;
use App\Services\PdfExporter;
use Inertia\Testing\AssertableInertia as Assert;

test('angka dashboard kepala ruangan, perawat, dan dokter sama persis dengan angka di database', function () {
    $kepala = User::factory()->kepalaRuangan()->create();
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $operator = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();

    // Kasus 1: Pre-Op, tanpa SSC, tanpa verifikasi dokter
    SurgeryCase::factory()->create([
        'status' => CaseStatus::PreOp,
        'scheduled_at' => now('Asia/Jakarta'),
        'circulating_nurse_id' => $perawat->id,
        'surgeon_id' => $operator->id,
        'anesthesiologist_id' => $anestesi->id,
        'created_by' => $perawat->id,
    ]);

    // Kasus 2: Intra-Op, ada verifikasi dokter pada pre-op
    $case2 = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::IntraOp)->create([
        'scheduled_at' => now('Asia/Jakarta'),
        'circulating_nurse_id' => $perawat->id,
        'surgeon_id' => $operator->id,
        'anesthesiologist_id' => $anestesi->id,
        'created_by' => $perawat->id,
    ]);
    CaseSignoff::factory()->create([
        'case_id' => $case2->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Verification,
        'role_label' => 'dokter_operator',
        'user_id' => $operator->id,
    ]);

    // Kasus 3: Selesai lengkap 100%
    $case3 = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::Selesai)->create([
        'scheduled_at' => now('Asia/Jakarta')->subDay(),
        'surgeon_id' => $operator->id,
        'anesthesiologist_id' => $anestesi->id,
        'entered_or_at' => now()->subHours(5),
        'surgery_end_at' => now()->subHours(4),
        'left_or_at' => now()->subHours(3),
        'created_by' => $perawat->id,
    ]);

    PreopAssessment::factory()->create([
        'case_id' => $case3->id,
        'chief_complaint' => 'Nyeri akut',
        'readiness_status' => ReadinessStatus::Siap,
        'created_by' => $perawat->id,
    ]);
    VitalSign::factory()->create(['case_id' => $case3->id, 'phase' => VitalSignPhase::Pre, 'created_by' => $perawat->id]);
    CaseSignoff::factory()->create([
        'case_id' => $case3->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Signoff,
        'role_label' => 'perawat',
        'user_id' => $perawat->id,
    ]);
    CaseSignoff::factory()->create([
        'case_id' => $case3->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Verification,
        'role_label' => 'dokter_operator',
        'user_id' => $operator->id,
    ]);
    InstrumentCheck::factory()->create([
        'case_id' => $case3->id,
        'instrument_id' => Instrument::factory()->create()->id,
        'checked_by' => $perawat->id,
    ]);
    SscSignIn::factory()->create(['case_id' => $case3->id, 'status' => SscStatus::Selesai, 'created_by' => $perawat->id]);
    SscTimeOut::factory()->create(['case_id' => $case3->id, 'status' => SscStatus::Selesai, 'created_by' => $perawat->id]);
    SscSignOut::factory()->create(['case_id' => $case3->id, 'status' => SignOutStatus::Terverifikasi, 'created_by' => $perawat->id]);
    IntraopRecord::factory()->create(['case_id' => $case3->id, 'count_verification' => CountVerification::LengkapSesuai, 'created_by' => $perawat->id]);
    foreach (InstrumentCountItem::cases() as $item) {
        InstrumentCount::factory()->create([
            'case_id' => $case3->id,
            'item' => $item,
            'initial_count' => 5,
            'additional_count' => 0,
            'final_count' => 5,
            'status' => InstrumentCountStatus::Sesuai,
            'created_by' => $perawat->id,
        ]);
    }
    foreach (['perawat_sirkuler', 'perawat_instrumen', 'dokter_anestesi', 'dokter_operator'] as $rl) {
        CaseSignoff::factory()->create([
            'case_id' => $case3->id,
            'section' => SignoffSection::IntraOp,
            'kind' => SignoffKind::Signoff,
            'role_label' => $rl,
            'user_id' => $perawat->id,
        ]);
    }
    PostopObservation::factory()->create(['case_id' => $case3->id, 'created_by' => $perawat->id]);
    PostopSummary::factory()->create(['case_id' => $case3->id, 'ready_to_transfer' => true, 'created_by' => $perawat->id]);
    Handover::factory()->create(['case_id' => $case3->id, 'handed_by_id' => $perawat->id, 'created_by' => $perawat->id]);

    // Cek kelengkapan kasus 3 = 100%
    expect(app(CaseCompletenessCalculator::class)->calculate($case3)['percentage'])->toBe(100);

    // Dashboard Kepala Ruangan: 3 total kasus, 2 SSC belum selesai (kasus 1 & 2), 1 belum diverifikasi dokter (kasus 1)
    $this->actingAs($kepala)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('headNurseDashboard.stats.total_cases', 3)
            ->where('headNurseDashboard.stats.ssc_incomplete', 2)
            ->where('headNurseDashboard.stats.pending_doctor_verification', 1)
        );

    // Dashboard Perawat: 2 kasus hari ini (kasus 1 & 2)
    $this->actingAs($perawat)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('nurseDashboard.stats.today_total', 2)
            ->where('nurseDashboard.stats.my_today_count', 2)
        );

    // Dashboard Dokter Operator: 3 kasus total, 1 selesai
    $this->actingAs($operator)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('doctorDashboard.stats.my_cases_total', 3)
            ->where('doctorDashboard.stats.completed_cases', 1)
        );
});

test('halaman laporan bulanan dan audit log global hanya dapat diakses oleh Kepala Ruangan dan mendukung filter', function () {
    $kepala = User::factory()->kepalaRuangan()->create();
    $perawat = User::factory()->perawat()->create();
    $operator = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();
    $admin = User::factory()->admin()->create();

    $case = SurgeryCase::factory()->create([
        'scheduled_at' => now('Asia/Jakarta'),
        'created_by' => $perawat->id,
    ]);

    foreach ([$perawat, $operator, $anestesi, $admin] as $blocked) {
        $this->actingAs($blocked)->get('/laporan')->assertForbidden();
        $this->actingAs($blocked)->get('/audit')->assertForbidden();
    }

    $now = now('Asia/Jakarta');
    $this->actingAs($kepala)
        ->get("/laporan?month={$now->month}&year={$now->year}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/index')
            ->where('summary.total_cases', 1)
        );

    $this->actingAs($kepala)
        ->get("/audit?case_id={$case->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit/index')
            ->where('filters.case_id', (string) $case->id)
        );
});

test('ekspor PDF rekam keperawatan lengkap dan lembar serah terima terunduh untuk peran klinis, diblokir untuk admin, dan memuat seluruh bagian', function () {
    Setting::setValue('hospital_name', 'RS Universitas Muhammadiyah Jember');
    Setting::setValue('hospital_address', 'Jl. Wolter Monginsidi No. 91, Jember');

    $perawat = User::factory()->perawat()->create();
    $operator = User::factory()->dokterOperator()->create();
    $kepala = User::factory()->kepalaRuangan()->create();
    $admin = User::factory()->admin()->create();

    $patient = Patient::factory()->create([
        'rm_number' => 'PDF-001',
        'name' => 'Pasien Uji PDF',
        'created_by' => $perawat->id,
    ]);

    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::Selesai)->create([
        'patient_id' => $patient->id,
        'surgeon_id' => $operator->id,
        'created_by' => $perawat->id,
    ]);

    PreopAssessment::factory()->create(['case_id' => $case->id, 'chief_complaint' => 'Nyeri perut kanan', 'created_by' => $perawat->id]);
    SscSignIn::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai, 'created_by' => $perawat->id]);
    SscTimeOut::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai, 'created_by' => $perawat->id]);
    SscSignOut::factory()->create(['case_id' => $case->id, 'status' => SignOutStatus::Terverifikasi, 'created_by' => $perawat->id]);
    IntraopRecord::factory()->create(['case_id' => $case->id, 'created_by' => $perawat->id]);
    PostopObservation::factory()->create(['case_id' => $case->id, 'created_by' => $perawat->id]);
    PostopSummary::factory()->create(['case_id' => $case->id, 'created_by' => $perawat->id]);
    Handover::factory()->create([
        'case_id' => $case->id,
        'handed_by_id' => $perawat->id,
        'received_by_name' => 'Ns. Penerima Ruangan',
        'created_by' => $perawat->id,
    ]);
    CaseSignoff::factory()->create([
        'case_id' => $case->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Signoff,
        'role_label' => 'perawat',
        'user_id' => $perawat->id,
    ]);

    // Admin diblokir (403)
    $this->actingAs($admin)->get("/kasus/{$case->id}/pdf")->assertForbidden();
    $this->actingAs($admin)->get("/kasus/{$case->id}/pdf/serah-terima")->assertForbidden();

    // Perawat, Dokter, Kepala Ruangan berhasil mengunduh PDF valid
    foreach ([$perawat, $operator, $kepala] as $allowed) {
        $fullRes = $this->actingAs($allowed)->get("/kasus/{$case->id}/pdf");
        $fullRes->assertOk();
        expect($fullRes->headers->get('content-type'))->toContain('application/pdf');
        expect(str_starts_with((string) $fullRes->getContent(), '%PDF-'))->toBeTrue();

        $handoverRes = $this->actingAs($allowed)->get("/kasus/{$case->id}/pdf/serah-terima");
        $handoverRes->assertOk();
        expect($handoverRes->headers->get('content-type'))->toContain('application/pdf');
        expect(str_starts_with((string) $handoverRes->getContent(), '%PDF-'))->toBeTrue();
    }

    // Verifikasi konten HTML sumber PDF memuat header RS, identitas pasien, dan seluruh bagian
    $exporter = app(PdfExporter::class);
    $fullHtml = view('pdf.case-full', $exporter->fullCaseViewData($case))->render();
    expect($fullHtml)
        ->toContain('RS Universitas Muhammadiyah Jember')
        ->toContain('Pasien Uji PDF')
        ->toContain('PDF-001')
        ->toContain('Pengkajian & Persiapan Pre-Operasi')
        ->toContain('Surgical Safety Checklist')
        ->toContain('Dokumentasi Intra-Operasi')
        ->toContain('Observasi Pasca-Operasi')
        ->toContain('Ringkasan Serah Terima Pasien (12 Item SKPL V.E)')
        ->toContain('Pengesahan & Verifikasi Petugas');

    $handoverHtml = view('pdf.case-handover', $exporter->handoverViewData($case))->render();
    expect($handoverHtml)
        ->toContain('RS Universitas Muhammadiyah Jember')
        ->toContain('Lembar Serah Terima Pasien Kamar Operasi')
        ->toContain('Ns. Penerima Ruangan')
        ->toContain('1. Identitas Pasien')
        ->toContain('12. Instruksi &amp; Tindak Lanjut Perawatan');
});
