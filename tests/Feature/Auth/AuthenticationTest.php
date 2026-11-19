<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('tamu di halaman utama diarahkan ke halaman login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('halaman login dapat ditampilkan tanpa tautan registrasi', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

test('pengguna aktif dapat login menggunakan username', function () {
    $user = User::factory()->create([
        'username' => 'ns.sirkuler1',
        'email' => 'sirkuler1@rs.test',
    ]);

    $response = $this->post('/login', [
        'login' => 'ns.sirkuler1',
        'password' => 'Password123!',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();

    $log = Activity::query()
        ->where('log_name', 'auth')
        ->where('event', 'login')
        ->where('causer_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
});

test('pengguna aktif dapat login menggunakan email', function () {
    $user = User::factory()->create([
        'username' => 'dr.operator1',
        'email' => 'operator1@rs.test',
    ]);

    $response = $this->post('/login', [
        'login' => 'operator1@rs.test',
        'password' => 'Password123!',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');
});

test('akun nonaktif ditolak saat login', function () {
    $user = User::factory()->inactive()->create([
        'username' => 'ns.nonaktif',
    ]);

    $response = $this->from('/login')->post('/login', [
        'login' => 'ns.nonaktif',
        'password' => 'Password123!',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('login');
});

test('login dengan sandi salah gagal dan tercatat di audit tanpa menyimpan password', function () {
    $user = User::factory()->create([
        'username' => 'ns.gagal',
    ]);

    $this->from('/login')->post('/login', [
        'login' => 'ns.gagal',
        'password' => 'SandiSalah999',
    ])->assertRedirect('/login')->assertSessionHasErrors('login');

    $this->assertGuest();

    $log = Activity::query()
        ->where('log_name', 'auth')
        ->where('event', 'login_failed')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->properties->get('login'))->toBe('ns.gagal');
    expect($log->properties->has('password'))->toBeFalse();
});

test('pengguna yang dinonaktifkan saat sesi berjalan langsung dikeluarkan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->update(['is_active' => false]);

    $this->actingAs($user->fresh())
        ->get('/dashboard')
        ->assertRedirect('/login');

    $this->assertGuest();
});

test('rate limit login memblokir percobaan ke-6 dalam satu menit', function () {
    User::factory()->create([
        'username' => 'ratelimit.user',
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $this->post('/login', [
            'login' => 'ratelimit.user',
            'password' => 'SalahTerus123',
        ])->assertRedirect();
    }

    $this->post('/login', [
        'login' => 'ratelimit.user',
        'password' => 'SalahTerus123',
    ])->assertTooManyRequests();
});

test('pengguna dapat logout dan tercatat di audit log', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');

    $log = Activity::query()
        ->where('log_name', 'auth')
        ->where('event', 'logout')
        ->where('causer_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
});

test('tidak ada route registrasi publik', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Pengguna Liar',
        'email' => 'liar@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertNotFound();
});

test('endpoint keep-alive hanya dapat diakses pengguna terautentikasi', function () {
    $this->post('/keep-alive')->assertRedirect('/login');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/keep-alive')
        ->assertNoContent();
});
