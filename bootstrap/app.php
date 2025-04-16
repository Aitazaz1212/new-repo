<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckApiKey; // Import your middleware
use App\Http\Middleware\CheckDriverAppAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
         // Register middleware for specific groups or global
         $middleware->group('key-check-middleware', [
            CheckApiKey::class, // Add your middleware to the API group
        ]);

        $middleware->group('driver-app-auth', [
            CheckDriverAppAuth::class, // Add your middleware to the API group
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
