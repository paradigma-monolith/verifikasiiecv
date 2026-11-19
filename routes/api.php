<?php

use App\Http\Controllers\Api\EicvVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('verifications')->group(function (): void {
    Route::get('/stats', [EicvVerificationController::class, 'stats']);
    Route::get('/export/csv', [EicvVerificationController::class, 'exportCsv']);
    Route::get('/', [EicvVerificationController::class, 'index']);
    Route::post('/verify', [EicvVerificationController::class, 'verify']);
    Route::get('/{verification}', [EicvVerificationController::class, 'show'])->whereNumber('verification');
    Route::post('/{verification}/recheck', [EicvVerificationController::class, 'recheck'])->whereNumber('verification');
    Route::delete('/{verification}', [EicvVerificationController::class, 'destroy'])->whereNumber('verification');
});
