<?php

use App\Enums\CaseStatus;
use App\Enums\Gender;
use App\Enums\NurseType;
use App\Models\AnesthesiaType;
use App\Models\AuditActivity;
use App\Models\CaseSignoff;
use App\Models\MedicalDiagnosis;
use App\Models\Patient;
use App\Models\Room;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Inertia\Testing\AssertableInertia as Assert;

test('perawat dapat membuat kasus dari pasien baru maupun pasien lama dengan nomor kasus otomatis', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $diagnosis = MedicalDiagnosis::factory()->create(['name' => 'Apendisitis Akut']);
    $surgeryType = SurgeryType::factory()->create(['name' => 'Apendektomi']);
    $anesthesia = AnesthesiaType::factory()->create(['code' => 'SAB', 'name' => 'Spinal (SAB)']);

    $prefix = 'OK-'.now('Asia/Jakarta')->format('Ymd').'-';

    $this->actingAs($perawat)
        ->post('/kasus', [
            'patient_mode' => 'new',
            'rm_number' => 'rm-55001',
            'patient_name' => 'Siti Aminah',
            'birth_date' => '1992-07-10',
            'gender' => Gender::Female->value,
            'address' => 'Jl. Mawar No. 12',
            'phone' => '081333444555',
            'admitted_at' => now('Asia/Jakarta')->subHours(6)->format('Y-m-d\TH:i'),
            'origin_ward' => 'Mawar 1',
            'origin_class' => 'Kelas 1',
            'scheduled_at' => now('Asia/Jakarta')->addHours(3)->format('Y-m-d\TH:i'),
            'medical_diagnosis_id' => $diagnosis->id,
            'surgery_type_id' => $surgeryType->id,
            'surgery_site' => 'Abdomen kuadran kanan bawah',
            'anesthesia_type_id' => $anesthesia->id,
        ])
        ->assertRedirect();

    $patient = Patient::query()->where('rm_number', 'RM-55001')->firstOrFail();
    $firstCase = SurgeryCase::query()->where('patient_id', $patient->id)->firstOrFail();

    expect($firstCase->case_number)->toBe($prefix.'001');
    expect($firstCase->status)->toBe(CaseStatus::PreOp);
    expect($firstCase->created_by)->toBe($perawat->id);

    $this->actingAs($perawat)
        ->post('/kasus', [
            'patient_mode' => 'existing',
            'patient_id' => $patient->id,
            'admitted_at' => now('Asia/Jakarta')->subHours(2)->format('Y-m-d\TH:i'),
            'origin_ward' => 'Melati 2',
            'scheduled_at' => now('Asia/Jakarta')->addHours(5)->format('Y-m-d\TH:i'),
            'medical_diagnosis_id' => 'other',
            'medical_diagnosis_other' => 'Observasi Massa Abdomen',
            'surgery_type_id' => 'other',
            'surgery_type_other' => 'Laparoskopi Diagnostik',
            'surgery_site' => 'Abdomen',
        ])
        ->assertRedirect();

    $secondCase = SurgeryCase::query()
        ->where('patient_id', $patient->id)
        ->where('id', '!=', $firstCase->id)
        ->firstOrFail();

    expect($secondCase->case_number)->toBe($prefix.'002');
    expect($secondCase->diagnosisDisplay())->toBe('Observasi Massa Abdomen');
    expect($secondCase->surgeryTypeDisplay())->toBe('Laparoskopi Diagnostik');
});

test('daftar kasus mendukung filter status, tanggal, ruang, dokter, dan pencarian tanpa lazy loading', function () {
    $perawat = User::factory()->perawat()->create();
    $surgeonA = User::factory()->dokterOperator()->create(['name' => 'dr. Bagus, Sp.B']);
    $surgeonB = User::factory()->dokterOperator()->create(['name' => 'dr. Rina, Sp.OG']);
    $roomA = Room::factory()->create(['code' => 'OK 1']);
    $roomB = Room::factory()->create(['code' => 'OK 2']);

    $patientA = Patient::factory()->create(['rm_number' => 'RM-FLT-1', 'name' => 'Ahmad Filter', 'created_by' => $perawat->id]);
    $patientB = Patient::factory()->create(['rm_number' => 'RM-FLT-2', 'name' => 'Dewi Lain', 'created_by' => $perawat->id]);

    $caseA = SurgeryCase::factory()
        ->atStatus(CaseStatus::IntraOp)
        ->create([
            'patient_id' => $patientA->id,
            'case_number' => 'OK-FILTER-001',
            'scheduled_at' => '2026-11-20 08:30:00',
            'room_id' => $roomA->id,
            'surgeon_id' => $surgeonA->id,
            'created_by' => $perawat->id,
        ]);

    SurgeryCase::factory()
        ->atStatus(CaseStatus::PreOp)
        ->create([
            'patient_id' => $patientB->id,
            'case_number' => 'OK-FILTER-002',
            'scheduled_at' => '2026-11-21 10:00:00',
            'room_id' => $roomB->id,
            'surgeon_id' => $surgeonB->id,
            'created_by' => $perawat->id,
        ]);

    Model::preventLazyLoading();

    $this->actingAs($perawat)
        ->get("/kasus?q=Ahmad&status=intra_op&date=2026-11-20&room_id={$roomA->id}&surgeon_id={$surgeonA->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/index')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $caseA->id)
            ->where('cases.data.0.case_number', 'OK-FILTER-001')
        );
});

test('status kasus tidak bisa diubah lewat mass assignment maupun endpoint pembaruan biasa dan perubahan tercatat di riwayat', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id, 'origin_ward' => 'Mawar 1']);

    expect(fn () => $case->update(['status' => CaseStatus::Selesai->value]))
        ->toThrow(MassAssignmentException::class);
    expect($case->fresh()?->status)->toBe(CaseStatus::PreOp);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}", [
            'admitted_at' => $case->admitted_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'scheduled_at' => $case->scheduled_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'origin_ward' => 'Anggrek 2',
            'origin_class' => 'VIP',
            'status' => CaseStatus::Selesai->value,
        ])
        ->assertRedirect();

    $case->refresh();
    expect($case->origin_ward)->toBe('Anggrek 2');
    expect($case->status)->toBe(CaseStatus::PreOp);

    $this->actingAs($perawat)
        ->get("/kasus/{$case->id}/riwayat")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/history')
            ->has('logs.data', 2)
            ->where('logs.data.0.event', 'updated')
            ->where('logs.data.0.causer_name', $perawat->name)
        );
});

test('hak akses kasus membedakan peran dan mengunci kasus selesai serta melindungi log audit dan pengesahan', function () {
    $perawat = User::factory()->perawat()->create();
    $case = SurgeryCase::factory()->create(['created_by' => $perawat->id]);
    $completedCase = SurgeryCase::factory()->atStatus(CaseStatus::Selesai)->create(['created_by' => $perawat->id]);

    $this->actingAs($perawat)
        ->put("/kasus/{$completedCase->id}", [
            'admitted_at' => $completedCase->admitted_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'scheduled_at' => $completedCase->scheduled_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),
            'origin_ward' => 'ICU',
        ])
        ->assertForbidden();

    $readOnlyUsers = [
        User::factory()->dokterOperator()->create(),
        User::factory()->dokterAnestesi()->create(),
        User::factory()->kepalaRuangan()->create(),
    ];

    foreach ($readOnlyUsers as $user) {
        $this->actingAs($user)->get("/kasus/{$case->id}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canUpdate', false)
                ->where('canAdvance', false)
            );
        $this->actingAs($user)->get("/kasus/{$case->id}/riwayat")->assertOk();
        $this->actingAs($user)->get("/kasus/{$case->id}/pre-op")->assertOk();
        $this->actingAs($user)->get('/kasus/buat')->assertForbidden();
        $this->actingAs($user)->post('/kasus', [])->assertForbidden();
        $this->actingAs($user)->put("/kasus/{$case->id}", [])->assertForbidden();
    }

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/kasus')->assertForbidden();
    $this->actingAs($admin)->get("/kasus/{$case->id}")->assertForbidden();
    $this->actingAs($admin)->get("/kasus/{$case->id}/riwayat")->assertForbidden();
    $this->actingAs($admin)->get("/kasus/{$case->id}/pre-op")->assertForbidden();

    $activity = AuditActivity::query()->where('case_id', $case->id)->firstOrFail();
    expect(fn () => $activity->update(['description' => 'ubah paksa']))->toThrow(LogicException::class);
    expect(fn () => $activity->delete())->toThrow(LogicException::class);

    $signoff = CaseSignoff::factory()->create(['case_id' => $case->id, 'user_id' => $perawat->id]);
    expect(fn () => $signoff->delete())->toThrow(LogicException::class);
});
