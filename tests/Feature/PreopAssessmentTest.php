<?php

use App\Actions\AdvanceCaseStatus;
use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\CaseStatus;
use App\Enums\ExamType;
use App\Enums\NurseType;
use App\Enums\NursingPhase;
use App\Enums\ReadinessStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\VitalSignPhase;
use App\Models\AuditActivity;
use App\Models\CaseInterventionItem;
use App\Models\CaseNursingDiagnosis;
use App\Models\CaseSignoff;
use App\Models\NursingDiagnosis;
use App\Models\PreopAssessment;
use App\Models\SupportingExam;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use Database\Seeders\MasterSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(MasterSeeder::class);
});

test('perawat dapat melihat halaman pre-op dan menyimpan asesmen pra-operasi lengkap beserta TTV dan penunjang', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);

    $this->actingAs($perawat)
        ->get("/kasus/{$case->id}/pre-op")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/pre-op')
            ->where('canManage', true)
            ->where('canSign', true)
            ->where('canVerify', false)
            ->has('options.masterNursingDiagnoses')
        );

    $payload = [
        'chief_complaint' => 'Nyeri perut kanan bawah hebat sejak pagi',
        'current_illness_history' => 'Pasien merasakan nyeri tumpul lalu berpindah ke kanan bawah disertai mual',
        'past_illness_history' => 'Gastritis kronis sejak 2 tahun lalu',
        'surgery_history' => 'Tidak ada',
        'allergy_status' => AllergyStatus::Ada->value,
        'allergy_detail' => 'Alergi amoksisilin dan lateks ringan',
        'medication_history' => 'Antasida sirup',
        'anesthesia_history' => 'Tidak pernah',
        'fasting_status' => true,
        'fasting_since' => now('Asia/Jakarta')->subHours(7)->format('Y-m-d\TH:i'),
        'skin_condition' => 'Turgor baik, tidak ada luka di area bedah',
        'anxiety_level' => AnxietyLevel::Sedang->value,
        'surgery_knowledge' => 'Pasien telah menerima penjelasan dari dokter bedah',
        'family_support' => 'Didampingi suami',
        'mental_readiness' => 'Pasien siap menjalani operasi',
        'preparation' => [
            'identity_verified' => true,
            'informed_consent_surgery' => true,
            'anesthesia_consent' => true,
            'fasting_verified' => true,
            'allergy_assessed' => true,
            'lab_available' => true,
            'iv_access' => true,
            'operation_area_prepared' => true,
            'jewelry_removed' => true,
            'site_marked' => true,
        ],
        'implementation_actions' => 'Pasang infus RL 20 tpm pada tangan kiri, posisikan nyaman',
        'implementation_education' => 'Edukasi napas dalam dan puasa lanjut',
        'implementation_monitoring' => 'Observasi TTV setiap 15 menit',
        'implementation_response' => 'Pasien lebih tenang dan memahami prosedur',
        'evaluation_condition' => 'Compos mentis, nyeri perut skala 4',
        'evaluation_completeness' => 'Seluruh berkas administrasi dan checklist lengkap',
        'readiness_status' => ReadinessStatus::Siap->value,
        'evaluation_notes' => 'Pasien siap ditransfer ke kamar operasi',
        'vital_signs' => [
            'recorded_at' => now('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'bp_systolic' => 125,
            'bp_diastolic' => 82,
            'pulse' => 84,
            'temperature' => 36.7,
            'respiratory_rate' => 18,
            'spo2' => 99,
            'weight_kg' => 55.5,
            'height_cm' => 160.0,
            'consciousness' => 'compos_mentis',
            'notes' => 'TTV stabil',
        ],
        'supporting_exams' => [
            [
                'exam_type' => ExamType::Hemoglobin->value,
                'result' => '12.8 g/dL',
                'exam_date' => now('Asia/Jakarta')->toDateString(),
            ],
            [
                'exam_type' => ExamType::Leukosit->value,
                'result' => '13.200 /uL',
                'exam_date' => now('Asia/Jakarta')->toDateString(),
            ],
        ],
    ];

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", $payload)
        ->assertRedirect();

    $assessment = PreopAssessment::query()->where('case_id', $case->id)->firstOrFail();
    expect($assessment->chief_complaint)->toBe('Nyeri perut kanan bawah hebat sejak pagi');
    expect($assessment->allergy_status)->toBe(AllergyStatus::Ada);
    expect($assessment->allergy_detail)->toBe('Alergi amoksisilin dan lateks ringan');
    expect($assessment->readiness_status)->toBe(ReadinessStatus::Siap);
    expect($assessment->isPreparationComplete())->toBeTrue();

    $vital = VitalSign::query()->where('case_id', $case->id)->where('phase', VitalSignPhase::Pre)->firstOrFail();
    expect($vital->bp_systolic)->toBe(125);
    expect($vital->spo2)->toBe(99);

    $exams = SupportingExam::query()->where('case_id', $case->id)->get();
    expect($exams)->toHaveCount(2);

    $audit = AuditActivity::query()
        ->where('case_id', $case->id)
        ->where('subject_type', PreopAssessment::class)
        ->first();
    expect($audit)->not->toBeNull();
    expect($audit?->causer_id)->toBe($perawat->id);
});

test('status kesiapan operasi hanya boleh Siap jika seluruh checklist persiapan wajib lengkap', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);

    $payloadIncomplete = [
        'allergy_status' => AllergyStatus::TidakAda->value,
        'fasting_status' => false,
        'anxiety_level' => AnxietyLevel::TidakCemas->value,
        'readiness_status' => ReadinessStatus::Siap->value,
        'preparation' => [
            'identity_verified' => true,
            'informed_consent_surgery' => false, // belum lengkap
            'anesthesia_consent' => true,
            'fasting_verified' => true,
            'allergy_assessed' => true,
            'lab_available' => true,
            'iv_access' => true,
            'operation_area_prepared' => true,
            'jewelry_removed' => true,
            'site_marked' => true,
        ],
    ];

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", $payloadIncomplete)
        ->assertSessionHasErrors('readiness_status');
});

test('status alergi Ada mewajibkan detail alergi dan status puasa aktif mewajibkan waktu mulai puasa', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", [
            'allergy_status' => AllergyStatus::Ada->value,
            'allergy_detail' => '', // kosong
            'fasting_status' => false,
            'anxiety_level' => AnxietyLevel::TidakCemas->value,
            'readiness_status' => ReadinessStatus::BelumSiap->value,
        ])
        ->assertSessionHasErrors('allergy_detail');

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", [
            'allergy_status' => AllergyStatus::TidakAda->value,
            'fasting_status' => true,
            'fasting_since' => '', // kosong
            'anxiety_level' => AnxietyLevel::TidakCemas->value,
            'readiness_status' => ReadinessStatus::BelumSiap->value,
        ])
        ->assertSessionHasErrors('fasting_since');
});

test('perawat dapat membubuhkan pengesahan pra-operasi dengan konfirmasi kata sandi dan tercatat di audit', function () {
    $perawat = User::factory()->perawat()->create([
        'nip' => '198906152014022001',
        'password' => bcrypt('Password123!'),
    ]);
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pre-op/sahkan", [
            'password' => 'WrongPassword',
        ])
        ->assertSessionHasErrors('password');

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pre-op/sahkan", [
            'password' => 'Password123!',
        ])
        ->assertRedirect();

    $signoff = CaseSignoff::query()
        ->where('case_id', $case->id)
        ->where('section', SignoffSection::PreOp)
        ->where('kind', SignoffKind::Signoff)
        ->firstOrFail();

    expect($signoff->user_id)->toBe($perawat->id);
    expect($signoff->role_label)->toBe('perawat');
    expect($signoff->nip_snapshot)->toBe('198906152014022001');

    $audit = AuditActivity::query()
        ->where('case_id', $case->id)
        ->where('subject_type', CaseSignoff::class)
        ->first();
    expect($audit)->not->toBeNull();
    expect($audit?->causer_id)->toBe($perawat->id);
});

test('dokter dapat memverifikasi pra-operasi dengan konfirmasi kata sandi dan tercatat di audit', function () {
    $perawat = User::factory()->perawat()->create();
    $dokter = User::factory()->dokterOperator()->create([
        'sip' => 'SIP.503/0101/SP.B/2026',
        'password' => bcrypt('Password123!'),
    ]);
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id, 'surgeon_id' => $dokter->id]);

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pre-op/verifikasi", ['password' => 'Password123!'])
        ->assertForbidden();

    $this->actingAs($dokter)
        ->post("/kasus/{$case->id}/pre-op/verifikasi", ['password' => 'Password123!'])
        ->assertRedirect();

    $verification = CaseSignoff::query()
        ->where('case_id', $case->id)
        ->where('section', SignoffSection::PreOp)
        ->where('kind', SignoffKind::Verification)
        ->firstOrFail();

    expect($verification->user_id)->toBe($dokter->id);
    expect($verification->sip_snapshot)->toBe('SIP.503/0101/SP.B/2026');
});

test('pembaruan data pra-operasi setelah verifikasi dokter otomatis mereset verifikasi dokter tersebut', function () {
    $perawat = User::factory()->perawat()->create();
    $dokter = User::factory()->dokterOperator()->create(['password' => bcrypt('Password123!')]);
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id, 'surgeon_id' => $dokter->id]);

    CaseSignoff::factory()->create([
        'case_id' => $case->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Verification,
        'role_label' => 'dokter_operator',
        'user_id' => $dokter->id,
        'sip_snapshot' => $dokter->sip,
    ]);

    expect(CaseSignoff::query()->where('case_id', $case->id)->whereNull('revoked_at')->count())->toBe(1);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", [
            'chief_complaint' => 'Nyeri bertambah hebat',
            'allergy_status' => AllergyStatus::TidakAda->value,
            'fasting_status' => false,
            'anxiety_level' => AnxietyLevel::Ringan->value,
            'readiness_status' => ReadinessStatus::BelumSiap->value,
        ])
        ->assertRedirect();

    $revoked = CaseSignoff::query()
        ->where('case_id', $case->id)
        ->where('section', SignoffSection::PreOp)
        ->where('kind', SignoffKind::Verification)
        ->firstOrFail();

    expect($revoked->revoked_at)->not->toBeNull();
    expect($revoked->revoke_reason)->toBe('Data pre-operasi diperbarui setelah verifikasi dokter.');
});

test('perawat dapat menambah diagnosis SDKI dan mencentang intervensi SIKI yang tercatat waktu dan pelaksana', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);

    $sdkiAnsietas = NursingDiagnosis::query()->where('code', 'D.0080')->where('phase', NursingPhase::Pre->value)->firstOrFail();

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pre-op/diagnosis", [
            'nursing_diagnosis_id' => $sdkiAnsietas->id,
            'notes' => 'Pasien tampak gelisah menjelang operasi',
        ])
        ->assertRedirect();

    $caseDiag = CaseNursingDiagnosis::query()->where('case_id', $case->id)->firstOrFail();
    expect($caseDiag->nursing_diagnosis_id)->toBe($sdkiAnsietas->id);

    $items = CaseInterventionItem::query()->where('case_nursing_diagnosis_id', $caseDiag->id)->get();
    expect($items->count())->toBeGreaterThan(0);

    $firstItem = $items->first();
    expect($firstItem?->is_done)->toBeFalse();

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pre-op/intervensi/{$firstItem?->id}/toggle")
        ->assertRedirect();

    $firstItem?->refresh();
    expect($firstItem?->is_done)->toBeTrue();
    expect($firstItem?->done_by)->toBe($perawat->id);
    expect($firstItem?->done_at)->not->toBeNull();

    $this->actingAs($perawat)
        ->delete("/kasus/{$case->id}/pre-op/diagnosis/{$caseDiag->id}")
        ->assertRedirect();

    expect(CaseNursingDiagnosis::query()->where('id', $caseDiag->id)->exists())->toBeFalse();
});

test('alur transisi pre_op menuju siap_operasi lolos ketika syarat preop riil di database telah terpenuhi', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()
        ->withTeam()
        ->create([
            'created_by' => $perawat->id,
            'stage_readiness_flags' => [
                'instrument_check_completed' => true,
                'sign_in_completed' => true,
            ],
        ]);

    PreopAssessment::factory()->create([
        'case_id' => $case->id,
        'readiness_status' => ReadinessStatus::Siap,
        'created_by' => $perawat->id,
    ]);

    VitalSign::factory()->create([
        'case_id' => $case->id,
        'phase' => VitalSignPhase::Pre,
        'created_by' => $perawat->id,
    ]);

    CaseSignoff::factory()->create([
        'case_id' => $case->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Signoff,
        'role_label' => 'perawat',
        'user_id' => $perawat->id,
    ]);

    $action = app(AdvanceCaseStatus::class);
    $updated = $action->execute($case, CaseStatus::SiapOperasi, $perawat);

    expect($updated->status)->toBe(CaseStatus::SiapOperasi);
    expect($updated->ready_at)->not->toBeNull();
});

test('kasus yang berstatus selesai mengunci pembaruan data pre-op', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->atStatus(CaseStatus::Selesai)->create(['created_by' => $perawat->id]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", [
            'allergy_status' => AllergyStatus::TidakAda->value,
            'fasting_status' => false,
            'anxiety_level' => AnxietyLevel::TidakCemas->value,
            'readiness_status' => ReadinessStatus::BelumSiap->value,
        ])
        ->assertForbidden();

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pre-op/sahkan", ['password' => 'Password123!'])
        ->assertForbidden();
});
