<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // keamanan: deteksi serangan + header di SEMUA request
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\DetectIntrusions::class);

        // cookie visitor id untuk pengunjung anonim
        $middleware->append(\App\Http\Middleware\EnsureVisitorId::class);

        // alias untuk grup admin
        $middleware->alias([
            'log.admin' => \App\Http\Middleware\LogAdminActivity::class,
            'noindex.admin' => \App\Http\Middleware\NoIndexAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON untuk semua error API
        $exceptions->shouldRenderJsonWhen(fn (\Illuminate\Http\Request $request) => $request->is('api/*'));
    })->create();
