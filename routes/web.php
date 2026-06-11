<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TrackingController;
use App\Http\Controllers\Web\RiderController;
use App\Http\Controllers\Web\InstallationController;
use App\Http\Controllers\Web\ProfileController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard Routes (Protected)
Route::middleware(['auth:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.home');

    // Live Tracking
    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::get('/tracking/riders', [TrackingController::class, 'getActiveRiders'])->name('tracking.riders');
    Route::get('/tracking/all-riders', [TrackingController::class, 'getAllRidersStatus'])->name('tracking.all-riders');
    Route::get('/tracking/rider/{id}', [TrackingController::class, 'getRiderLocation'])->name('tracking.rider');
    Route::get('/tracking/historical', [TrackingController::class, 'getHistoricalData'])->name('tracking.historical');

    // Riders Management
    Route::resource('riders', RiderController::class);

    // Installation Locations
    Route::get('/installations', [InstallationController::class, 'index'])->name('installations.index');
    Route::get('/installations/create', [InstallationController::class, 'create'])->name('installations.create');
    Route::post('/installations', [InstallationController::class, 'store'])->name('installations.store');
    Route::get('/installations/{installation}/edit', [InstallationController::class, 'edit'])->name('installations.edit');
    Route::put('/installations/{installation}', [InstallationController::class, 'update'])->name('installations.update');
    Route::delete('/installations/{installation}', [InstallationController::class, 'destroy'])->name('installations.destroy');
    Route::post('/installations/import', [InstallationController::class, 'import'])->name('installations.import');
    Route::get('/installations/export', [InstallationController::class, 'export'])->name('installations.export');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePassword'])->name('profile.change-password');
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.update-password');

    // Travel History - Redirect to tracking page
    Route::get('/history', function() {
        return redirect()->route('tracking.index');
    })->name('history.index');
    Route::get('/history/route', function() {
        return redirect()->route('tracking.index');
    })->name('history.route');

    // Reports - Redirect to tracking page
    Route::get('/reports/stops', function() {
        return redirect()->route('tracking.index');
    })->name('reports.stops');
    Route::get('/reports/visits', function() {
        return redirect()->route('tracking.index');
    })->name('reports.visits');
    Route::get('/reports/analytics', function() {
        return redirect()->route('tracking.index');
    })->name('reports.analytics');
});
