<?php

use App\Models\User;

test('halaman pengaturan profil dapat ditampilkan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/pengaturan')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/profile'));
});

test('informasi profil dapat diperbarui dengan flash sukses', function () {
    $user = User::factory()->create([
        'role' => 'perawat',
    ]);

    $response = $this->actingAs($user)
        ->patch('/pengaturan', [
            'name' => 'Ns. Budi Santoso',
            'email' => 'budi.santoso@rs.test',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect('/pengaturan');

    $user->refresh();

    expect($user->name)->toBe('Ns. Budi Santoso');
    expect($user->email)->toBe('budi.santoso@rs.test');
});

test('email profil boleh dikosongkan karena bersifat opsional', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch('/pengaturan', [
            'name' => 'Ns. Sinta',
            'email' => null,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/pengaturan');
    expect($user->fresh()->email)->toBeNull();
});
