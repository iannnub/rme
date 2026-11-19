<?php

use App\Http\Controllers\Admin\AnesthesiaTypeController;
use App\Http\Controllers\Admin\InstrumentController;
use App\Http\Controllers\Admin\MedicalDiagnosisController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SurgeryTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureUserIsActive::class, 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('pengguna/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::put('pengguna/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::prefix('master')->name('master.')->group(function (): void {
            Route::get('ruang', [RoomController::class, 'index'])->name('rooms.index');
            Route::post('ruang', [RoomController::class, 'store'])->name('rooms.store');
            Route::put('ruang/{room}', [RoomController::class, 'update'])->name('rooms.update');
            Route::patch('ruang/{room}/toggle-active', [RoomController::class, 'toggleActive'])->name('rooms.toggle-active');
            Route::delete('ruang/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

            Route::get('alat', [InstrumentController::class, 'index'])->name('instruments.index');
            Route::post('alat', [InstrumentController::class, 'store'])->name('instruments.store');
            Route::put('alat/{instrument}', [InstrumentController::class, 'update'])->name('instruments.update');
            Route::patch('alat/{instrument}/toggle-active', [InstrumentController::class, 'toggleActive'])->name('instruments.toggle-active');
            Route::delete('alat/{instrument}', [InstrumentController::class, 'destroy'])->name('instruments.destroy');

            Route::get('jenis-operasi', [SurgeryTypeController::class, 'index'])->name('surgery-types.index');
            Route::post('jenis-operasi', [SurgeryTypeController::class, 'store'])->name('surgery-types.store');
            Route::put('jenis-operasi/{surgeryType}', [SurgeryTypeController::class, 'update'])->name('surgery-types.update');
            Route::patch('jenis-operasi/{surgeryType}/toggle-active', [SurgeryTypeController::class, 'toggleActive'])->name('surgery-types.toggle-active');
            Route::delete('jenis-operasi/{surgeryType}', [SurgeryTypeController::class, 'destroy'])->name('surgery-types.destroy');

            Route::get('jenis-anestesi', [AnesthesiaTypeController::class, 'index'])->name('anesthesia-types.index');
            Route::post('jenis-anestesi', [AnesthesiaTypeController::class, 'store'])->name('anesthesia-types.store');
            Route::put('jenis-anestesi/{anesthesiaType}', [AnesthesiaTypeController::class, 'update'])->name('anesthesia-types.update');
            Route::patch('jenis-anestesi/{anesthesiaType}/toggle-active', [AnesthesiaTypeController::class, 'toggleActive'])->name('anesthesia-types.toggle-active');
            Route::delete('jenis-anestesi/{anesthesiaType}', [AnesthesiaTypeController::class, 'destroy'])->name('anesthesia-types.destroy');

            Route::get('diagnosis-medis', [MedicalDiagnosisController::class, 'index'])->name('medical-diagnoses.index');
            Route::post('diagnosis-medis', [MedicalDiagnosisController::class, 'store'])->name('medical-diagnoses.store');
            Route::put('diagnosis-medis/{medicalDiagnosis}', [MedicalDiagnosisController::class, 'update'])->name('medical-diagnoses.update');
            Route::patch('diagnosis-medis/{medicalDiagnosis}/toggle-active', [MedicalDiagnosisController::class, 'toggleActive'])->name('medical-diagnoses.toggle-active');
            Route::delete('diagnosis-medis/{medicalDiagnosis}', [MedicalDiagnosisController::class, 'destroy'])->name('medical-diagnoses.destroy');
        });
    });
