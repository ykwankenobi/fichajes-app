<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            if ($response->getStatusCode() === 419 && $request->routeIs('kiosk.*')) {
                return redirect()->route('kiosk.index', ['session_expired' => 1]);
            }

            return $response;
        });
    })->create();
