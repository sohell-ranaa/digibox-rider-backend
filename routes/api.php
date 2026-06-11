<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DutyController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SystemEventController;
use App\Http\Controllers\Api\ReportController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Duty Management
    Route::post('/duty/start', [DutyController::class, 'start']);
    Route::post('/duty/stop', [DutyController::class, 'stop']);
    Route::get('/duty/current', [DutyController::class, 'current']);
    Route::get('/duty/history', [DutyController::class, 'history']);

    // Location Tracking
    Route::post('/locations/record', [LocationController::class, 'record']);
    Route::post('/locations/bulk', [LocationController::class, 'bulk']);
    Route::get('/locations/my-latest', [LocationController::class, 'myLatest']);

    // Installation Locations
    Route::get('/installations', [LocationController::class, 'installations']);

    // System Events
    Route::post('/events/record', [SystemEventController::class, 'record']);

    // Reports
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::get('/reports/installation-visits', [ReportController::class, 'installationVisits']);
});
