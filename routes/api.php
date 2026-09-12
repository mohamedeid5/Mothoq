<?php

use App\Http\Controllers\Api\V1\CarBrandController;
use App\Http\Controllers\Api\V1\GovernorateCityController;
use App\Http\Controllers\Api\V1\GovernorateController;
use App\Http\Controllers\Api\V1\ServiceCenterController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
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
