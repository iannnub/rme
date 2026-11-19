<?php

use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\CaseStatus;
use App\Enums\NurseType;
use App\Enums\ReadinessStatus;
use App\Enums\Role;
use App\Enums\SignOutStatus;
use App\Enums\SscStatus;
use App\Models\IntraopRecord;
use App\Models\Patient;
use App\Models\PreopAssessment;
use App\Models\Room;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Support\OptimisticLock;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

test('respons halaman web menyertakan seluruh HTTP security headers dan CSP dasar', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("frame-ancestors 'self'");
    expect($csp)->toContain("script-src-elem 'self'");
    expect($csp)->toContain("object-src 'none'");

    $httpsResponse = $this->get('https://localhost/login');
    $httpsResponse->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('optimistic locking menolak penyimpanan stale pada form Pre-Op dan menerima token terbaru', function () {
    $nurse1 = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Sirkuler]);
    $nurse2 = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Sirkuler]);
    $patient = Patient::factory()->create(['created_by' => $nurse1->id]);
    $case = SurgeryCase::factory()->create(['patient_id' => $patient->id, 'created_by' => $nurse1->id]);

    $assessment = PreopAssessment::query()->create([
        'case_id' => $case->id,
        'chief_complaint' => 'Keluhan awal',
        'allergy_status' => AllergyStatus::TidakAda,
        'fasting_status' => false,
        'anxiety_level' => AnxietyLevel::TidakCemas,
        'readiness_status' => ReadinessStatus::BelumSiap,
        'preparation' => [],
        'created_by' => $nurse1->id,
    ]);

    $staleToken = OptimisticLock::token($assessment);

    // Simulasikan petugas ke-2 memperbarui data 2 detik lebih baru
    $assessment->forceFill([
        'chief_complaint' => 'Keluhan diperbarui petugas 2',
        'updated_at' => $assessment->updated_at->copy()->addSeconds(5),
    ])->save();

    $freshToken = OptimisticLock::token($assessment->fresh());
    expect($freshToken)->not->toBe($staleToken);

    $payload = [
        'expected_updated_at' => $staleToken,
        'chief_complaint' => 'Timpa dari tab lama petugas 1',
        'allergy_status' => AllergyStatus::TidakAda->value,
        'fasting_status' => false,
        'anxiety_level' => AnxietyLevel::Ringan->value,
        'readiness_status' => ReadinessStatus::BelumSiap->value,
        'preparation' => [],
    ];

    $this->actingAs($nurse1)
        ->put(route('cases.preop.update', $case), $payload)
        ->assertSessionHasErrors('concurrency');

    // Data petugas 2 tidak tertimpa
    expect($assessment->fresh()?->chief_complaint)->toBe('Keluhan diperbarui petugas 2');

    // Simpan ulang dengan token terbaru berhasil
    $payload['expected_updated_at'] = $freshToken;
    $payload['chief_complaint'] = 'Keluhan setelah muat ulang';

    $this->actingAs($nurse2)
        ->put(route('cases.preop.update', $case), $payload)
        ->assertSessionHasNoErrors();

    expect($assessment->fresh()?->chief_complaint)->toBe('Keluhan setelah muat ulang');
});

test('optimistic locking melindungi form SSC Sign In, Time Out, Sign Out, dan Intra-Op', function () {
    $nurse = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Sirkuler]);
    $scrub = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Instrumen]);
    $anestNurse = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Anestesi]);
    $surgeon = User::factory()->create(['role' => Role::DokterOperator]);
    $anest = User::factory()->create(['role' => Role::DokterAnestesi]);
    $room = Room::factory()->create();
    $patient = Patient::factory()->create(['created_by' => $nurse->id]);

    $case = SurgeryCase::factory()->create([
        'patient_id' => $patient->id,
        'room_id' => $room->id,
        'surgeon_id' => $surgeon->id,
        'anesthesiologist_id' => $anest->id,
        'circulating_nurse_id' => $nurse->id,
        'scrub_nurse_id' => $scrub->id,
        'anesthesia_nurse_id' => $anestNurse->id,
        'team_assigned_at' => now(),
        'created_by' => $nurse->id,
    ]);
    $case->forceFill(['status' => CaseStatus::IntraOp])->save();

    $signIn = SscSignIn::query()->create([
        'case_id' => $case->id,
        'status' => SscStatus::DalamProses,
        'filled_by' => $nurse->id,
        'created_by' => $nurse->id,
    ]);
    $staleSignIn = $signIn->updated_at->copy()->subMinutes(2)->utc()->toIso8601String();

    $this->actingAs($nurse)
        ->put(route('cases.ssc.sign-in.update', $case), [
            'expected_updated_at' => $staleSignIn,
            'status' => SscStatus::DalamProses->value,
        ])
        ->assertSessionHasErrors('concurrency');

    $timeOut = SscTimeOut::query()->create([
        'case_id' => $case->id,
        'status' => SscStatus::DalamProses,
        'filled_by' => $nurse->id,
        'created_by' => $nurse->id,
    ]);
    $staleTimeOut = $timeOut->updated_at->copy()->subMinutes(2)->utc()->toIso8601String();

    $this->actingAs($surgeon)
        ->put(route('cases.ssc.time-out.update', $case), [
            'expected_updated_at' => $staleTimeOut,
            'surgeon_critical_steps' => 'tidak',
        ])
        ->assertSessionHasErrors('concurrency');

    $signOut = SscSignOut::query()->create([
        'case_id' => $case->id,
        'status' => SignOutStatus::Draf,
        'filled_by' => $nurse->id,
        'created_by' => $nurse->id,
    ]);
    $staleSignOut = $signOut->updated_at->copy()->subMinutes(2)->utc()->toIso8601String();

    $this->actingAs($nurse)
        ->put(route('cases.ssc.sign-out.update', $case), [
            'expected_updated_at' => $staleSignOut,
            'status' => SignOutStatus::Draf->value,
        ])
        ->assertSessionHasErrors('concurrency');

    $record = IntraopRecord::query()->create([
        'case_id' => $case->id,
        'created_by' => $nurse->id,
    ]);
    $staleRecord = $record->updated_at->copy()->subMinutes(2)->utc()->toIso8601String();
    $staleCase = $case->updated_at->copy()->subMinutes(2)->utc()->toIso8601String();

    $this->actingAs($nurse)
        ->put(route('cases.intraop.chronology', $case), [
            'expected_updated_at' => $staleCase,
            'entered_or_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ])
        ->assertSessionHasErrors('concurrency');

    $this->actingAs($nurse)
        ->put(route('cases.intraop.position', $case), [
            'expected_updated_at' => $staleRecord,
            'draping_done' => true,
        ])
        ->assertSessionHasErrors('concurrency');

    // Token fresh diterima pada Intra-Op posisi
    $this->actingAs($nurse)
        ->put(route('cases.intraop.position', $case), [
            'expected_updated_at' => OptimisticLock::token($record->fresh()),
            'draping_done' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($record->fresh()?->draping_done)->toBeTrue();
});

test('uji beban ringan 30 kasus berjalan tanpa N+1 query dan tanpa lazy loading violation', function () {
    Model::preventLazyLoading(true);

    $headNurse = User::factory()->create(['role' => Role::KepalaRuangan]);
    $nurse = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Sirkuler]);
    $surgeon = User::factory()->create(['role' => Role::DokterOperator]);
    $room = Room::factory()->create();

    $patients = Patient::factory()->count(30)->create(['created_by' => $nurse->id]);
    foreach ($patients as $index => $patient) {
        $case = SurgeryCase::factory()->create([
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'surgeon_id' => $surgeon->id,
            'circulating_nurse_id' => $nurse->id,
            'scheduled_at' => now('Asia/Jakarta')->subDays($index % 5),
            'created_by' => $nurse->id,
        ]);

        PreopAssessment::query()->create([
            'case_id' => $case->id,
            'chief_complaint' => "Keluhan uji beban #{$index}",
            'allergy_status' => AllergyStatus::TidakAda,
            'fasting_status' => false,
            'anxiety_level' => AnxietyLevel::Ringan,
            'readiness_status' => ReadinessStatus::Siap,
            'preparation' => [],
            'created_by' => $nurse->id,
        ]);
    }

    // 1. Dashboard Kepala Ruangan - query harus konstan (bukan O(N) terhadap 30 kasus)
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($headNurse)->get(route('dashboard'))->assertOk();
    $dashboardQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($dashboardQueries)->toBeLessThan(25);

    // 2. Laporan Bulanan - 6 bulan tren di-aggregate dalam 1 query tunggal
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($headNurse)->get(route('reports.index'))->assertOk();
    $reportQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($reportQueries)->toBeLessThan(30);

    // 3. Daftar Kasus & Audit Global
    $this->actingAs($headNurse)->get(route('cases.index'))->assertOk();
    $this->actingAs($headNurse)->get(route('audit.index'))->assertOk();
});

test('kasus demo selesai menghasilkan ekspor PDF lengkap dan serah terima valid', function () {
    $this->seed(DatabaseSeeder::class);

    $headNurse = User::query()->where('username', 'kepala')->firstOrFail();
    $completedCase = SurgeryCase::query()->where('case_number', 'OK-20261119-003')->firstOrFail();

    $fullPdf = $this->actingAs($headNurse)->get(route('cases.pdf.full', $completedCase));
    $fullPdf->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    expect(substr((string) $fullPdf->getContent(), 0, 5))->toBe('%PDF-');

    $handoverPdf = $this->actingAs($headNurse)->get(route('cases.pdf.handover', $completedCase));
    $handoverPdf->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    expect(substr((string) $handoverPdf->getContent(), 0, 5))->toBe('%PDF-');
});

test('metadata SEO, Open Graph, JSON-LD pada login publik dan proteksi noindex pada halaman klinis berfungsi', function () {
    $loginResponse = $this->get('/login');
    $loginResponse->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:locale" content="id_ID">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('WebApplication', false)
        ->assertSee('MedicalOrganization', false);

    expect($loginResponse->headers->get('X-Robots-Tag'))->toBeNull();

    $nurse = User::factory()->create(['role' => Role::Perawat, 'nurse_type' => NurseType::Sirkuler]);
    $dashboardResponse = $this->actingAs($nurse)->get('/dashboard');
    $dashboardResponse->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    expect(file_exists(public_path('og-image.png')))->toBeTrue();
    expect(file_exists(public_path('robots.txt')))->toBeTrue();
    expect(file_exists(public_path('sitemap.xml')))->toBeTrue();
    expect(file_exists(public_path('llms.txt')))->toBeTrue();
});
