<?php

use App\Enums\AirwayPatency;
use App\Enums\AnalgesicResponse;
use App\Enums\BreathingPattern;
use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\DressingCondition;
use App\Enums\FluidColor;
use App\Enums\FluidOdor;
use App\Enums\GeneralCondition;
use App\Enums\HandoverDestination;
use App\Enums\NurseType;
use App\Enums\NursingPhase;
use App\Enums\OxygenDevice;
use App\Enums\PainCharacter;
use App\Enums\PatientResponse;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SutureCondition;
use App\Enums\VitalSignPhase;
use App\Models\AuditActivity;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\Handover;
use App\Models\NursingDiagnosis;
use App\Models\PostopObservation;
use App\Models\PostopSummary;
use App\Models\SurgeryCase;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Support\Facades\Hash;

function samplePostopObservationPayload(array $overrides = []): array
{
    return array_merge([
        'recorded_at' => '2026-11-19T10:15',
        'gcs_eye' => 4,
        'gcs_verbal' => 5,
        'gcs_motor' => 6,
        'general_condition' => GeneralCondition::Baik->value,
        'response' => PatientResponse::Stabil->value,
        'airway_patency' => AirwayPatency::Paten->value,
        'bp_systolic' => 122,
        'bp_diastolic' => 78,
        'pulse' => 80,
        'temperature' => 36.7,
        'respiratory_rate' => 18,
        'spo2' => 99,
        'breathing_pattern' => BreathingPattern::Eupnea->value,
        'oxygen_device' => OxygenDevice::NasalKanul->value,
        'oxygen_flow_lpm' => 2,
        'wound_location' => 'Abdomen kuadran kanan bawah',
        'dressing' => DressingCondition::Bersih->value,
        'bleeding_seepage' => false,
        'swelling_hematoma' => false,
        'suture_condition' => SutureCondition::RapiUtuh->value,
        'pain_nrs' => 3,
        'pain_location' => 'Area luka operasi',
        'pain_character' => PainCharacter::Tumpul->value,
        'pain_duration' => 'Intermiten',
        'analgesic_response' => AnalgesicResponse::Berkurang->value,
        'fluid_intake_ml' => 500,
        'urine_ml' => 200,
        'drain_ml' => 30,
        'drain_color' => FluidColor::MerahMuda->value,
        'drain_odor' => FluidOdor::TidakBerbau->value,
        'bleeding_ml' => 20,
        'anesthesia_recovery' => Consciousness::ComposMentis->value,
        'nausea_vomiting' => false,
        'shivering_hypothermia' => false,
        'complications' => [],
        'notes' => 'Pasien sadar penuh, hemodinamik stabil.',
    ], $overrides);
}

test('perawat dapat mencatat observasi pasca-op lengkap beserta TTV/oksigen, cairan/drain, dan diagnosis pasca-op', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::PascaOp)->create([
        'created_by' => $perawat->id,
    ]);

    // Validasi LPM wajib jika oxygen_device != tanpa_oksigen
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pasca-op/observasi", samplePostopObservationPayload(['oxygen_flow_lpm' => null]))
        ->assertSessionHasErrors('oxygen_flow_lpm');

    // Simpan observasi lengkap
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pasca-op/observasi", samplePostopObservationPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $obs = PostopObservation::query()->where('case_id', $case->id)->first();
    expect($obs)->not->toBeNull();
    expect($obs?->gcsTotal())->toBe(15);
    expect($obs?->pain_nrs)->toBe(3);

    expect(VitalSign::query()->where('case_id', $case->id)->where('phase', VitalSignPhase::Post)->count())->toBe(1);
    expect(FluidRecord::query()->where('case_id', $case->id)->where('phase', VitalSignPhase::Post)->count())->toBe(4);

    // Tambah diagnosis keperawatan pasca-op
    $diag = NursingDiagnosis::factory()->create([
        'code' => 'D.0077',
        'name' => 'Nyeri Akut',
        'phase' => NursingPhase::Post,
    ]);

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pasca-op/diagnosis", [
            'nursing_diagnosis_id' => $diag->id,
        ])
        ->assertRedirect();

    $this->actingAs($perawat)->get("/kasus/{$case->id}/pasca-op")->assertOk();
});

test('verifikasi dokter pasca-op tercatat dan ter-reset otomatis jika perawat mengubah data pasca-op', function () {
    $perawat = User::factory()->perawat()->create();
    $operator = User::factory()->dokterOperator()->create(['password' => Hash::make('Rahasia1234')]);

    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::PascaOp)->create([
        'surgeon_id' => $operator->id,
        'created_by' => $perawat->id,
    ]);

    // Dokter operator memverifikasi pasca-op
    $this->actingAs($operator)
        ->post("/kasus/{$case->id}/pasca-op/verifikasi", ['password' => 'Rahasia1234'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $verification = CaseSignoff::query()
        ->where('case_id', $case->id)
        ->where('section', SignoffSection::PostOp)
        ->where('kind', SignoffKind::Verification)
        ->first();
    expect($verification?->revoked_at)->toBeNull();

    // Perawat memperbarui kondisi sebelum pindah -> verifikasi dokter otomatis dibatalkan
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pasca-op/kondisi-pindah", [
            'ready_to_transfer' => true,
            'pre_transfer_condition_notes' => 'Pasien stabil dan layak dipindahkan ke ruang rawat inap.',
            'assessed_at' => '2026-11-19T11:00',
        ])
        ->assertRedirect();

    expect($verification?->fresh()?->revoked_at)->not->toBeNull();
});

test('serah terima hanya boleh dilakukan jika observasi pasca-op dan kondisi sebelum pindah tercatat, lalu menyelesaikan kasus dan mengunci edit perawat', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::PascaOp)->create([
        'stage_readiness_flags' => null,
        'created_by' => $perawat->id,
    ]);

    // 1. Tanpa observasi & kondisi sebelum pindah -> serah terima ditolak
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/serah-terima", [
            'destination' => HandoverDestination::RuangRawatInap->value,
            'destination_detail' => 'Ruang Melati 2',
            'documents_complete' => true,
            'received_by_name' => 'Ns. Budi Santoso',
            'handed_over_at' => '2026-11-19T11:30',
            'complete_case' => true,
        ])
        ->assertSessionHasErrors('handover');

    // 2. Tambah observasi, tetapi kondisi sebelum pindah dinyatakan ready tanpa catatan -> ditolak
    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pasca-op/observasi", samplePostopObservationPayload())
        ->assertRedirect();

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pasca-op/kondisi-pindah", [
            'ready_to_transfer' => true,
            'pre_transfer_condition_notes' => '',
        ])
        ->assertSessionHasErrors('pre_transfer_condition_notes');

    // 3. Simpan kondisi sebelum pindah dengan catatan lengkap
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pasca-op/kondisi-pindah", [
            'ready_to_transfer' => true,
            'pre_transfer_condition_notes' => 'GCS 15, jalan napas paten, TTV stabil, balutan bersih kering, layak dipindahkan.',
            'assessed_at' => '2026-11-19T11:15',
        ])
        ->assertRedirect();

    // 4. Halaman serah terima merender 12 item ringkasan otomatis
    $this->actingAs($perawat)->get("/kasus/{$case->id}/serah-terima")->assertOk();

    // 5. Simpan & selesaikan kasus
    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/serah-terima", [
            'destination' => HandoverDestination::RuangRawatInap->value,
            'destination_detail' => 'Ruang Melati 2 Bed 3',
            'documents_complete' => true,
            'received_by_name' => 'Ns. Budi Santoso, S.Kep',
            'handed_over_at' => '2026-11-19T11:30',
            'ongoing_issues' => 'Nyeri ringan NRS 3 pada area insisi.',
            'follow_up_instructions' => 'Pantau TTV setiap 1 jam dan lanjutkan analgetik sesuai order.',
            'complete_case' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Selesai);
    expect($case->completed_at)->not->toBeNull();

    $handover = Handover::query()->where('case_id', $case->id)->first();
    expect($handover)->not->toBeNull();
    expect(count($handover?->summary_snapshot ?? []))->toBe(12);
    expect($handover?->summary_snapshot)->toHaveKeys([
        'identitas_pasien',
        'diagnosis_medis',
        'jenis_operasi',
        'jenis_anestesi',
        'kondisi_kesadaran',
        'ttv_terakhir',
        'alat_bantu_pernapasan',
        'obat_dan_cairan',
        'jumlah_perdarahan',
        'kondisi_luka',
        'keluhan_atau_masalah',
        'instruksi_tindak_lanjut',
    ]);

    // 6. Setelah status selesai -> seluruh edit perawat terkunci (403)
    $this->actingAs($perawat)->post("/kasus/{$case->id}/pasca-op/observasi", samplePostopObservationPayload())->assertForbidden();
    $this->actingAs($perawat)->put("/kasus/{$case->id}/pasca-op/kondisi-pindah", ['ready_to_transfer' => true])->assertForbidden();
    $this->actingAs($perawat)->put("/kasus/{$case->id}/serah-terima", ['received_by_name' => 'Ubah'])->assertForbidden();
});

test('koreksi dokter setelah kasus selesai mewajibkan alasan, menolak non-dokter kasus, dan mencatat alasan di audit log', function () {
    $perawat = User::factory()->perawat()->create();
    $operatorKasus = User::factory()->dokterOperator()->create();
    $operatorLain = User::factory()->dokterOperator()->create();
    $kepala = User::factory()->kepalaRuangan()->create();

    $case = SurgeryCase::factory()->withTeam()->atStatus(CaseStatus::Selesai)->create([
        'surgeon_id' => $operatorKasus->id,
        'created_by' => $perawat->id,
    ]);

    PostopSummary::factory()->create([
        'case_id' => $case->id,
        'pre_transfer_condition_notes' => 'Catatan awal sebelum koreksi dokter.',
        'created_by' => $perawat->id,
    ]);

    // Perawat, kepala ruangan, dan dokter operator lain ditolak (403)
    $this->actingAs($perawat)->post("/kasus/{$case->id}/pasca-op/koreksi-dokter", [])->assertForbidden();
    $this->actingAs($kepala)->post("/kasus/{$case->id}/pasca-op/koreksi-dokter", [])->assertForbidden();
    $this->actingAs($operatorLain)->post("/kasus/{$case->id}/pasca-op/koreksi-dokter", [
        'pre_transfer_condition_notes' => 'Coba ubah',
        'reason' => 'Bukan dokter kasus',
    ])->assertForbidden();

    // Dokter operator kasus tanpa alasan -> ditolak validasi
    $this->actingAs($operatorKasus)
        ->post("/kasus/{$case->id}/pasca-op/koreksi-dokter", [
            'pre_transfer_condition_notes' => 'Klarifikasi instruksi antibiotik lanjutan 24 jam.',
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');

    // Dokter operator kasus dengan alasan -> berhasil & masuk audit log dengan alasan
    $alasan = 'Klarifikasi hasil evaluasi luka dan tambahan instruksi antibiotik pasca-operasi.';
    $this->actingAs($operatorKasus)
        ->post("/kasus/{$case->id}/pasca-op/koreksi-dokter", [
            'pre_transfer_condition_notes' => 'Luka bersih kering, tambahkan evaluasi drain tiap 4 jam.',
            'operation_plan_actual' => 'Apendektomi Laparotomi Eksplorasi',
            'reason' => $alasan,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($case->postopSummary?->fresh()?->pre_transfer_condition_notes)
        ->toBe('Luka bersih kering, tambahkan evaluasi drain tiap 4 jam.');

    $audit = AuditActivity::query()
        ->where('case_id', $case->id)
        ->where('subject_type', PostopSummary::class)
        ->where('reason', $alasan)
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit?->causer_id)->toBe($operatorKasus->id);
    expect($audit?->properties?->get('old')['pre_transfer_condition_notes'] ?? null)
        ->toBe('Catatan awal sebelum koreksi dokter.');
    expect($audit?->properties?->get('attributes')['pre_transfer_condition_notes'] ?? null)
        ->toBe('Luka bersih kering, tambahkan evaluasi drain tiap 4 jam.');
});
