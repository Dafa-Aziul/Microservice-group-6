<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\CorrelationIdMiddleware::class
        ]);
        // [TAMBAHAN BARU]
        // Jika belum login, jangan redirect ke halaman login, tapi return null
        // agar Error Handler di bawah yang menangani.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle Error Belum Login (Yang sudah kita buat sebelumnya)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 401,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Anda harus login terlebih dahulu untuk mengakses resource ini.',
                        'details' => []
                    ],
                    'data' => null
                ], 401);
            }
        });
    })->create();
