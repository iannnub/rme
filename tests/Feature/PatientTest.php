<?php

use App\Enums\Gender;
use App\Enums\NurseType;
use App\Models\AuditActivity;
use App\Models\Patient;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('perawat dapat melihat, mencari, menambah, dan memperbarui pasien serta tercatat di audit log', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();

    $this->actingAs($perawat)
        ->post('/pasien', [
            'rm_number' => ' rm-100201 ',
            'name' => '  Budi Santoso  ',
            'birth_date' => '1988-05-14',
            'gender' => Gender::Male->value,
            'address' => 'Jl. Kesehatan No. 1',
            'phone' => '081234567890',
        ])
        ->assertRedirect();

    $patient = Patient::query()->where('rm_number', 'RM-100201')->first();
    expect($patient)->not->toBeNull();
    expect($patient?->name)->toBe('Budi Santoso');
    expect($patient?->created_by)->toBe($perawat->id);

    $createdLog = AuditActivity::query()
        ->where('subject_type', Patient::class)
        ->where('subject_id', $patient?->id)
        ->where('event', 'created')
        ->first();

    expect($createdLog)->not->toBeNull();
    expect($createdLog?->causer_id)->toBe($perawat->id);
    expect($createdLog?->log_name)->toBe('clinical');

    $this->actingAs($perawat)
        ->get('/pasien?q=Budi')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('patients/index')
            ->where('canManage', true)
            ->has('patients.data', 1)
            ->where('patients.data.0.rm_number', 'RM-100201')
        );

    $this->actingAs($perawat)
        ->getJson('/pasien/cari?q=100201')
        ->assertOk()
        ->assertJsonCount(1, 'patients')
        ->assertJsonPath('patients.0.rm_number', 'RM-100201');

    $this->actingAs($perawat)
        ->put("/pasien/{$patient?->id}", [
            'rm_number' => 'RM-100201',
            'name' => 'Budi Santoso Wijaya',
            'birth_date' => '1988-05-14',
            'gender' => Gender::Male->value,
            'address' => 'Jl. Kesehatan No. 2',
            'phone' => '081234567899',
        ])
        ->assertRedirect();

    expect($patient?->fresh()?->name)->toBe('Budi Santoso Wijaya');

    $updatedLog = AuditActivity::query()
        ->where('subject_type', Patient::class)
        ->where('subject_id', $patient?->id)
        ->where('event', 'updated')
        ->first();

    expect($updatedLog)->not->toBeNull();
    expect($updatedLog?->properties?->get('old')['name'] ?? null)->toBe('Budi Santoso');
    expect($updatedLog?->properties?->get('attributes')['name'] ?? null)->toBe('Budi Santoso Wijaya');
});

test('nomor rekam medis pasien harus unik dan menolak duplikat lintas penulisan huruf', function () {
    $perawat = User::factory()->perawat()->create();
    Patient::factory()->create(['rm_number' => 'RM-998877', 'created_by' => $perawat->id]);
    $otherPatient = Patient::factory()->create(['rm_number' => 'RM-112233', 'created_by' => $perawat->id]);

    $this->actingAs($perawat)
        ->post('/pasien', [
            'rm_number' => 'rm-998877',
            'name' => 'Pasien Duplikat',
            'birth_date' => '1990-01-01',
            'gender' => Gender::Female->value,
        ])
        ->assertSessionHasErrors('rm_number');

    $this->actingAs($perawat)
        ->put("/pasien/{$otherPatient->id}", [
            'rm_number' => 'RM-998877',
            'name' => $otherPatient->name,
            'birth_date' => $otherPatient->birth_date->format('Y-m-d'),
            'gender' => $otherPatient->gender->value,
        ])
        ->assertSessionHasErrors('rm_number');
});

test('perhitungan label usia pasien mendukung tahun, bulan, dan hari', function () {
    $adult = Patient::factory()->make(['birth_date' => now()->subYears(32)->subDays(5)->toDateString()]);
    $infant = Patient::factory()->make(['birth_date' => now()->subMonths(4)->toDateString()]);
    $newborn = Patient::factory()->make(['birth_date' => now()->subDays(9)->toDateString()]);

    expect($adult->ageLabel())->toBe('32 tahun');
    expect($infant->ageLabel())->toBe('4 bulan');
    expect($newborn->ageLabel())->toBe('9 hari');
});

test('dokter dan kepala ruangan hanya dapat membaca data pasien, sedangkan admin diblokir dan tidak ada penghapusan permanen', function () {
    $perawat = User::factory()->perawat()->create();
    $patient = Patient::factory()->create(['created_by' => $perawat->id]);

    $readOnlyUsers = [
        User::factory()->dokterOperator()->create(),
        User::factory()->dokterAnestesi()->create(),
        User::factory()->kepalaRuangan()->create(),
    ];

    foreach ($readOnlyUsers as $user) {
        $this->actingAs($user)
            ->get('/pasien')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canManage', false));

        $this->actingAs($user)->get('/pasien/cari?q=RM')->assertForbidden();
        $this->actingAs($user)->post('/pasien', [
            'rm_number' => 'RM-403',
            'name' => 'Ditolak',
            'birth_date' => '1990-01-01',
            'gender' => Gender::Male->value,
        ])->assertForbidden();
        $this->actingAs($user)->put("/pasien/{$patient->id}", [
            'rm_number' => $patient->rm_number,
            'name' => 'Ditolak',
            'birth_date' => '1990-01-01',
            'gender' => Gender::Male->value,
        ])->assertForbidden();

        expect($user->can('delete', $patient))->toBeFalse();
    }

    expect($perawat->can('delete', $patient))->toBeFalse();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/pasien')->assertForbidden();
    $this->actingAs($admin)->get('/pasien/cari?q=RM')->assertForbidden();
    $this->actingAs($admin)->post('/pasien', [])->assertForbidden();
    $this->actingAs($admin)->put("/pasien/{$patient->id}", [])->assertForbidden();
});
