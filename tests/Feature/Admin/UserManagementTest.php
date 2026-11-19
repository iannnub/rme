<?php

use App\Enums\NurseType;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

test('admin dapat melihat daftar pengguna dengan pencarian dan filter', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    User::factory()->perawat(NurseType::Sirkuler)->create([
        'name' => 'Ns. Budi Sirkuler',
        'username' => 'ns.budi',
    ]);
    User::factory()->dokterOperator()->inactive()->create([
        'name' => 'dr. Nonaktif, Sp.B',
        'username' => 'dr.nonaktif',
    ]);

    $this->actingAs($admin)
        ->get('/admin/pengguna')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 3));

    $this->actingAs($admin)
        ->get('/admin/pengguna?q=ns.budi&role=perawat&status=aktif')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.username', 'ns.budi'));
});

test('admin dapat membuat akun pengguna baru termasuk username bertitik dan tercatat di audit tanpa password', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/pengguna', [
        'name' => 'Ns. Rina Sirkuler, S.Kep',
        'username' => 'ns.sirkuler3',
        'email' => 'rina.sirkuler@rs.test',
        'role' => 'perawat',
        'nurse_type' => 'sirkuler',
        'nip' => '199101012019022001',
        'password' => 'SandiKlinis2026',
        'password_confirmation' => 'SandiKlinis2026',
    ]);

    $response->assertSessionHasNoErrors()->assertSessionHas('success');

    $created = User::query()->where('username', 'ns.sirkuler3')->first();
    expect($created)->not->toBeNull();
    expect($created->role)->toBe(Role::Perawat);
    expect($created->nurse_type)->toBe(NurseType::Sirkuler);
    expect($created->is_active)->toBeTrue();

    $log = Activity::query()
        ->where('log_name', 'users')
        ->where('subject_type', User::class)
        ->where('subject_id', $created->id)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->properties->get('attributes'))->not->toHaveKey('password');
    expect($log->properties->get('attributes'))->not->toHaveKey('remember_token');
});

test('validasi kondisional peran dan kebijakan sandi ditegakkan saat membuat pengguna', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/pengguna', [
            'name' => 'Perawat Kurang Data',
            'username' => 'ns.kurang',
            'role' => 'perawat',
            'nurse_type' => '',
            'nip' => '',
            'password' => 'lemah',
            'password_confirmation' => 'lemah',
        ])
        ->assertSessionHasErrors(['nurse_type', 'nip', 'password']);

    $this->actingAs($admin)
        ->post('/admin/pengguna', [
            'name' => 'Dokter Tanpa SIP',
            'username' => 'dr.nosip',
            'role' => 'dokter_operator',
            'sip' => '',
            'password' => 'SandiKlinis2026',
            'password_confirmation' => 'SandiKlinis2026',
        ])
        ->assertSessionHasErrors(['sip']);
});

test('admin dapat mengedit pengguna dan mereset kata sandi', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->perawat(NurseType::Instrumen)->create([
        'username' => 'ns.instrumen9',
        'nip' => '199001012015011001',
    ]);

    $this->actingAs($admin)
        ->put("/admin/pengguna/{$user->id}", [
            'name' => 'dr. Baru Operator, Sp.B',
            'username' => 'ns.instrumen9',
            'email' => null,
            'role' => 'dokter_operator',
            'nurse_type' => 'instrumen',
            'nip' => '',
            'sip' => 'SIP.503/999/SP.B/2026',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->role)->toBe(Role::DokterOperator);
    expect($user->nurse_type)->toBeNull();
    expect($user->sip)->toBe('SIP.503/999/SP.B/2026');

    $this->actingAs($admin)
        ->put("/admin/pengguna/{$user->id}/reset-password", [
            'password' => 'ResetBaru2026',
            'password_confirmation' => 'ResetBaru2026',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Hash::check('ResetBaru2026', $user->fresh()->password))->toBeTrue();
});

test('admin dapat menonaktifkan pengguna lain tetapi dilarang menonaktifkan diri sendiri atau menghapus permanen', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->patch("/admin/pengguna/{$target->id}/toggle-active")
        ->assertSessionHas('success');

    expect($target->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch("/admin/pengguna/{$admin->id}/toggle-active")
        ->assertForbidden();

    expect($admin->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)
        ->delete("/admin/pengguna/{$target->id}")
        ->assertForbidden();

    expect(User::query()->find($target->id))->not->toBeNull();
});
