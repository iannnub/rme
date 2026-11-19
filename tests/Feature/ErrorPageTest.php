<?php

test('route yang tidak dikenal mengembalikan status 404 dengan komponen error-page', function () {
    $this->get('/halaman-tidak-ditemukan-404')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('errors/error-page')
            ->where('status', 404));
});
