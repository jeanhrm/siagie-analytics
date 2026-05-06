<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ImprovementPlanController;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/uploads', [UploadController::class, 'index'])
        ->name('uploads.index');
    Route::post('/uploads', [UploadController::class, 'store'])
        ->name('uploads.store');
    Route::delete('/uploads/{upload}', [UploadController::class, 'destroy'])
        ->name('uploads.destroy');

    Route::get('/analysis', [AnalysisController::class, 'index'])
        ->name('analysis.index');
    Route::post('/analysis/{upload}', [AnalysisController::class, 'generate'])
        ->name('analysis.generate');
    Route::get('/analysis/{report}', [AnalysisController::class, 'show'])
        ->name('analysis.show');
    Route::get('/analysis/institutional', [AnalysisController::class, 'institutional'])
        ->name('analysis.institutional')
        ->middleware('role:director');
    


    Route::get('/plans', [ImprovementPlanController::class, 'index'])
        ->name('plans.index');
    Route::post('/plans/{report}', [ImprovementPlanController::class, 'generate'])
        ->name('plans.generate');
    Route::get('/plans/{plan}', [ImprovementPlanController::class, 'show'])
        ->name('plans.show');
    Route::patch('/plans/{plan}', [ImprovementPlanController::class, 'update'])
        ->name('plans.update');

    Route::get('/chat', [ChatController::class, 'index'])
        ->name('chat.index');
    Route::post('/chat', [ChatController::class, 'send'])
        ->name('chat.send');

});

require __DIR__.'/auth.php';