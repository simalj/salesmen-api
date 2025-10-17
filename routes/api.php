<?php

use App\Http\Controllers\CodelistController;
use App\Http\Controllers\SalesmanController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\DatabasePerformanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API with rate limiting and versioning
Route::middleware(['throttle:api'])->group(function () {
    // Version 1 - Explicit versioning for future compatibility
    Route::prefix('v1')->name('v1.')->group(function () {
        Route::apiResource('salesmen', SalesmanController::class)->parameters([
            'salesmen' => 'salesman'
        ]);
        Route::get('codelists', [CodelistController::class, 'index']);
        
        // Database performance monitoring routes
        Route::prefix('database')->group(function () {
            Route::get('performance', [DatabasePerformanceController::class, 'getPerformanceMetrics']);
            Route::get('slow-queries', [DatabasePerformanceController::class, 'getSlowQueries']);
            Route::post('optimize', [DatabasePerformanceController::class, 'optimizeDatabase']);
        });
        
        // Cache management endpoints (for debugging/monitoring)
        Route::prefix('cache')->group(function () {
            Route::get('stats', [CacheController::class, 'stats']);
            Route::delete('clear', [CacheController::class, 'clear']);
        });

        // Metrics and monitoring endpoints
        Route::prefix('metrics')->group(function () {
            Route::get('performance', [MetricsController::class, 'performance']);
            Route::get('requests', [MetricsController::class, 'requests']);
            Route::get('database', [MetricsController::class, 'database']);
        });
    });

    // Current version (default to v1 for backward compatibility)
    Route::apiResource('salesmen', SalesmanController::class)->parameters([
        'salesmen' => 'salesman'
    ]);
    Route::get('codelists', [CodelistController::class, 'index']);
});

// Health check endpoint (no rate limiting)
Route::get('health', [\App\Http\Controllers\HealthController::class, 'check']);