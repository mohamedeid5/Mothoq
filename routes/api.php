<?php

use App\Http\Controllers\Api\V1\ServiceCenterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('service-centers', [ServiceCenterController::class, 'index'])
        ->name('service-centers.index');
    Route::get('service-centers/{serviceCenter:slug}', [ServiceCenterController::class, 'show'])
        ->name('service-centers.show');
});
