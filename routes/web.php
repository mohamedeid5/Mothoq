<?php

use App\Http\Controllers\Web\Admin\CenterImageController as AdminCenterImageController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\OpeningHourController as AdminOpeningHourController;
use App\Http\Controllers\Web\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Web\Admin\ServiceCenterController as AdminServiceCenterController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Owner\CenterImageController as OwnerCenterImageController;
use App\Http\Controllers\Web\Owner\OpeningHourController as OwnerOpeningHourController;
use App\Http\Controllers\Web\Owner\ServiceCenterController as OwnerServiceCenterController;
use App\Http\Controllers\Web\ReviewController;
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

    Route::post('/centers/{serviceCenter}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])
        ->whereNumber('review')
        ->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->whereNumber('review')
        ->name('reviews.destroy');

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
            Route::patch('/centers/{serviceCenter}/opening-hours', [OwnerOpeningHourController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.opening-hours.update');
            Route::post('/centers/{serviceCenter}/images', [OwnerCenterImageController::class, 'store'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.store');
            Route::patch('/centers/{serviceCenter}/images/order', [OwnerCenterImageController::class, 'reorder'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.reorder');
            Route::patch('/centers/{serviceCenter}/images/{centerImage}', [OwnerCenterImageController::class, 'update'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.update');
            Route::put('/centers/{serviceCenter}/images/{centerImage}/cover', [OwnerCenterImageController::class, 'cover'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.cover.update');
            Route::delete('/centers/{serviceCenter}/images/{centerImage}', [OwnerCenterImageController::class, 'destroy'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.destroy');
        });

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('/service-centers', [AdminServiceCenterController::class, 'index'])
                ->name('service-centers.index');
            Route::get('/reviews', [AdminReviewController::class, 'index'])
                ->name('reviews.index');
            Route::patch('/reviews/{review}/status', [AdminReviewController::class, 'updateStatus'])
                ->whereNumber('review')
                ->name('reviews.status.update');
            Route::get('/service-centers/{serviceCenter}', [AdminServiceCenterController::class, 'show'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.show');
            Route::get('/service-centers/{serviceCenter}/edit', [AdminServiceCenterController::class, 'edit'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.edit');
            Route::patch('/service-centers/{serviceCenter}', [AdminServiceCenterController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.update');
            Route::patch('/service-centers/{serviceCenter}/opening-hours', [AdminOpeningHourController::class, 'update'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.opening-hours.update');
            Route::post('/service-centers/{serviceCenter}/images', [AdminCenterImageController::class, 'store'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.store');
            Route::patch('/service-centers/{serviceCenter}/images/order', [AdminCenterImageController::class, 'reorder'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.images.reorder');
            Route::patch('/service-centers/{serviceCenter}/images/{centerImage}', [AdminCenterImageController::class, 'update'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.update');
            Route::put('/service-centers/{serviceCenter}/images/{centerImage}/cover', [AdminCenterImageController::class, 'cover'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.cover.update');
            Route::delete('/service-centers/{serviceCenter}/images/{centerImage}', [AdminCenterImageController::class, 'destroy'])
                ->whereNumber(['serviceCenter', 'centerImage'])
                ->name('service-centers.images.destroy');
            Route::patch('/service-centers/{serviceCenter}/status', [AdminServiceCenterController::class, 'updateStatus'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.status.update');
            Route::patch('/service-centers/{serviceCenter}/verification', [AdminServiceCenterController::class, 'updateVerification'])
                ->whereNumber('serviceCenter')
                ->name('service-centers.verification.update');
        });
});
