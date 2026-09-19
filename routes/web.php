<?php

use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\ServiceCenterController as AdminServiceCenterController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Owner\ServiceCenterController as OwnerServiceCenterController;
use App\Http\Controllers\Web\ServiceCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/centers/{serviceCenter}', ServiceCenterController::class)
    ->name('service-centers.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::middleware('role:center_owner')
        ->prefix('owner')
        ->name('owner.')
        ->group(function (): void {
            Route::get('/', [OwnerServiceCenterController::class, 'index'])
                ->name('dashboard');
            Route::get('/centers/{serviceCenter}/edit', [OwnerServiceCenterController::class, 'edit'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.edit');
            Route::patch('/centers/{serviceCenter}', [OwnerServiceCenterController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.update');
        });

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('/service-centers', [AdminServiceCenterController::class, 'index'])
                ->name('service-centers.index');
            Route::get('/service-centers/{serviceCenter}', [AdminServiceCenterController::class, 'show'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.show');
            Route::get('/service-centers/{serviceCenter}/edit', [AdminServiceCenterController::class, 'edit'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.edit');
            Route::patch('/service-centers/{serviceCenter}', [AdminServiceCenterController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.update');
            Route::patch('/service-centers/{serviceCenter}/status', [AdminServiceCenterController::class, 'updateStatus'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.status.update');
            Route::patch('/service-centers/{serviceCenter}/verification', [AdminServiceCenterController::class, 'updateVerification'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.verification.update');
        });
});
