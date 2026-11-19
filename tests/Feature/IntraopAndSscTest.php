<?php

use App\Enums\BreathingPattern;
use App\Enums\CaseStatus;
use App\Enums\CountVerification;
use App\Enums\FluidKind;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Enums\NurseType;
use App\Enums\OxygenDevice;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SkinIntegrity;
use App\Enums\SpecimenPreservative;
use App\Enums\SpecimenPurpose;
use App\Enums\SscStatus;
use App\Enums\SurgicalPosition;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\InstrumentCount;
use App\Models\IntraopRecord;
use App\Models\Medication;
use App\Models\NursingAction;
use App\Models\Specimen;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Support\Facades\Hash;

function completeTimeOutPayload(array $overrides = []): array
{
    return array_merge([
        'status' => SscStatus::Selesai->value,
        'intro_circulating' => true,
        'intro_scrub' => true,
        'intro_anesthesia_nurse' => true,
        'intro_surgeon' => true,
        'intro_anesthesiologist' => true,
        'identity_procedure_site_confirmed' => true,
        'antibiotic_prophylaxis' => 'ya',
        'antibiotic_name' => 'Cefazolin 1g IV',
        'antibiotic_given_at' => '2026-11-19 08:30',
        'antibiotic_confirmed_by' => 'Ns. Sari',
        'nurse_sterility_confirmed' => true,
        'nurse_indicator_checked' => true,
        'equipment_problem' => 'tidak',
        'other_concern' => 'tidak',
        'nurse_note' => '',
        'imaging_needed' => 'tidak',
    ], $overrides);
}

function fiveCountsPayload(bool $withMismatch = false): array
{
    return [
        InstrumentCountItem::KasaSteril->value => [
            'initial_count' => 10,
            'additional_count' => 2,
            'final_count' => $withMismatch ? 11 : 12,
        ],
        InstrumentCountItem::KasaLaparotomi->value => [
            'initial_count' => 5,
            'additional_count' => 0,
            'final_count' => 5,
        ],
        InstrumentCountItem::Jarum->value => [
            'initial_count' => 4,
            'additional_count' => 1,
            'final_count' => 5,
        ],
        InstrumentCountItem::Pisau->value => [
            'initial_count' => 2,
            'additional_count' => 0,
            'final_count' => 2,
        ],
        InstrumentCountItem::InstrumenSet->value => [
            'initial_count' => 20,
            'additional_count' => 0,
            'final_count' => 20,
        ],
    ];
}

test('waktu mulai anestesi terkunci sebelum Sign In selesai, dan waktu mulai insisi terkunci sebelum Time Out selesai, serta urutan waktu harus menaik', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::IntraOp)->create([
        'created_by' => $perawat->id,
    ]);

    // 1. Tanpa Sign In Selesai -> anesthesia_start_at ditolak; tanpa Time Out Selesai -> incision_at ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/kronologi", [
            'entered_or_at' => '2026-11-19T08:00',
            'anesthesia_start_at' => '2026-11-19T08:10',
            'incision_at' => '2026-11-19T08:25',
        ])
        ->assertSessionHasErrors(['anesthesia_start_at', 'incision_at']);

    // 2. Selesaikan Sign In -> mulai anestesi terbuka, insisi masih terkunci
    SscSignIn::factory()->create([
        'case_id' => $case->id,
        'status' => SscStatus::Selesai,
        'created_by' => $perawat->id,
    ]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/kronologi", [
            'entered_or_at' => '2026-11-19T08:00',
            'anesthesia_start_at' => '2026-11-19T08:10',
            'incision_at' => '2026-11-19T08:25',
        ])
        ->assertSessionHasErrors('incision_at');

    // 3. Selesaikan Time Out -> tapi urutan waktu terbalik (insisi lebih awal dari anestesi) -> ditolak
    SscTimeOut::factory()->create([
        'case_id' => $case->id,
        'status' => SscStatus::Selesai,
        'created_by' => $perawat->id,
    ]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/kronologi", [
            'entered_or_at' => '2026-11-19T08:00',
            'anesthesia_start_at' => '2026-11-19T08:20',
            'incision_at' => '2026-11-19T08:15',
        ])
        ->assertSessionHasErrors('incision_at');

    // 4. Urutan waktu menaik dan kedua SSC Selesai -> berhasil disimpan
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/kronologi", [
            'operation_plan_actual' => 'Apendektomi Laparotomi',
            'entered_or_at' => '2026-11-19T08:00',
            'anesthesia_start_at' => '2026-11-19T08:10',
            'incision_at' => '2026-11-19T08:25',
            'surgery_end_at' => '2026-11-19T09:15',
            'left_or_at' => '2026-11-19T09:30',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $case->refresh();
    expect($case->incision_at)->not->toBeNull();
    expect($case->operation_plan_actual)->toBe('Apendektomi Laparotomi');
});

test('SSC Time Out memisahkan hak isi blok Dokter Operator, Dokter Anestesi, dan Perawat serta memvalidasi antibiotik', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $operator = User::factory()->dokterOperator()->create();
    $operatorLain = User::factory()->dokterOperator()->create();
    $anestesi = User::factory()->dokterAnestesi()->create();

    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::IntraOp)->create([
        'surgeon_id' => $operator->id,
        'anesthesiologist_id' => $anestesi->id,
        'created_by' => $perawat->id,
    ]);

    // Dokter operator lain tidak boleh mengisi Time Out
    $this->actingAs($operatorLain)
        ->put("/kasus/{$case->id}/ssc/time-out", ['surgeon_critical_steps' => 'tidak'])
        ->assertForbidden();

    // Dokter operator kasus mengisi blok operator (langkah kritis = ya tanpa uraian -> ditolak)
    $this->actingAs($operator)
        ->put("/kasus/{$case->id}/ssc/time-out", [
            'surgeon_critical_steps' => 'ya',
            'surgeon_critical_steps_text' => '',
            'surgeon_estimated_duration_min' => 90,
            'surgeon_estimated_blood_loss_ml' => 200,
        ])
        ->assertSessionHasErrors('surgeon_critical_steps_text');

    $this->actingAs($operator)
        ->put("/kasus/{$case->id}/ssc/time-out", [
            'surgeon_critical_steps' => 'ya',
            'surgeon_critical_steps_text' => 'Perlengkapan hemostasis untuk adhesi berat.',
            'surgeon_estimated_duration_min' => 90,
            'surgeon_estimated_blood_loss_ml' => 200,
        ])
        ->assertRedirect();

    // Dokter anestesi kasus mengisi blok anestesi
    $this->actingAs($anestesi)
        ->put("/kasus/{$case->id}/ssc/time-out", [
            'anesthesia_special_condition' => 'ya',
            'anesthesia_risk' => 'tidak',
            'anesthesia_note' => 'Hipertensi terkontrol, pantau TD ketat.',
        ])
        ->assertRedirect();

    // Perawat pilih antibiotik Ya tanpa nama -> ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/time-out", completeTimeOutPayload(['antibiotic_name' => '']))
        ->assertSessionHasErrors('antibiotic_name');

    // Perawat menyelesaikan Time Out -> blok dokter yang sudah diisi tetap terjaga
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/time-out", completeTimeOutPayload())
        ->assertRedirect();

    $timeOut = SscTimeOut::query()->where('case_id', $case->id)->first();
    expect($timeOut?->status)->toBe(SscStatus::Selesai);
    expect($timeOut?->surgeon_estimated_duration_min)->toBe(90);
    expect($timeOut?->anesthesia_note)->toBe('Hipertensi terkontrol, pantau TD ketat.');
});

test('pemantauan intra-op menyimpan posisi/area, TTV dengan validasi LPM oksigen, obat, cairan dengan total otomatis, dan 7 template tindakan', function () {
    $perawat = User::factory()->perawat(NurseType::Anestesi)->create();
    $anestesi = User::factory()->dokterAnestesi()->create();
    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::IntraOp)->create([
        'anesthesiologist_id' => $anestesi->id,
        'created_by' => $perawat->id,
    ]);

    // Posisi & Area
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/posisi", [
            'position' => SurgicalPosition::Supine->value,
            'pressure_protection' => ['ganjal_kepala_leher', 'safety_belt'],
            'skin_antiseptic' => ['povidone_iodine', 'alkohol_70'],
            'grounding_plate_site' => 'Paha kanan lateral',
            'skin_condition_pre' => SkinIntegrity::Utuh->value,
            'skin_condition_post' => SkinIntegrity::Utuh->value,
            'draping_done' => true,
            'warming' => ['blanket_warmer'],
            'room_temperature_c' => 21.5,
        ])
        ->assertRedirect();

    expect(IntraopRecord::query()->where('case_id', $case->id)->first()?->position)->toBe(SurgicalPosition::Supine);

    // TTV dengan nasal kanul tapi tanpa LPM -> ditolak (KF-8)
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/intra-op/ttv", [
            'recorded_at' => '2026-11-19T08:30',
            'bp_systolic' => 118,
            'bp_diastolic' => 76,
            'pulse' => 74,
            'temperature' => 36.4,
            'respiratory_rate' => 16,
            'spo2' => 99,
            'breathing_pattern' => BreathingPattern::Eupnea->value,
            'oxygen_device' => OxygenDevice::NasalKanul->value,
            'oxygen_flow_lpm' => null,
        ])
        ->assertSessionHasErrors('oxygen_flow_lpm');

    // TTV dengan LPM 3 -> berhasil
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/intra-op/ttv", [
            'recorded_at' => '2026-11-19T08:30',
            'bp_systolic' => 118,
            'bp_diastolic' => 76,
            'pulse' => 74,
            'temperature' => 36.4,
            'respiratory_rate' => 16,
            'spo2' => 99,
            'breathing_pattern' => BreathingPattern::Eupnea->value,
            'oxygen_device' => OxygenDevice::NasalKanul->value,
            'oxygen_flow_lpm' => 3,
        ])
        ->assertRedirect();

    expect(VitalSign::query()->where('case_id', $case->id)->count())->toBe(1);

    // Obat bisa ditambahkan oleh dokter anestesi kasus (Q4) maupun perawat
    $this->actingAs($anestesi)
        ->post("/kasus/{$case->id}/intra-op/obat", [
            'given_at' => '2026-11-19T08:12',
            'name' => 'Propofol',
            'dose' => '100 mg',
            'route' => 'IV',
        ])
        ->assertRedirect();

    expect(Medication::query()->where('case_id', $case->id)->count())->toBe(1);

    // Cairan intake & output
    $this->actingAs($perawat)->post("/kasus/{$case->id}/intra-op/cairan", [
        'kind' => FluidKind::Kristaloid->value,
        'volume_ml' => 1000,
    ])->assertRedirect();
    $this->actingAs($perawat)->post("/kasus/{$case->id}/intra-op/cairan", [
        'kind' => FluidKind::Perdarahan->value,
        'volume_ml' => 250,
    ])->assertRedirect();
    $this->actingAs($perawat)->post("/kasus/{$case->id}/intra-op/cairan", [
        'kind' => FluidKind::Urine->value,
        'volume_ml' => 150,
    ])->assertRedirect();

    expect((int) FluidRecord::query()->where('case_id', $case->id)->where('direction', 'intake')->sum('volume_ml'))->toBe(1000);
    expect((int) FluidRecord::query()->where('case_id', $case->id)->where('direction', 'output')->sum('volume_ml'))->toBe(400);

    // Template 7 tindakan keperawatan intra-op
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/intra-op/tindakan/template")
        ->assertRedirect();

    expect(NursingAction::query()->where('case_id', $case->id)->count())->toBe(7);

    // Halaman intra-op merender dengan sukses tanpa lazy loading
    $this->actingAs($perawat)->get("/kasus/{$case->id}/intra-op")->assertOk();
});

test('hitung instrumen menentukan status S/TS otomatis dan mewajibkan tindak lanjut SOP serta menghalangi Sign Out Terverifikasi bila belum tertangani', function () {
    $perawat = User::factory()->perawat(NurseType::Instrumen)->create();
    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::IntraOp)->create([
        'created_by' => $perawat->id,
    ]);

    // 1. Selisih hitungan kasa steril (10+2 ≠ 11 -> TS) dengan verifikasi lengkap_sesuai -> ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/hitung-instrumen", [
            'counts' => fiveCountsPayload(withMismatch: true),
            'count_verification' => CountVerification::LengkapSesuai->value,
            'count_discrepancy_actions' => [],
        ])
        ->assertSessionHasErrors(['count_verification', 'count_discrepancy_actions']);

    // 2. Selisih hitungan dengan verifikasi tidak_sesuai dan tindak lanjut SOP -> tersimpan dan status Kasa Steril = TS
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/hitung-instrumen", [
            'counts' => fiveCountsPayload(withMismatch: true),
            'count_verification' => CountVerification::TidakSesuai->value,
            'count_discrepancy_actions' => ['pencarian_ulang', 'lapor_operator'],
        ])
        ->assertRedirect();

    $kasa = InstrumentCount::query()->where('case_id', $case->id)->where('item', InstrumentCountItem::KasaSteril)->first();
    expect($kasa?->status)->toBe(InstrumentCountStatus::TidakSesuai);

    // 3. Sign Out Terverifikasi tanpa Q8 = sudah & catatan tindakan -> ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-out", [
            'status' => SignOutStatus::Terverifikasi->value,
            'q1_procedure_confirmed' => true,
            'q2_counts_correct' => false,
            'q3_specimen_labeled' => 'tidak_berlaku',
            'q4_equipment_problem' => false,
            'q5_recovery_discussed' => true,
            'extra_8_discrepancy_followup' => 'belum',
            'issues_and_actions' => '',
        ])
        ->assertSessionHasErrors('issues_and_actions');

    // 4. Sign Out Terverifikasi dengan Q8 = sudah & catatan tindakan -> diterima
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/ssc/sign-out", [
            'status' => SignOutStatus::Terverifikasi->value,
            'q1_procedure_confirmed' => true,
            'q2_counts_correct' => false,
            'q3_specimen_labeled' => 'tidak_berlaku',
            'q4_equipment_problem' => false,
            'q5_recovery_discussed' => true,
            'extra_8_discrepancy_followup' => 'sudah',
            'issues_and_actions' => 'Pencarian ulang berhasil menemukan 1 kasa di bawah meja instrumen; telah dilaporkan ke operator.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(SscSignOut::query()->where('case_id', $case->id)->first()?->status)->toBe(SignOutStatus::Terverifikasi);
});

test('spesimen dan komplikasi menegakkan validasi kondisional, pengesahan 4 pihak ketat sesuai tim, dan transisi intra_op ke pasca_op menggunakan data riil database', function () {
    $sirkuler = User::factory()->perawat(NurseType::Sirkuler)->create(['password' => Hash::make('Rahasia1234')]);
    $instrumen = User::factory()->perawat(NurseType::Instrumen)->create(['password' => Hash::make('Rahasia1234')]);
    $perawatAnestesi = User::factory()->perawat(NurseType::Anestesi)->create(['password' => Hash::make('Rahasia1234')]);
    $anestesi = User::factory()->dokterAnestesi()->create(['password' => Hash::make('Rahasia1234')]);
    $operator = User::factory()->dokterOperator()->create(['password' => Hash::make('Rahasia1234')]);

    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::IntraOp)->create([
        'circulating_nurse_id' => $sirkuler->id,
        'scrub_nurse_id' => $instrumen->id,
        'anesthesia_nurse_id' => $perawatAnestesi->id,
        'anesthesiologist_id' => $anestesi->id,
        'surgeon_id' => $operator->id,
        'stage_readiness_flags' => null,
        'created_by' => $sirkuler->id,
    ]);

    // Validasi kondisional spesimen & komplikasi
    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/intra-op/spesimen-komplikasi", [
            'has_specimen' => true,
            'has_complication' => true,
        ])
        ->assertSessionHasErrors(['specimen_type', 'origin_organ', 'purpose', 'preservative', 'complication_description', 'complication_handling']);

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/intra-op/spesimen-komplikasi", [
            'has_specimen' => true,
            'specimen_type' => 'Jaringan Apendiks',
            'origin_organ' => 'Apendiks Vermiformis',
            'purpose' => SpecimenPurpose::PatologiAnatomi->value,
            'preservative' => SpecimenPreservative::Formalin10->value,
            'has_complication' => false,
        ])
        ->assertRedirect();

    expect(Specimen::query()->where('case_id', $case->id)->first()?->has_specimen)->toBeTrue();

    // Lengkapi SSC Sign In, Time Out, kronologi waktu lengkap, hitungan akhir sesuai, dan Sign Out Terverifikasi
    SscSignIn::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai, 'created_by' => $sirkuler->id]);
    SscTimeOut::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai, 'created_by' => $sirkuler->id]);

    $this->actingAs($sirkuler)->put("/kasus/{$case->id}/intra-op/kronologi", [
        'entered_or_at' => '2026-11-19T08:00',
        'anesthesia_start_at' => '2026-11-19T08:10',
        'incision_at' => '2026-11-19T08:25',
        'surgery_end_at' => '2026-11-19T09:15',
        'left_or_at' => '2026-11-19T09:30',
    ])->assertRedirect();

    $this->actingAs($instrumen)->put("/kasus/{$case->id}/intra-op/hitung-instrumen", [
        'counts' => fiveCountsPayload(withMismatch: false),
        'count_verification' => CountVerification::LengkapSesuai->value,
        'count_discrepancy_actions' => [],
    ])->assertRedirect();

    // Karena ada spesimen, Sign Out dengan q3 = tidak_berlaku ditolak
    $this->actingAs($sirkuler)->put("/kasus/{$case->id}/ssc/sign-out", [
        'status' => SignOutStatus::Terverifikasi->value,
        'q1_procedure_confirmed' => true,
        'q2_counts_correct' => true,
        'q3_specimen_labeled' => 'tidak_berlaku',
        'q4_equipment_problem' => false,
        'q5_recovery_discussed' => true,
    ])->assertSessionHasErrors('q3_specimen_labeled');

    $this->actingAs($sirkuler)->put("/kasus/{$case->id}/ssc/sign-out", [
        'status' => SignOutStatus::Terverifikasi->value,
        'q1_procedure_confirmed' => true,
        'q2_counts_correct' => true,
        'q3_specimen_labeled' => 'ya',
        'q4_equipment_problem' => false,
        'q5_recovery_discussed' => true,
    ])->assertRedirect();

    // Pengesahan lintas peran ditolak (sirkuler tidak boleh sahkan bagian dokter_operator)
    $this->actingAs($sirkuler)
        ->post("/kasus/{$case->id}/intra-op/sahkan", [
            'role_label' => 'dokter_operator',
            'password' => 'Rahasia1234',
        ])
        ->assertSessionHasErrors('auth');

    // Baru 3 dari 4 pihak mengesahkan -> transisi intra_op -> pasca_op masih ditolak
    foreach ([
        'perawat_sirkuler' => $sirkuler,
        'perawat_instrumen' => $instrumen,
        'dokter_anestesi' => $anestesi,
    ] as $roleLabel => $signer) {
        $this->actingAs($signer)
            ->post("/kasus/{$case->id}/intra-op/sahkan", [
                'role_label' => $roleLabel,
                'password' => 'Rahasia1234',
            ])
            ->assertRedirect();
    }

    $this->actingAs($sirkuler)
        ->post("/kasus/{$case->id}/lanjut-status", ['target_status' => CaseStatus::PascaOp->value])
        ->assertSessionHasErrors('transition');

    // Pihak ke-4 (dokter_operator) mengesahkan -> transisi intra_op -> pasca_op BERHASIL!
    $this->actingAs($operator)
        ->post("/kasus/{$case->id}/intra-op/sahkan", [
            'role_label' => 'dokter_operator',
            'password' => 'Rahasia1234',
        ])
        ->assertRedirect();

    expect(
        CaseSignoff::query()
            ->where('case_id', $case->id)
            ->where('section', SignoffSection::IntraOp)
            ->where('kind', SignoffKind::Signoff)
            ->count()
    )->toBe(4);

    $this->actingAs($sirkuler)
        ->post("/kasus/{$case->id}/lanjut-status", ['target_status' => CaseStatus::PascaOp->value])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($case->fresh()?->status)->toBe(CaseStatus::PascaOp);
});
