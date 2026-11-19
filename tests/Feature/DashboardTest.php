<?php

use App\Models\User;

test('tamu diarahkan ke halaman login saat mengakses dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('pengguna aktif dapat mengakses dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});
