<?php

use App\Enums\NurseType;
use App\Models\AnesthesiaType;
use App\Models\Instrument;
use App\Models\MedicalDiagnosis;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SurgeryType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

function makeUsersByRole(): array
{
    return [
        'admin' => User::factory()->admin()->create(),
        'kepala_ruangan' => User::factory()->kepalaRuangan()->create(),
        'perawat' => User::factory()->perawat(NurseType::Sirkuler)->create(),
        'dokter_operator' => User::factory()->dokterOperator()->create(),
        'dokter_anestesi' => User::factory()->dokterAnestesi()->create(),
    ];
}

test('admin diblokir dari seluruh route dan gate klinis, laporan, serta audit', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/kasus')->assertForbidden();
    $this->actingAs($admin)->get('/kasus/buat')->assertForbidden();
    $this->actingAs($admin)->get('/pasien')->assertForbidden();
    $this->actingAs($admin)->get('/laporan')->assertForbidden();
    $this->actingAs($admin)->get('/audit')->assertForbidden();

    expect(Gate::forUser($admin)->allows('clinical.view'))->toBeFalse();
    expect(Gate::forUser($admin)->allows('clinical.create_case'))->toBeFalse();
    expect(Gate::forUser($admin)->allows('clinical.edit_nursing'))->toBeFalse();
    expect(Gate::forUser($admin)->allows('clinical.signoff_intraop'))->toBeFalse();
    expect(Gate::forUser($admin)->allows('clinical.export_pdf'))->toBeFalse();
    expect(Gate::forUser($admin)->allows('reports.view'))->toBeFalse();
    expect(Gate::forUser($admin)->allows('audit.view'))->toBeFalse();

    expect(Gate::forUser($admin)->allows('users.manage'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('master.manage'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('master.view'))->toBeTrue();
});

test('non-admin ditolak pada seluruh endpoint manajemen pengguna, master data, dan pengaturan rumah sakit', function () {
    $users = makeUsersByRole();
    $room = Room::factory()->create();
    $instrument = Instrument::factory()->create();
    $surgeryType = SurgeryType::factory()->create();
    $anesthesiaType = AnesthesiaType::factory()->create();
    $medicalDiagnosis = MedicalDiagnosis::factory()->create();
    $targetUser = User::factory()->create();

    $nonAdmins = [
        $users['kepala_ruangan'],
        $users['perawat'],
        $users['dokter_operator'],
        $users['dokter_anestesi'],
    ];

    foreach ($nonAdmins as $actor) {
        $this->actingAs($actor)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($actor)->post('/admin/pengguna', [])->assertForbidden();
        $this->actingAs($actor)->put("/admin/pengguna/{$targetUser->id}", [])->assertForbidden();
        $this->actingAs($actor)->patch("/admin/pengguna/{$targetUser->id}/toggle-active")->assertForbidden();
        $this->actingAs($actor)->put("/admin/pengguna/{$targetUser->id}/reset-password", [])->assertForbidden();

        $masterRoutes = [
            ['/admin/master/ruang', $room->id],
            ['/admin/master/alat', $instrument->id],
            ['/admin/master/jenis-operasi', $surgeryType->id],
            ['/admin/master/jenis-anestesi', $anesthesiaType->id],
            ['/admin/master/diagnosis-medis', $medicalDiagnosis->id],
        ];

        foreach ($masterRoutes as [$base, $id]) {
            $this->actingAs($actor)->get($base)->assertForbidden();
            $this->actingAs($actor)->post($base, [])->assertForbidden();
            $this->actingAs($actor)->put("{$base}/{$id}", [])->assertForbidden();
            $this->actingAs($actor)->patch("{$base}/{$id}/toggle-active")->assertForbidden();
        }

        $this->actingAs($actor)->get('/pengaturan/rumah-sakit')->assertForbidden();
        $this->actingAs($actor)->put('/pengaturan/rumah-sakit', [])->assertForbidden();

        expect(Gate::forUser($actor)->allows('users.manage'))->toBeFalse();
        expect(Gate::forUser($actor)->allows('master.manage'))->toBeFalse();
    }
});

test('hanya kepala ruangan yang boleh mengakses laporan dan audit log', function () {
    $users = makeUsersByRole();

    $this->actingAs($users['kepala_ruangan'])->get('/laporan')->assertOk();
    $this->actingAs($users['kepala_ruangan'])->get('/audit')->assertOk();
    expect(Gate::forUser($users['kepala_ruangan'])->allows('reports.view'))->toBeTrue();
    expect(Gate::forUser($users['kepala_ruangan'])->allows('audit.view'))->toBeTrue();

    foreach (['admin', 'perawat', 'dokter_operator', 'dokter_anestesi'] as $role) {
        $this->actingAs($users[$role])->get('/laporan')->assertForbidden();
        $this->actingAs($users[$role])->get('/audit')->assertForbidden();
        expect(Gate::forUser($users[$role])->allows('reports.view'))->toBeFalse();
        expect(Gate::forUser($users[$role])->allows('audit.view'))->toBeFalse();
    }
});

test('hanya perawat yang boleh mengakses pembuatan kasus operasi baru', function () {
    $users = makeUsersByRole();

    $this->actingAs($users['perawat'])->get('/kasus/buat')->assertOk();
    expect(Gate::forUser($users['perawat'])->allows('clinical.create_case'))->toBeTrue();
    expect(Gate::forUser($users['perawat'])->allows('clinical.edit_nursing'))->toBeTrue();

    foreach (['admin', 'kepala_ruangan', 'dokter_operator', 'dokter_anestesi'] as $role) {
        $this->actingAs($users[$role])->get('/kasus/buat')->assertForbidden();
        expect(Gate::forUser($users[$role])->allows('clinical.create_case'))->toBeFalse();
        expect(Gate::forUser($users[$role])->allows('clinical.edit_nursing'))->toBeFalse();
    }
});

test('semua peran klinis dan kepala dapat mengakses daftar kasus dan pasien tetapi kepala tidak bisa mengesahkan intra-op', function () {
    $users = makeUsersByRole();

    foreach (['kepala_ruangan', 'perawat', 'dokter_operator', 'dokter_anestesi'] as $role) {
        $this->actingAs($users[$role])->get('/kasus')->assertOk();
        $this->actingAs($users[$role])->get('/pasien')->assertOk();
        expect(Gate::forUser($users[$role])->allows('clinical.view'))->toBeTrue();
        expect(Gate::forUser($users[$role])->allows('clinical.export_pdf'))->toBeTrue();
    }

    expect(Gate::forUser($users['kepala_ruangan'])->allows('clinical.signoff_intraop'))->toBeFalse();
    expect(Gate::forUser($users['admin'])->allows('clinical.signoff_intraop'))->toBeFalse();

    expect(Gate::forUser($users['perawat'])->allows('clinical.signoff_intraop'))->toBeTrue();
    expect(Gate::forUser($users['dokter_operator'])->allows('clinical.signoff_intraop'))->toBeTrue();
    expect(Gate::forUser($users['dokter_anestesi'])->allows('clinical.signoff_intraop'))->toBeTrue();
});

test('policy master data dan pengguna menegakkan hak akses baca, ubah, dan larangan hapus permanen', function () {
    $users = makeUsersByRole();

    $models = [
        Room::factory()->create(),
        Instrument::factory()->create(),
        SurgeryType::factory()->create(),
        AnesthesiaType::factory()->create(),
        MedicalDiagnosis::factory()->create(),
    ];

    foreach ($models as $model) {
        expect($users['admin']->can('viewAny', $model::class))->toBeTrue();
        expect($users['admin']->can('create', $model::class))->toBeTrue();
        expect($users['admin']->can('update', $model))->toBeTrue();
        expect($users['admin']->can('toggleActive', $model))->toBeTrue();
        expect($users['admin']->can('delete', $model))->toBeFalse();

        foreach (['kepala_ruangan', 'perawat', 'dokter_operator', 'dokter_anestesi'] as $role) {
            expect($users[$role]->can('viewAny', $model::class))->toBeTrue();
            expect($users[$role]->can('view', $model))->toBeTrue();
            expect($users[$role]->can('create', $model::class))->toBeFalse();
            expect($users[$role]->can('update', $model))->toBeFalse();
            expect($users[$role]->can('toggleActive', $model))->toBeFalse();
            expect($users[$role]->can('delete', $model))->toBeFalse();
        }
    }

    expect($users['admin']->can('delete', $users['perawat']))->toBeFalse();
    expect($users['admin']->can('viewAny', Setting::class))->toBeTrue();
    expect($users['perawat']->can('viewAny', Setting::class))->toBeFalse();
});
