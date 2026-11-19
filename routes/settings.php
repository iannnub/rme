<?php

use App\Http\Controllers\Admin\HospitalSettingController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('pengaturan', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('pengaturan', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('pengaturan/sandi', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('pengaturan/sandi', [PasswordController::class, 'update'])->name('password.update');

    Route::get('pengaturan/tampilan', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    Route::middleware('role:admin')->group(function () {
        Route::get('pengaturan/rumah-sakit', [HospitalSettingController::class, 'edit'])->name('hospital-settings.edit');
        Route::put('pengaturan/rumah-sakit', [HospitalSettingController::class, 'update'])->name('hospital-settings.update');
    });
});
