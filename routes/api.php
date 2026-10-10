<?php

use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\CenterImageController as AdminCenterImageController;
use App\Http\Controllers\Api\V1\Admin\OpeningHourController as AdminOpeningHourController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\ScheduleExceptionController as AdminScheduleExceptionController;
use App\Http\Controllers\Api\V1\Admin\ServiceCenterController as AdminServiceCenterController;
use App\Http\Controllers\Api\V1\Admin\ServiceDurationController as AdminServiceDurationController;
use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CarBrandController;
use App\Http\Controllers\Api\V1\GovernorateCityController;
use App\Http\Controllers\Api\V1\GovernorateController;
use App\Http\Controllers\Api\V1\Owner\BookingController as OwnerBookingController;
use App\Http\Controllers\Api\V1\Owner\CenterImageController as OwnerCenterImageController;
use App\Http\Controllers\Api\V1\Owner\OpeningHourController as OwnerOpeningHourController;
use App\Http\Controllers\Api\V1\Owner\ScheduleExceptionController as OwnerScheduleExceptionController;
use App\Http\Controllers\Api\V1\Owner\ServiceCenterController as OwnerServiceCenterController;
use App\Http\Controllers\Api\V1\Owner\ServiceDurationController as OwnerServiceDurationController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ScheduleController;
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

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('service-centers/{serviceCenter}/reviews', [ReviewController::class, 'store'])
            ->name('reviews.store');
        Route::patch('reviews/{review}', [ReviewController::class, 'update'])
            ->whereNumber('review')
            ->name('reviews.update');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
            ->whereNumber('review')
            ->name('reviews.destroy');
    });

    Route::middleware(['auth:sanctum', 'role:customer'])->group(function (): void {
        Route::get('bookings', [BookingController::class, 'index'])
            ->name('bookings.index');
        Route::post('service-centers/{serviceCenter}/bookings', [BookingController::class, 'store'])
            ->name('bookings.store');
        Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])
            ->whereNumber('booking')
            ->name('bookings.destroy');
    });

    Route::middleware(['auth:sanctum', 'role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('bookings', [AdminBookingController::class, 'index'])
                ->name('bookings.index');
            Route::patch('bookings/{booking}', [AdminBookingController::class, 'update'])
                ->whereNumber('booking')
                ->name('bookings.update');
            Route::get('service-centers', [AdminServiceCenterController::class, 'index'])
                ->name('service-centers.index');
            Route::get('reviews', [AdminReviewController::class, 'index'])
                ->name('reviews.index');
            Route::patch('reviews/{review}/status', [AdminReviewController::class, 'updateStatus'])
                ->whereNumber('review')
                ->name('reviews.status.update');
            Route::get('service-centers/{serviceCenter}', [AdminServiceCenterController::class, 'show'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.show');
            Route::post('service-centers', [AdminServiceCenterController::class, 'store'])
                ->name('service-centers.store');
            Route::patch('service-centers/{serviceCenter}', [AdminServiceCenterController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.update');
            Route::get('service-centers/{serviceCenter}/schedule-exceptions', [AdminScheduleExceptionController::class, 'index'])
                ->whereNumber('serviceCenter')->name('service-centers.schedule-exceptions.index');
            Route::put('service-centers/{serviceCenter}/schedule-exceptions', [AdminScheduleExceptionController::class, 'store'])
                ->whereNumber('serviceCenter')->name('service-centers.schedule-exceptions.store');
            Route::delete('service-centers/{serviceCenter}/schedule-exceptions/{scheduleException}', [AdminScheduleExceptionController::class, 'destroy'])
                ->whereNumber(['serviceCenter', 'scheduleException'])->name('service-centers.schedule-exceptions.destroy');
            Route::patch('service-centers/{serviceCenter}/services/{service}/duration', [AdminServiceDurationController::class, 'update'])
                ->whereNumber(['serviceCenter', 'service'])->name('service-centers.services.duration.update');
            Route::patch('service-centers/{serviceCenter}/opening-hours', [AdminOpeningHourController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.opening-hours.update');
            Route::post('service-centers/{serviceCenter}/images', [AdminCenterImageController::class, 'store'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.store');
            Route::patch('service-centers/{serviceCenter}/images/order', [AdminCenterImageController::class, 'reorder'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.reorder');
            Route::patch('service-centers/{serviceCenter}/images/{centerImage}', [AdminCenterImageController::class, 'update'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.update');
            Route::put('service-centers/{serviceCenter}/images/{centerImage}/cover', [AdminCenterImageController::class, 'cover'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.cover.update');
            Route::delete('service-centers/{serviceCenter}/images/{centerImage}', [AdminCenterImageController::class, 'destroy'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.destroy');
            Route::patch('service-centers/{serviceCenter}/status', [AdminServiceCenterController::class, 'updateStatus'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.status.update');
            Route::patch('service-centers/{serviceCenter}/verification', [AdminServiceCenterController::class, 'updateVerification'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.verification.update');
        });

    Route::middleware(['auth:sanctum', 'role:center_owner'])
        ->prefix('owner')
        ->name('owner.')
        ->group(function (): void {
            Route::get('bookings', [OwnerBookingController::class, 'index'])
                ->name('bookings.index');
            Route::patch('bookings/{booking}', [OwnerBookingController::class, 'update'])
                ->whereNumber('booking')
                ->name('bookings.update');
            Route::get('service-centers', [OwnerServiceCenterController::class, 'index'])
                ->name('service-centers.index');
            Route::get('service-centers/{serviceCenter}', [OwnerServiceCenterController::class, 'show'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.show');
            Route::patch('service-centers/{serviceCenter}', [OwnerServiceCenterController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.update');
            Route::get('service-centers/{serviceCenter}/schedule-exceptions', [OwnerScheduleExceptionController::class, 'index'])
                ->whereNumber('serviceCenter')->name('service-centers.schedule-exceptions.index');
            Route::put('service-centers/{serviceCenter}/schedule-exceptions', [OwnerScheduleExceptionController::class, 'store'])
                ->whereNumber('serviceCenter')->name('service-centers.schedule-exceptions.store');
            Route::delete('service-centers/{serviceCenter}/schedule-exceptions/{scheduleException}', [OwnerScheduleExceptionController::class, 'destroy'])
                ->whereNumber(['serviceCenter', 'scheduleException'])->name('service-centers.schedule-exceptions.destroy');
            Route::patch('service-centers/{serviceCenter}/services/{service}/duration', [OwnerServiceDurationController::class, 'update'])
                ->whereNumber(['serviceCenter', 'service'])->name('service-centers.services.duration.update');
            Route::patch('service-centers/{serviceCenter}/opening-hours', [OwnerOpeningHourController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.opening-hours.update');
            Route::post('service-centers/{serviceCenter}/images', [OwnerCenterImageController::class, 'store'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.store');
            Route::patch('service-centers/{serviceCenter}/images/order', [OwnerCenterImageController::class, 'reorder'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.reorder');
            Route::patch('service-centers/{serviceCenter}/images/{centerImage}', [OwnerCenterImageController::class, 'update'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.update');
            Route::put('service-centers/{serviceCenter}/images/{centerImage}/cover', [OwnerCenterImageController::class, 'cover'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.cover.update');
            Route::delete('service-centers/{serviceCenter}/images/{centerImage}', [OwnerCenterImageController::class, 'destroy'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.destroy');
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
    Route::get('service-centers/{serviceCenter}/schedule', [ScheduleController::class, 'show'])
        ->name('service-centers.schedule.show');
    Route::get('service-centers/{serviceCenter}', [ServiceCenterController::class, 'show'])
        ->name('service-centers.show');
});
