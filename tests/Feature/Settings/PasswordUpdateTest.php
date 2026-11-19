<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('halaman ubah kata sandi dapat ditampilkan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/pengaturan/sandi')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/password'));
});

test('kata sandi dapat diperbarui jika memenuhi kebijakan minimal 10 karakter huruf dan angka', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/pengaturan/sandi')
        ->put('/pengaturan/sandi', [
            'current_password' => 'Password123!',
            'password' => 'SandiBaru2026',
            'password_confirmation' => 'SandiBaru2026',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect('/pengaturan/sandi');

    expect(Hash::check('SandiBaru2026', $user->fresh()->password))->toBeTrue();
});

test('kata sandi lemah atau kurang dari 10 karakter ditolak', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/pengaturan/sandi')
        ->put('/pengaturan/sandi', [
            'current_password' => 'Password123!',
            'password' => 'pendek12',
            'password_confirmation' => 'pendek12',
        ])
        ->assertSessionHasErrors('password')
        ->assertRedirect('/pengaturan/sandi');

    $this->actingAs($user)
        ->from('/pengaturan/sandi')
        ->put('/pengaturan/sandi', [
            'current_password' => 'Password123!',
            'password' => 'tanpaangkadisini',
            'password_confirmation' => 'tanpaangkadisini',
        ])
        ->assertSessionHasErrors('password')
        ->assertRedirect('/pengaturan/sandi');
});

test('kata sandi saat ini yang salah ditolak', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/pengaturan/sandi')
        ->put('/pengaturan/sandi', [
            'current_password' => 'SalahSandi123',
            'password' => 'SandiBaru2026',
            'password_confirmation' => 'SandiBaru2026',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/pengaturan/sandi');
});
