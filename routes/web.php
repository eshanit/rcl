<?php

use App\Http\Controllers\Upload\ShowDataUploadFormController;
use App\Http\Controllers\Upload\UploadAndValidateDataController;
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

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
