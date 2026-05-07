<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ImprovementPlanController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminController;

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

    // Rutas de análisis — específicas PRIMERO, parámetros AL FINAL
    Route::get('/analysis', [AnalysisController::class, 'index'])
        ->name('analysis.index');

    Route::get('/analysis/institutional', [AnalysisController::class, 'institutional'])
        ->name('analysis.institutional');

    Route::post('/analysis/institutional/generate', [AnalysisController::class, 'generateInstitutional'])
        ->name('analysis.institutional.generate');

    Route::post('/analysis/{upload}', [AnalysisController::class, 'generate'])
        ->name('analysis.generate');

    Route::get('/analysis/{report}', [AnalysisController::class, 'show'])
        ->name('analysis.show');

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

    Route::delete('/chat/clear', [ChatController::class, 'clear'])
    ->name('chat.clear');

    // Rutas de administración
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/institutions', [AdminController::class, 'createInstitution'])->name('institutions.create');
});


});

require __DIR__.'/auth.php';