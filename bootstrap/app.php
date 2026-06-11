<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Monthly cleanup: Delete data older than 3 calendar months
        // Runs on 1st of every month at 3 AM
        $schedule->command('tracking:cleanup --months=3')
            ->monthlyOn(1, '03:00')
            ->withoutOverlapping();

        // Weekly database optimization
        // Runs every Sunday at 4 AM
        $schedule->call(function () {
            DB::statement('OPTIMIZE TABLE location_points');
            DB::statement('OPTIMIZE TABLE duty_sessions');
            DB::statement('OPTIMIZE TABLE installation_visits');
            DB::statement('OPTIMIZE TABLE stop_records');
        })->weekly()->sundays()->at('04:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
