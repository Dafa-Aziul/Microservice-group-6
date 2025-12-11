<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // MASUKKAN CORRELATION ID DI SETIAP REQUEST API
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\CorrelationIdMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // HANDLE AUTHENTICATION ERROR KHUSUS API
        $exceptions->render(function (AuthenticationException $e, Request $request) {

            if ($request->is('api/*')) {

                // Ambil correlation_id dari middleware
                $cid = $request->header('X-Correlation-ID') ?: (string) Str::uuid();


                // Ambil header Authorization (jika ada)
                $authHeader = $request->header("Authorization");

                // Log detail error
                Log::warning("AUTHENTICATION FAILED", [
                    "message"           => $e->getMessage(),
                    "authorization"     => $authHeader,
                    "path"              => $request->path(),
                    "guards"            => $e->guards(),
                    "correlation_id"    => $cid,
                    "ip_address"        => $request->ip(),
                    "user_agent"        => $request->userAgent(),
                ]);

                // Response JSON lebih jelas
                return response()->json([
                    'status' => 401,
                    'error' => [
                        'code'      => 'UNAUTHORIZED',
                        'message'   => 'Autentikasi gagal. Token tidak valid, tidak diberikan, atau sudah kedaluwarsa.',
                        'details'   => [
                            'reason' => $e->getMessage(),
                        ],
                    ],
                    'data' => null,
                    'metadata' => [
                        'timestamp'      => now()->toIso8601String(),
                        'correlation_id' => $cid,
                    ]
                ], 401);
            }
        });
    })
    ->create();
