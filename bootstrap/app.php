<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\LogAllRequests::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sync:zyda-orders')
            ->everyMinute()
            ->withoutOverlapping(3) // Release lock after 3 minutes if still running
            ->appendOutputTo(storage_path('logs/schedule.log'))
            ->emailOutputOnFailure(env('ADMIN_EMAIL', null));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
