<?php

use App\Http\Controllers\Api\V1\Admin\ServiceCenterController as AdminServiceCenterController;
use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\CarBrandController;
use App\Http\Controllers\Api\V1\GovernorateCityController;
use App\Http\Controllers\Api\V1\GovernorateController;
use App\Http\Controllers\Api\V1\Owner\ServiceCenterController as OwnerServiceCenterController;
use App\Http\Controllers\Api\V1\ServiceCenterController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', RegisteredUserController::class)
            ->middleware('throttle:6,1')
            ->name('register');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', CurrentUserController::class)->name('me');
            Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
        });
    });

    Route::middleware(['auth:sanctum', 'role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::post('service-centers', [AdminServiceCenterController::class, 'store'])
                ->name('service-centers.store');
        });

    Route::middleware(['auth:sanctum', 'role:center_owner'])
        ->prefix('owner')
        ->name('owner.')
        ->group(function (): void {
            Route::get('service-centers', [OwnerServiceCenterController::class, 'index'])
                ->name('service-centers.index');
            Route::get('service-centers/{serviceCenter}', [OwnerServiceCenterController::class, 'show'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.show');
            Route::patch('service-centers/{serviceCenter}', [OwnerServiceCenterController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.update');
        });

    Route::get('governorates', [GovernorateController::class, 'index'])
        ->name('governorates.index');
    Route::get('governorates/{governorate}/cities', [GovernorateCityController::class, 'index'])
        ->name('governorates.cities.index');
    Route::get('services', [ServiceController::class, 'index'])
        ->name('services.index');
    Route::get('car-brands', [CarBrandController::class, 'index'])
        ->name('car-brands.index');
    Route::get('service-centers', [ServiceCenterController::class, 'index'])
        ->name('service-centers.index');
    Route::get('service-centers/{serviceCenter}', [ServiceCenterController::class, 'show'])
        ->name('service-centers.show');
});
