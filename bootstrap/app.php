<?php

use App\Http\Middleware\EnsureCliente;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\EnsureStaffRole;
use App\Http\Middleware\EnsureStaffWeb;
use App\Http\Middleware\ValidateApiKey;
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
        $middleware->alias([
            'api.key' => ValidateApiKey::class,
            'staff' => EnsureStaff::class,
            'staff.role' => EnsureStaffRole::class,
            'staff.web' => EnsureStaffWeb::class,
            'cliente.web' => EnsureCliente::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('apikey:rotation-reminders')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
