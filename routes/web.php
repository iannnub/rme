<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CasePdfController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HandoverController;
use App\Http\Controllers\IntraopController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PostopController;
use App\Http\Controllers\PreopController;
use App\Http\Controllers\PreparationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SscController;
use App\Http\Controllers\SurgeryCaseController;
use App\Http\Controllers\SurgeryCaseHistoryController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('keep-alive', function (Request $request) {
        if ($request->header('X-Inertia')) {
            return back();
        }

        return response()->noContent();
    })->name('session.keep-alive');

    Route::middleware('role:perawat')->group(function () {
        Route::get('pasien/cari', [PatientController::class, 'search'])->name('patients.search');
        Route::post('pasien', [PatientController::class, 'store'])->name('patients.store');
        Route::put('pasien/{patient}', [PatientController::class, 'update'])->name('patients.update');

        Route::get('kasus/buat', [SurgeryCaseController::class, 'create'])->name('cases.create');
        Route::post('kasus', [SurgeryCaseController::class, 'store'])->name('cases.store');
        Route::put('kasus/{surgeryCase}', [SurgeryCaseController::class, 'update'])
            ->whereNumber('surgeryCase')
            ->name('cases.update');
        Route::post('kasus/{surgeryCase}/lanjut-status', [SurgeryCaseController::class, 'advanceStatus'])
            ->whereNumber('surgeryCase')
            ->name('cases.advance-status');

        Route::put('kasus/{surgeryCase}/pre-op', [PreopController::class, 'update'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop.update');
        Route::post('kasus/{surgeryCase}/pre-op/sahkan', [PreopController::class, 'sign'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop.sign');
        Route::post('kasus/{surgeryCase}/pre-op/diagnosis', [PreopController::class, 'addDiagnosis'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop.add-diagnosis');
        Route::delete('kasus/{surgeryCase}/pre-op/diagnosis/{caseNursingDiagnosis}', [PreopController::class, 'removeDiagnosis'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop.remove-diagnosis');
        Route::post('kasus/{surgeryCase}/pre-op/intervensi/{caseInterventionItem}/toggle', [PreopController::class, 'toggleIntervention'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop.toggle-intervention');

        Route::put('kasus/{surgeryCase}/persiapan/tim', [PreparationController::class, 'updateTeam'])
            ->whereNumber('surgeryCase')
            ->name('cases.preparation.team');
        Route::put('kasus/{surgeryCase}/persiapan/alat', [PreparationController::class, 'updateInstruments'])
            ->whereNumber('surgeryCase')
            ->name('cases.preparation.instruments');

        Route::put('kasus/{surgeryCase}/ssc/sign-out', [SscController::class, 'updateSignOut'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.sign-out.update');

        Route::put('kasus/{surgeryCase}/intra-op/kronologi', [IntraopController::class, 'updateChronology'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.chronology');
        Route::put('kasus/{surgeryCase}/intra-op/posisi', [IntraopController::class, 'updatePosition'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.position');
        Route::post('kasus/{surgeryCase}/intra-op/ttv', [IntraopController::class, 'addVitalSign'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.vitals.store');
        Route::delete('kasus/{surgeryCase}/intra-op/ttv/{vitalSign}', [IntraopController::class, 'deleteVitalSign'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.vitals.destroy');
        Route::post('kasus/{surgeryCase}/intra-op/cairan', [IntraopController::class, 'addFluid'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.fluids.store');
        Route::delete('kasus/{surgeryCase}/intra-op/cairan/{fluidRecord}', [IntraopController::class, 'deleteFluid'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.fluids.destroy');
        Route::post('kasus/{surgeryCase}/intra-op/tindakan', [IntraopController::class, 'addNursingAction'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.actions.store');
        Route::post('kasus/{surgeryCase}/intra-op/tindakan/template', [IntraopController::class, 'applyTemplates'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.actions.templates');
        Route::delete('kasus/{surgeryCase}/intra-op/tindakan/{nursingAction}', [IntraopController::class, 'deleteNursingAction'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.actions.destroy');
        Route::put('kasus/{surgeryCase}/intra-op/hitung-instrumen', [IntraopController::class, 'saveInstrumentCounts'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.counts.update');
        Route::post('kasus/{surgeryCase}/intra-op/diagnosis', [IntraopController::class, 'addDiagnosis'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.add-diagnosis');
        Route::delete('kasus/{surgeryCase}/intra-op/diagnosis/{caseNursingDiagnosis}', [IntraopController::class, 'removeDiagnosis'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.remove-diagnosis');
        Route::post('kasus/{surgeryCase}/intra-op/intervensi/{caseInterventionItem}/toggle', [IntraopController::class, 'toggleIntervention'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.toggle-intervention');

        Route::post('kasus/{surgeryCase}/pasca-op/observasi', [PostopController::class, 'storeObservation'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.observations.store');
        Route::delete('kasus/{surgeryCase}/pasca-op/observasi/{postopObservation}', [PostopController::class, 'deleteObservation'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.observations.destroy');
        Route::put('kasus/{surgeryCase}/pasca-op/kondisi-pindah', [PostopController::class, 'saveSummary'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.summary');
        Route::post('kasus/{surgeryCase}/pasca-op/diagnosis', [PostopController::class, 'addDiagnosis'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.add-diagnosis');
        Route::delete('kasus/{surgeryCase}/pasca-op/diagnosis/{caseNursingDiagnosis}', [PostopController::class, 'removeDiagnosis'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.remove-diagnosis');
        Route::post('kasus/{surgeryCase}/pasca-op/intervensi/{caseInterventionItem}/toggle', [PostopController::class, 'toggleIntervention'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.toggle-intervention');

        Route::put('kasus/{surgeryCase}/serah-terima', [HandoverController::class, 'store'])
            ->whereNumber('surgeryCase')
            ->name('cases.handover.store');
    });

    Route::middleware('role:perawat,dokter_anestesi')->group(function () {
        Route::put('kasus/{surgeryCase}/ssc/sign-in', [SscController::class, 'updateSignIn'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.sign-in.update');
        Route::post('kasus/{surgeryCase}/intra-op/obat', [IntraopController::class, 'addMedication'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.medications.store');
        Route::delete('kasus/{surgeryCase}/intra-op/obat/{medication}', [IntraopController::class, 'deleteMedication'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.medications.destroy');
    });

    Route::middleware('role:perawat,dokter_operator,dokter_anestesi')->group(function () {
        Route::put('kasus/{surgeryCase}/ssc/time-out', [SscController::class, 'updateTimeOut'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.time-out.update');
        Route::put('kasus/{surgeryCase}/intra-op/spesimen-komplikasi', [IntraopController::class, 'updateSpecimenComplication'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.specimen-complication');
        Route::post('kasus/{surgeryCase}/intra-op/sahkan', [IntraopController::class, 'sign'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop.sign');
    });

    Route::middleware('role:dokter_anestesi')->group(function () {
        Route::post('kasus/{surgeryCase}/ssc/sign-in/verifikasi', [SscController::class, 'verifySignIn'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.sign-in.verify');
    });

    Route::middleware('role:dokter_operator,dokter_anestesi')->group(function () {
        Route::post('kasus/{surgeryCase}/pre-op/verifikasi', [PreopController::class, 'verify'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop.verify');
        Route::post('kasus/{surgeryCase}/ssc/sign-out/verifikasi', [SscController::class, 'verifySignOut'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.sign-out.verify');
        Route::post('kasus/{surgeryCase}/pasca-op/verifikasi', [PostopController::class, 'verify'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.verify');
        Route::post('kasus/{surgeryCase}/pasca-op/koreksi-dokter', [PostopController::class, 'doctorCorrection'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop.doctor-correction');
    });

    Route::middleware('role:perawat,dokter_operator,dokter_anestesi,kepala_ruangan')->group(function () {
        Route::get('pasien', [PatientController::class, 'index'])->name('patients.index');
        Route::get('kasus', [SurgeryCaseController::class, 'index'])->name('cases.index');
        Route::get('kasus/{surgeryCase}', [SurgeryCaseController::class, 'show'])
            ->whereNumber('surgeryCase')
            ->name('cases.show');
        Route::get('kasus/{surgeryCase}/riwayat', [SurgeryCaseHistoryController::class, 'index'])
            ->whereNumber('surgeryCase')
            ->name('cases.history');
        Route::get('kasus/{surgeryCase}/pre-op', [PreopController::class, 'show'])
            ->whereNumber('surgeryCase')
            ->name('cases.preop');
        Route::get('kasus/{surgeryCase}/persiapan', [PreparationController::class, 'show'])
            ->whereNumber('surgeryCase')
            ->name('cases.preparation');
        Route::get('kasus/{surgeryCase}/ssc', [SscController::class, 'index'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc');
        Route::get('kasus/{surgeryCase}/ssc/sign-in', [SscController::class, 'showSignIn'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.sign-in');
        Route::get('kasus/{surgeryCase}/ssc/time-out', [SscController::class, 'showTimeOut'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.time-out');
        Route::get('kasus/{surgeryCase}/ssc/sign-out', [SscController::class, 'showSignOut'])
            ->whereNumber('surgeryCase')
            ->name('cases.ssc.sign-out');
        Route::get('kasus/{surgeryCase}/intra-op', [IntraopController::class, 'show'])
            ->whereNumber('surgeryCase')
            ->name('cases.intraop');
        Route::get('kasus/{surgeryCase}/pasca-op', [PostopController::class, 'show'])
            ->whereNumber('surgeryCase')
            ->name('cases.postop');
        Route::get('kasus/{surgeryCase}/serah-terima', [HandoverController::class, 'show'])
            ->whereNumber('surgeryCase')
            ->name('cases.handover');
        Route::get('kasus/{surgeryCase}/pdf', [CasePdfController::class, 'full'])
            ->whereNumber('surgeryCase')
            ->middleware('throttle:30,1')
            ->name('cases.pdf.full');
        Route::get('kasus/{surgeryCase}/pdf/serah-terima', [CasePdfController::class, 'handover'])
            ->whereNumber('surgeryCase')
            ->middleware('throttle:30,1')
            ->name('cases.pdf.handover');
    });

    Route::middleware('role:kepala_ruangan')->group(function () {
        Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
