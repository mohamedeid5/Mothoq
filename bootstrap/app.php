<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Database\ConcurrencyErrorDetector;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->redirectGuestsTo(fn (Request $request): string => route('login'));
        $middleware->redirectUsersTo(fn (Request $request): string => route(
            $request->user()->role->dashboardRouteName(),
        ));

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request): ?Response {
            if (! $request->routeIs('*.schedule-exceptions.*', '*.opening-hours.update', '*.services.duration.update', '*.bookings.store', 'bookings.store', '*.service-centers.update')) {
                return null;
            }
            if (! $exception instanceof UniqueConstraintViolationException && ! (new ConcurrencyErrorDetector)->causedByConcurrencyError($exception)) {
                return null;
            }

            $message = 'تعذر حفظ التغيير بسبب تحديث متزامن. أعد تحميل البيانات وحاول مرة أخرى.';

            return $request->is('api/*') || $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['schedule' => [$message]]], 409)
                : back()->withInput()->withErrors(['schedule' => $message]);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
