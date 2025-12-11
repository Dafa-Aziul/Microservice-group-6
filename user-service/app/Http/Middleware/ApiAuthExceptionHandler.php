<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiAuthExceptionHandler
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {

            $cid = $request->header('X-Correlation-ID') ?: (string) Str::uuid();
            $authHeader = $request->header('Authorization');

            Log::warning("AUTH FAILED", [
                "message"        => $e->getMessage(),
                "authorization"  => $authHeader,
                "path"           => $request->path(),
                "guards"         => $e->guards(),
                "correlation_id" => $cid,
            ]);

            return response()->json([
                'status' => 401,
                'error' => [
                    'code'    => 'UNAUTHORIZED',
                    'message' => 'Token tidak valid atau tidak diberikan.',
                    'details' => ['reason' => $e->getMessage()],
                ],
                'data' => null,
                'metadata' => [
                    'timestamp' => now()->toIso8601String(),
                    'correlation_id' => $cid
                ]
            ], 401);
        }
    }
}
