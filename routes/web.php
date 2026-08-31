<?php

use App\Http\Controllers\Admin\WorkTimeReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KioskWorkTimeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkTimeRecordController;
use App\Http\Controllers\AbsenceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KioskWorkTimeController::class, 'index'])
    ->name('kiosk.index');

Route::post('/fichaje-pin', [KioskWorkTimeController::class, 'verify'])
    ->name('kiosk.verify');

Route::get('/fichaje-pin/{token}', [KioskWorkTimeController::class, 'show'])
    ->name('kiosk.show');

Route::post('/fichaje-pin/{token}/entrada', [KioskWorkTimeController::class, 'clockIn'])
    ->name('kiosk.clock-in');

Route::post('/fichaje-pin/{token}/salida', [KioskWorkTimeController::class, 'clockOut'])
    ->name('kiosk.clock-out');

Route::post('/fichaje-pin/{token}/finalizar-salida', [KioskWorkTimeController::class, 'finishExit'])
    ->name('kiosk.finish-exit');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/fichaje', [WorkTimeRecordController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('work-time.index');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/reports/weekly', [WorkTimeReportController::class, 'weekly'])
        ->name('reports.weekly');

    Route::get('/reports/weekly/export', [WorkTimeReportController::class, 'exportWeekly'])
    ->name('reports.weekly.export');

	Route::get('/reports/weekly/export/pdf', [WorkTimeReportController::class, 'exportWeeklyPdf'])
		->name('reports.weekly.export.pdf');

	Route::get('/reports/monthly/export/pdf', [WorkTimeReportController::class, 'exportMonthlyPdf'])
		->name('reports.monthly.export.pdf');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::post('/fichajes/entrada', [WorkTimeRecordController::class, 'clockIn'])
        ->name('work-time.clock-in');

    Route::post('/fichajes/salida', [WorkTimeRecordController::class, 'clockOut'])
        ->name('work-time.clock-out');

	Route::post('/fichajes/finalizar-salida', [WorkTimeRecordController::class, 'finishExit'])
		->name('work-time.finish-exit');

    Route::prefix('ausencias')
        ->name('absence-requests.')
        ->group(function () {

            Route::get('/', [AbsenceRequestController::class, 'index'])
                ->name('index');

            Route::get('/crear', [AbsenceRequestController::class, 'create'])
                ->name('create');

            Route::post('/', [AbsenceRequestController::class, 'store'])
                ->name('store');

        });

});
require __DIR__.'/auth.php';
