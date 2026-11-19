<?php

use App\Enums\AllergyStatus;
use App\Enums\BloodReadiness;
use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\IvAccessStatus;
use App\Enums\NurseType;
use App\Enums\ReadinessStatus;
use App\Enums\RiskPresence;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SiteMarkingStatus;
use App\Enums\SscConsentStatus;
use App\Enums\SscMatchStatus;
use App\Enums\SscReadiness;
use App\Enums\SscStatus;
use App\Enums\VitalSignPhase;
use App\Models\AuditActivity;
use App\Models\CaseSignoff;
use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\PreopAssessment;
use App\Models\Room;
use App\Models\SscSignIn;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Support\Facades\Hash;

function completeSignInPayload(array $overrides = []): array
{
    return array_merge([
        'status' => SscStatus::Selesai->value,
        'identity_confirmed' => SscMatchStatus::Sesuai->value,
        'procedure_confirmed' => SscMatchStatus::Sesuai->value,
        'site_confirmed' => SscMatchStatus::Sesuai->value,
        'consent_surgery' => SscConsentStatus::Ada->value,
        'consent_anesthesia' => SscConsentStatus::Ada->value,
        'site_marking' => SiteMarkingStatus::Ada->value,
        'readiness' => [
            'anesthesia_machine' => SscReadiness::Siap->value,
            'oxygen_source' => SscReadiness::Siap->value,
            'anesthesia_drugs' => SscReadiness::Siap->value,
            'airway_aids' => SscReadiness::Siap->value,
            'suction' => SscReadiness::Siap->value,
            'monitoring_device' => SscReadiness::Siap->value,
            'emergency_drugs' => SscReadiness::Siap->value,
        ],
        'pulse_ox_attached' => true,
        'pulse_ox_functional' => true,
        'spo2' => 99,
        'pulse' => 78,
        'pulse_ox_note' => 'Terpasang baik',
        'allergy_status' => AllergyStatus::TidakAda->value,
        'difficult_airway_risk' => RiskPresence::TidakAda->value,
        'aspiration_risk' => RiskPresence::TidakAda->value,
        'anesthesiologist_assessment' => 'Mallampati I',
        'anticipation_plan' => 'Standar',
        'airway_equipment_ready' => SscReadiness::Siap->value,
        'bleeding_risk' => RiskPresence::TidakAda->value,
        'estimated_blood_loss_ml' => 150,
        'iv_access' => IvAccessStatus::Memadai->value,
        'fluids_ready' => SscReadiness::Siap->value,
        'blood_ready' => BloodReadiness::TidakDiindikasikan->value,
        'bleeding_note' => 'IV line 18G paten',
        'notes_followup' => '',
    ], $overrides);
}

test('perawat dapat menetapkan ruang dan seluruh tim 5 petugas serta mendapat peringatan non-blokir jika ruang sedang dipakai kasus intra_op', function () {
    $sirkuler = User::factory()->perawat(NurseType::Sirkuler)->create();
    $instrumen = User::factory()->perawat(NurseType::Instrumen)->create();
    $perawatAnestesi = User::factory()->perawat(NurseType::Anestesi)->create();
    $operator = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();
    $room = Room::factory()->create(['code' => 'OK 1', 'is_active' => true]);

    // Kasus lain yang sedang intra_op di ruang yang sama
    SurgeryCase::factory()->atStatus(CaseStatus::IntraOp)->create([
        'room_id' => $room->id,
        'created_by' => $sirkuler->id,
    ]);

    $case = SurgeryCase::factory()->create(['created_by' => $sirkuler->id]);

    $this->actingAs($sirkuler)
        ->get("/kasus/{$case->id}/persiapan")
        ->assertOk();

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/persiapan/tim", [
            'room_id' => $room->id,
            'surgeon_id' => $operator->id,
            'anesthesiologist_id' => $anestesi->id,
            'circulating_nurse_id' => $sirkuler->id,
            'scrub_nurse_id' => $instrumen->id,
            'anesthesia_nurse_id' => $perawatAnestesi->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionHas('warning');

    $case->refresh();
    expect($case->room_id)->toBe($room->id);
    expect($case->surgeon_id)->toBe($operator->id);
    expect($case->anesthesiologist_id)->toBe($anestesi->id);
    expect($case->circulating_nurse_id)->toBe($sirkuler->id);
    expect($case->scrub_nurse_id)->toBe($instrumen->id);
    expect($case->anesthesia_nurse_id)->toBe($perawatAnestesi->id);
    expect($case->team_assigned_at)->not->toBeNull();

    expect(AuditActivity::query()->where('case_id', $case->id)->where('subject_type', SurgeryCase::class)->exists())->toBeTrue();
});

test('non-perawat ditolak mengubah ruang/tim dan cek alat, sedangkan admin diblokir total dari halaman persiapan dan SSC', function () {
    $perawat = User::factory()->perawat()->create();
    $operator = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();
    $kepala = User::factory()->kepalaRuangan()->create();
    $admin = User::factory()->admin()->create();

    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);

    $this->actingAs($admin)->get("/kasus/{$case->id}/persiapan")->assertForbidden();
    $this->actingAs($admin)->get("/kasus/{$case->id}/ssc/sign-in")->assertForbidden();
    $this->actingAs($admin)->put("/kasus/{$case->id}/persiapan/tim", [])->assertForbidden();
    $this->actingAs($admin)->put("/kasus/{$case->id}/persiapan/alat", [])->assertForbidden();
    $this->actingAs($admin)->put("/kasus/{$case->id}/ssc/sign-in", [])->assertForbidden();

    foreach ([$operator, $anestesi, $kepala] as $viewer) {
        $this->actingAs($viewer)->get("/kasus/{$case->id}/persiapan")->assertOk();
        $this->actingAs($viewer)->get("/kasus/{$case->id}/ssc/sign-in")->assertOk();
        $this->actingAs($viewer)->put("/kasus/{$case->id}/persiapan/tim", [])->assertForbidden();
        $this->actingAs($viewer)->put("/kasus/{$case->id}/persiapan/alat", [])->assertForbidden();
    }

    foreach ([$operator, $kepala] as $nonEditor) {
        $this->actingAs($nonEditor)->put("/kasus/{$case->id}/ssc/sign-in", [])->assertForbidden();
        $this->actingAs($nonEditor)->post("/kasus/{$case->id}/ssc/sign-in/verifikasi", ['password' => 'Password123!'])->assertForbidden();
    }
});

test('cek alat menolak alat bermasalah tanpa catatan tindak lanjut dan menyimpan jika catatan diisi', function () {
    $perawat = User::factory()->perawat(NurseType::Instrumen)->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);
    $inst1 = Instrument::factory()->create(['name' => 'Set Bedah Mayor']);
    $inst2 = Instrument::factory()->create(['name' => 'Gunting Jaringan']);

    // Alat 2 berkarat dan tidak steril tanpa catatan -> ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/persiapan/alat", [
            'items' => [
                [
                    'instrument_id' => $inst1->id,
                    'is_available' => true,
                    'is_sterile' => true,
                    'is_functional' => true,
                    'is_rusty' => false,
                    'note' => '',
                ],
                [
                    'instrument_id' => $inst2->id,
                    'is_available' => true,
                    'is_sterile' => false,
                    'is_functional' => true,
                    'is_rusty' => true,
                    'note' => '',
                ],
            ],
        ])
        ->assertSessionHasErrors('items.1.note');

    expect(InstrumentCheck::query()->where('case_id', $case->id)->count())->toBe(0);

    // Dengan catatan tindak lanjut -> berhasil tersimpan
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/persiapan/alat", [
            'items' => [
                [
                    'instrument_id' => $inst1->id,
                    'is_available' => true,
                    'is_sterile' => true,
                    'is_functional' => true,
                    'is_rusty' => false,
                    'note' => '',
                ],
                [
                    'instrument_id' => $inst2->id,
                    'is_available' => true,
                    'is_sterile' => false,
                    'is_functional' => true,
                    'is_rusty' => true,
                    'note' => 'Diganti dengan gunting jaringan steril dari lemari cadangan OK 2.',
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(InstrumentCheck::query()->where('case_id', $case->id)->count())->toBe(2);
    expect(AuditActivity::query()->where('case_id', $case->id)->where('subject_type', InstrumentCheck::class)->exists())->toBeTrue();
});

test('SSC Sign In tidak dapat diisi sebelum ruang dan tim lengkap (Q1)', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->create([
        'room_id' => null,
        'surgeon_id' => null,
        'created_by' => $perawat->id,
    ]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", completeSignInPayload())
        ->assertSessionHasErrors('sign_in');

    expect(SscSignIn::query()->where('case_id', $case->id)->exists())->toBeFalse();
});

test('SSC Sign In tidak bisa berstatus Selesai jika item bermasalah tanpa catatan tindak lanjut atau item wajib belum lengkap', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->withTeam()->create(['created_by' => $perawat->id]);

    // 1. Item wajib belum lengkap tetapi memilih status Selesai
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", [
            'status' => SscStatus::Selesai->value,
            'identity_confirmed' => SscMatchStatus::Sesuai->value,
        ])
        ->assertSessionHasErrors('status');

    // 2. Item bermasalah (consent_anesthesia = belum_ada & mesin anestesi = tidak_siap) tanpa notes_followup
    $problemPayload = completeSignInPayload([
        'consent_anesthesia' => SscConsentStatus::BelumAda->value,
        'readiness' => [
            'anesthesia_machine' => SscReadiness::TidakSiap->value,
            'oxygen_source' => SscReadiness::Siap->value,
            'anesthesia_drugs' => SscReadiness::Siap->value,
            'airway_aids' => SscReadiness::Siap->value,
            'suction' => SscReadiness::Siap->value,
            'monitoring_device' => SscReadiness::Siap->value,
            'emergency_drugs' => SscReadiness::Siap->value,
        ],
        'notes_followup' => '',
    ]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", $problemPayload)
        ->assertSessionHasErrors('notes_followup');

    // 3. Dengan notes_followup -> berhasil disimpan sebagai Selesai
    $problemPayload['notes_followup'] = 'Informed consent anestesi sudah ditandatangani ulang dan mesin anestesi diganti unit cadangan siap pakai.';

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", $problemPayload)
        ->assertRedirect()
        ->assertSessionHas('success');

    $saved = SscSignIn::query()->where('case_id', $case->id)->first();
    expect($saved)->not->toBeNull();
    expect($saved?->status)->toBe(SscStatus::Selesai);
    expect($saved?->signed_at)->not->toBeNull();
    expect($saved?->filled_by)->toBe($perawat->id);
});

test('dokter anestesi kasus dapat mengisi penilaian risiko anestesi dan memverifikasi Sign In, serta edit ulang oleh perawat mereset verifikasi', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $anestesiKasus = User::factory()->dokterAnestesi()->create([
        'password' => Hash::make('Rahasia1234'),
        'sip' => 'SIP.ANESTESI.001',
    ]);
    $anestesiLain = User::factory()->dokterAnestesi()->create([
        'password' => Hash::make('Rahasia1234'),
    ]);

    $case = SurgeryCase::factory()->withTeam()->create([
        'anesthesiologist_id' => $anestesiKasus->id,
        'created_by' => $perawat->id,
    ]);

    // Dokter anestesi lain (bukan penanggung jawab kasus) ditolak
    $this->actingAs($anestesiLain)
        ->put("/kasus/{$case->id}/ssc/sign-in", [
            'difficult_airway_risk' => RiskPresence::Ada->value,
        ])
        ->assertForbidden();

    // Perawat menyimpan Sign In awal
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", completeSignInPayload())
        ->assertRedirect();

    // Dokter anestesi kasus memperbarui penilaian risiko jalan napas & perdarahan
    $this->actingAs($anestesiKasus)
        ->put("/kasus/{$case->id}/ssc/sign-in", [
            'difficult_airway_risk' => RiskPresence::Ada->value,
            'aspiration_risk' => RiskPresence::TidakAda->value,
            'anesthesiologist_assessment' => 'Mallampati III, leher pendek.',
            'anticipation_plan' => 'Siapkan video laringoskop dan bougie.',
            'airway_equipment_ready' => SscReadiness::Siap->value,
            'bleeding_risk' => RiskPresence::TidakAda->value,
            'estimated_blood_loss_ml' => 250,
            'iv_access' => IvAccessStatus::Memadai->value,
            'fluids_ready' => SscReadiness::Siap->value,
            'blood_ready' => BloodReadiness::TidakDiindikasikan->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $signIn = SscSignIn::query()->where('case_id', $case->id)->first();
    expect($signIn?->difficult_airway_risk)->toBe(RiskPresence::Ada);
    expect($signIn?->anesthesiologist_assessment)->toBe('Mallampati III, leher pendek.');
    // Status selesai yang diisi perawat tetap terjaga
    expect($signIn?->status)->toBe(SscStatus::Selesai);

    // Dokter anestesi kasus memverifikasi Sign In dengan kata sandi salah -> ditolak
    $this->actingAs($anestesiKasus)
        ->post("/kasus/{$case->id}/ssc/sign-in/verifikasi", ['password' => 'Salah99999'])
        ->assertSessionHasErrors('password');

    // Dengan kata sandi benar -> berhasil verifikasi
    $this->actingAs($anestesiKasus)
        ->post("/kasus/{$case->id}/ssc/sign-in/verifikasi", ['password' => 'Rahasia1234'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $verification = CaseSignoff::query()
        ->where('case_id', $case->id)
        ->where('section', SignoffSection::SignIn)
        ->where('kind', SignoffKind::Verification)
        ->first();

    expect($verification)->not->toBeNull();
    expect($verification?->revoked_at)->toBeNull();
    expect($verification?->sip_snapshot)->toBe('SIP.ANESTESI.001');

    // Perawat mengedit ulang Sign In -> verifikasi dokter anestesi otomatis dicabut (revoked)
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", completeSignInPayload(['spo2' => 98]))
        ->assertRedirect();

    $verification?->refresh();
    expect($verification?->revoked_at)->not->toBeNull();
    expect($verification?->revoke_reason)->toContain('diperbarui');
});

test('transisi pre_op ke siap_operasi hanya berhasil jika ruang+tim, cek alat, Sign In selesai, dan pengesahan pra-op lengkap menggunakan data riil database', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $instrumen = User::factory()->perawat(NurseType::Instrumen)->create();
    $perawatAnestesi = User::factory()->perawat(NurseType::Anestesi)->create();
    $operator = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();
    $room = Room::factory()->create();
    $instrument = Instrument::factory()->create();

    // Kasus tanpa stage_readiness_flags sama sekali
    $case = SurgeryCase::factory()->create([
        'room_id' => null,
        'surgeon_id' => null,
        'anesthesiologist_id' => null,
        'circulating_nurse_id' => null,
        'scrub_nurse_id' => null,
        'anesthesia_nurse_id' => null,
        'stage_readiness_flags' => null,
        'created_by' => $perawat->id,
    ]);

    // 1. Lengkapi syarat Pre-Op riil (asesmen siap, TTV pre, pengesahan perawat)
    PreopAssessment::factory()->create([
        'case_id' => $case->id,
        'chief_complaint' => 'Nyeri perut kanan bawah',
        'readiness_status' => ReadinessStatus::Siap,
        'created_by' => $perawat->id,
    ]);
    VitalSign::factory()->create([
        'case_id' => $case->id,
        'phase' => VitalSignPhase::Pre,
        'consciousness' => Consciousness::ComposMentis,
        'created_by' => $perawat->id,
    ]);
    CaseSignoff::factory()->create([
        'case_id' => $case->id,
        'section' => SignoffSection::PreOp,
        'kind' => SignoffKind::Signoff,
        'role_label' => 'perawat',
        'user_id' => $perawat->id,
    ]);

    // Tanpa ruang+tim, cek alat, Sign In -> ditolak
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/lanjut-status", ['target_status' => CaseStatus::SiapOperasi->value])
        ->assertSessionHasErrors('transition');

    // 2. Tetapkan ruang & tim lengkap
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/persiapan/tim", [
            'room_id' => $room->id,
            'surgeon_id' => $operator->id,
            'anesthesiologist_id' => $anestesi->id,
            'circulating_nurse_id' => $perawat->id,
            'scrub_nurse_id' => $instrumen->id,
            'anesthesia_nurse_id' => $perawatAnestesi->id,
        ])
        ->assertRedirect();

    // Masih kurang cek alat & Sign In -> ditolak
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/lanjut-status", ['target_status' => CaseStatus::SiapOperasi->value])
        ->assertSessionHasErrors('transition');

    // 3. Simpan cek alat lengkap
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/persiapan/alat", [
            'items' => [
                [
                    'instrument_id' => $instrument->id,
                    'is_available' => true,
                    'is_sterile' => true,
                    'is_functional' => true,
                    'is_rusty' => false,
                    'note' => '',
                ],
            ],
        ])
        ->assertRedirect();

    // Sign In masih dalam_proses -> ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", completeSignInPayload(['status' => SscStatus::DalamProses->value]))
        ->assertRedirect();

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/lanjut-status", ['target_status' => CaseStatus::SiapOperasi->value])
        ->assertSessionHasErrors('transition');

    // 4. Selesaikan SSC Sign In -> kini transisi pre_op -> siap_operasi berhasil!
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-in", completeSignInPayload(['status' => SscStatus::Selesai->value]))
        ->assertRedirect();

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/lanjut-status", ['target_status' => CaseStatus::SiapOperasi->value])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($case->fresh()?->status)->toBe(CaseStatus::SiapOperasi);
});

test('kasus berstatus selesai mengunci pembaruan ruang/tim, cek alat, dan SSC Sign In', function () {
    $perawat = User::factory()->perawat()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();
    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::Selesai)->create([
        'anesthesiologist_id' => $anestesi->id,
        'created_by' => $perawat->id,
    ]);

    $this->actingAs($perawat)->put("/kasus/{$case->id}/persiapan/tim", [])->assertForbidden();
    $this->actingAs($perawat)->put("/kasus/{$case->id}/persiapan/alat", [])->assertForbidden();
    $this->actingAs($perawat)->put("/kasus/{$case->id}/ssc/sign-in", [])->assertForbidden();
    $this->actingAs($anestesi)->put("/kasus/{$case->id}/ssc/sign-in", [])->assertForbidden();
    $this->actingAs($anestesi)->post("/kasus/{$case->id}/ssc/sign-in/verifikasi", ['password' => 'Password123!'])->assertForbidden();
});
