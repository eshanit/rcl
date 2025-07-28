<?php

use App\Http\Controllers\Report\DashboardController;
use App\Http\Controllers\Report\GroupedAnalysisController;
use App\Http\Controllers\Report\IndicatorAnalysisController;
use App\Http\Controllers\Upload\ImportDataController;
use App\Http\Controllers\Upload\ShowDataUploadFormController;
use App\Http\Controllers\Upload\UploadAndValidateDataController;
use App\Http\Controllers\Upload\UploadFormGetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('upload', ShowDataUploadFormController::class)
        ->name('upload.show');

    Route::post('/upload/patients', [UploadAndValidateDataController::class, 'uploadPatients'])
        ->name('upload.patients');

    Route::post('/upload/visits', [UploadAndValidateDataController::class, 'uploadVisits'])
        ->name('upload.visits');

    // Cross-file validation endpoint
    Route::post('/upload/validate', [UploadAndValidateDataController::class, 'validateFiles'])
        ->name('upload.validate');

    Route::post('/import-data', [ImportDataController::class, 'import'])
        ->name('import.data');

    /**
     * This is to cater for page refreshes
     */
    Route::get('/upload/patients', UploadFormGetController::class);

    Route::get('/upload/visits', UploadFormGetController::class);

    Route::get('/upload/validate', UploadFormGetController::class);

    /**
     *  Reports
     */
    Route::prefix('reports')->group(function () {
        Route::get('indicator-analysis', [IndicatorAnalysisController::class, 'index'])->name('reports.indicators.index');
        Route::get('indicator-analysis/{indicator}', [IndicatorAnalysisController::class, 'analyze'])->name('reports.indicators');
        Route::get('/', DashboardController::class)->name('reports.index');
        Route::get('/grouped', GroupedAnalysisController::class)->name('reports.grouped');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
