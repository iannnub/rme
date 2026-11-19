<?php

use App\Actions\AdvanceCaseStatus;
use App\Enums\CaseStatus;
use App\Enums\NurseType;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Models\AuditActivity;
use App\Models\CaseSignoff;
use App\Models\Patient;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Services\CaseTransitionChecker;
use Illuminate\Validation\ValidationException;

test('kasus dapat bertransisi linear penuh dari pre_op hingga selesai dan mencatat audit beserta waktu tonggak', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $instrumen = User::factory()->perawat(NurseType::Instrumen)->create();
    $perawatAnestesi = User::factory()->perawat(NurseType::Anestesi)->create();
    $operator = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();

    $case = SurgeryCase::factory()
        ->withTeam()
        ->create([
            'surgeon_id' => $operator->id,
            'anesthesiologist_id' => $anestesi->id,
            'circulating_nurse_id' => $perawat->id,
            'scrub_nurse_id' => $instrumen->id,
            'anesthesia_nurse_id' => $perawatAnestesi->id,
            'stage_readiness_flags' => [
                'preop_assessment_completed' => true,
                'preop_checklist_ready' => true,
                'instrument_check_completed' => true,
                'sign_in_completed' => true,
            ],
            'created_by' => $perawat->id,
        ]);

    // Hanya pengesahan perawat pre-op yang wajib (dokter berjalan paralel)
    CaseSignoff::factory()->create([
        'case_id' => $case->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Signoff,
        'role_label' => 'perawat',
        'user_id' => $perawat->id,
    ]);

    $action = app(AdvanceCaseStatus::class);

    $case = $action->execute($case, CaseStatus::SiapOperasi, $perawat, 'Pasien siap menuju kamar operasi');
    expect($case->status)->toBe(CaseStatus::SiapOperasi);
    expect($case->ready_at)->not->toBeNull();

    $entered = now()->subHours(2);
    $case->update(['entered_or_at' => $entered]);

    $case = $action->execute($case->fresh(), CaseStatus::IntraOp, $perawat);
    expect($case->status)->toBe(CaseStatus::IntraOp);

    $case->update([
        'surgery_end_at' => $entered->copy()->addMinutes(55),
        'left_or_at' => $entered->copy()->addMinutes(70),
        'stage_readiness_flags' => [
            ...($case->stage_readiness_flags ?? []),
            'final_count_completed' => true,
            'sign_out_verified' => true,
        ],
    ]);

    foreach ([
        'perawat_sirkuler' => $perawat,
        'perawat_instrumen' => $instrumen,
        'dokter_anestesi' => $anestesi,
        'dokter_operator' => $operator,
    ] as $roleLabel => $signer) {
        CaseSignoff::factory()->create([
            'case_id' => $case->id,
            'section' => SignoffSection::IntraOp,
            'kind' => SignoffKind::Signoff,
            'role_label' => $roleLabel,
            'user_id' => $signer->id,
        ]);
    }

    $case = $action->execute($case->fresh(), CaseStatus::PascaOp, $perawat);
    expect($case->status)->toBe(CaseStatus::PascaOp);

    $case->update([
        'stage_readiness_flags' => [
            ...($case->stage_readiness_flags ?? []),
            'postop_observation_completed' => true,
            'pre_transfer_condition_recorded' => true,
        ],
    ]);

    $case = $action->execute($case->fresh(), CaseStatus::SerahTerima, $perawat);
    expect($case->status)->toBe(CaseStatus::SerahTerima);

    $case->update([
        'stage_readiness_flags' => [
            ...($case->stage_readiness_flags ?? []),
            'handover_completed' => true,
        ],
    ]);

    $case = $action->execute($case->fresh(), CaseStatus::Selesai, $perawat, 'Serah terima lengkap');
    expect($case->status)->toBe(CaseStatus::Selesai);
    expect($case->completed_at)->not->toBeNull();

    $statusLog = AuditActivity::query()
        ->where('case_id', $case->id)
        ->where('subject_type', SurgeryCase::class)
        ->where('reason', 'Serah terima lengkap')
        ->first();

    expect($statusLog)->not->toBeNull();
    expect($statusLog?->causer_id)->toBe($perawat->id);
    expect($statusLog?->properties?->get('old')['status'] ?? null)->toBe(CaseStatus::SerahTerima->value);
    expect($statusLog?->properties?->get('attributes')['status'] ?? null)->toBe(CaseStatus::Selesai->value);
});

test('transisi mundur, lompat tahap, status sama, dan kasus selesai ditolak dengan pesan Bahasa Indonesia', function () {
    $perawat = User::factory()->perawat()->create();
    $action = app(AdvanceCaseStatus::class);

    $intraCase = SurgeryCase::factory()->atStatus(CaseStatus::IntraOp)->create(['created_by' => $perawat->id]);

    expect(fn () => $action->execute($intraCase, CaseStatus::PreOp, $perawat))
        ->toThrow(ValidationException::class, 'Status kasus tidak boleh mundur ke tahap sebelumnya.');

    expect(fn () => $action->execute($intraCase, CaseStatus::IntraOp, $perawat))
        ->toThrow(ValidationException::class, 'Status tujuan harus berbeda dari status saat ini.');

    expect(fn () => $action->execute($intraCase, CaseStatus::SerahTerima, $perawat))
        ->toThrow(ValidationException::class, 'Tahap tidak boleh dilewati; status berikutnya harus Pasca-Operasi.');

    $doneCase = SurgeryCase::factory()->atStatus(CaseStatus::Selesai)->create(['created_by' => $perawat->id]);

    expect(fn () => $action->execute($doneCase, CaseStatus::SerahTerima, $perawat))
        ->toThrow(ValidationException::class, 'Kasus yang sudah selesai tidak dapat diubah statusnya.');
});

test('setiap syarat wajib yang belum lengkap menahan transisi dengan daftar syarat terstruktur', function () {
    $perawat = User::factory()->perawat()->create();
    $checker = app(CaseTransitionChecker::class);

    // 1. Pre-op tanpa pengesahan perawat, tanpa tim, tanpa flag
    $patient = Patient::factory()->create(['created_by' => $perawat->id]);
    $preopCase = SurgeryCase::factory()->create([
        'patient_id' => $patient->id,
        'created_by' => $perawat->id,
    ]);

    // Buat signoff yang sudah dicabut agar terbukti signoff revoked tidak dihitung sah
    CaseSignoff::factory()->revoked()->create([
        'case_id' => $preopCase->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Signoff,
        'role_label' => 'perawat',
        'user_id' => $perawat->id,
    ]);

    $missingPreop = $checker->missingRequirements($preopCase, CaseStatus::SiapOperasi);
    expect($missingPreop)->toContain('Pengesahan pra-operasi oleh perawat tercatat');
    expect($missingPreop)->toContain('Ruang operasi dan seluruh tim (5 petugas) ditetapkan');
    expect($missingPreop)->toContain('Asesmen pra-operasi tersimpan (riwayat, alergi, puasa, TTV)');
    expect($missingPreop)->toContain('Checklist persiapan pra-operasi terisi dan status kesiapan Siap');
    expect($missingPreop)->toContain('Cek ketersediaan dan kesterilan alat selesai');
    expect($missingPreop)->toContain('SSC Sign In berstatus Selesai');

    // 2. Siap operasi tanpa entered_or_at
    $readyCase = SurgeryCase::factory()->atStatus(CaseStatus::SiapOperasi)->create(['created_by' => $perawat->id]);
    expect($checker->missingRequirements($readyCase, CaseStatus::IntraOp))
        ->toBe(['Waktu masuk kamar operasi harus diisi']);

    // 3. Intra-op dengan waktu terbalik dan baru 3 dari 4 pengesahan
    $entered = now();
    $intraCase = SurgeryCase::factory()->atStatus(CaseStatus::IntraOp)->create([
        'entered_or_at' => $entered,
        'surgery_end_at' => $entered->copy()->addMinutes(40),
        'left_or_at' => $entered->copy()->addMinutes(20),
        'created_by' => $perawat->id,
    ]);

    foreach (['perawat_sirkuler', 'perawat_instrumen', 'dokter_anestesi'] as $roleLabel) {
        CaseSignoff::factory()->create([
            'case_id' => $intraCase->id,
            'section' => SignoffSection::IntraOp,
            'kind' => SignoffKind::Signoff,
            'role_label' => $roleLabel,
            'user_id' => $perawat->id,
        ]);
    }

    $missingIntra = $checker->missingRequirements($intraCase, CaseStatus::PascaOp);
    expect($missingIntra)->toContain('Waktu selesai operasi dan keluar kamar operasi terisi dengan urutan kronologi menaik');
    expect($missingIntra)->toContain('Pengesahan intra-operasi oleh 4 pihak (sirkuler, instrumen, dokter anestesi, dokter operator) lengkap');
    expect($missingIntra)->toContain('Hitungan akhir instrumen, kasa, dan jarum tercatat');
    expect($missingIntra)->toContain('SSC Sign Out berstatus Terverifikasi');
});

test('endpoint lanjut status hanya dapat diakses perawat dan meneruskan validasi kegagalan', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->atStatus(CaseStatus::SiapOperasi)->create([
        'entered_or_at' => null,
        'created_by' => $perawat->id,
    ]);

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/lanjut-status", [
            'target_status' => CaseStatus::IntraOp->value,
        ])
        ->assertSessionHasErrors('transition');

    $case->update(['entered_or_at' => now()]);

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/lanjut-status", [
            'target_status' => CaseStatus::IntraOp->value,
            'reason' => 'Pasien masuk kamar operasi OK 1',
        ])
        ->assertRedirect();

    expect($case->fresh()?->status)->toBe(CaseStatus::IntraOp);

    foreach ([
        User::factory()->dokterOperator()->create(),
        User::factory()->dokterAnestesi()->create(),
        User::factory()->kepalaRuangan()->create(),
        User::factory()->admin()->create(),
    ] as $nonPerawat) {
        $this->actingAs($nonPerawat)
            ->post("/kasus/{$case->id}/lanjut-status", [
                'target_status' => CaseStatus::PascaOp->value,
            ])
            ->assertForbidden();
    }
});
