<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;

class AuthTokenMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->attributes->get('correlation_id');

        Log::info("AUTH CHECK: Memulai validasi token", [
            "path"            => $request->path(),
            "method"          => $request->method(),
            "correlation_id"  => $correlationId
        ]);

        // Ambil header Authorization
        $rawToken = $request->header("Authorization");

        // 1. Token tidak diberikan
        if (!$rawToken) {
            Log::warning("AUTH FAILED: Token tidak ditemukan", [
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Unauthorized: Token tidak ditemukan.",
                401,
                "TOKEN_NOT_FOUND"
            );
        }

        // 2. Format salah
        if (!str_starts_with($rawToken, "Bearer ")) {
            Log::warning("AUTH FAILED: Format Authorization tidak valid", [
                "header"         => $rawToken,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Format token tidak valid.",
                401,
                "INVALID_TOKEN_FORMAT"
            );
        }

        // Ambil token asli
        $token = substr($rawToken, 7);

        Log::info("AUTH CHECK: Mengirim token ke User Service untuk validasi", [
            "correlation_id" => $correlationId
        ]);

        // 3. Panggil User Service
        try {
            $response = Http::withHeaders([
                "Authorization"     => "Bearer $token",
                "X-Correlation-ID"  => $correlationId
            ])->timeout(5)->get(env("USER_SERVICE_URL") . "/auth/validate-token");

        } catch (\Throwable $e) {

            Log::error("AUTH ERROR: User Service tidak dapat dihubungi", [
                "error"          => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "User Service tidak dapat dihubungi.",
                503,
                "USER_SERVICE_UNAVAILABLE"
            );
        }

        // 4. Token invalid dari User Service
        if ($response->failed()) {
            Log::warning("AUTH FAILED: Token ditolak oleh User Service", [
                "response"        => $response->json(),
                "correlation_id"  => $correlationId
            ]);

            return $this->errorResponse(
                "Unauthorized: Token invalid.",
                401,
                "TOKEN_INVALID"
            );
        }

        // 5. Token valid → Ambil data user
        $user = $response->json("data");

        Log::info("AUTH SUCCESS: Token valid", [
            "user_id"        => $user["id"] ?? null,
            "role"           => $user["role"] ?? null,
            "correlation_id" => $correlationId
        ]);

        // Masukkan data user ke request
        $request->merge([
            "auth_user" => $user
        ]);

        return $next($request);
    }
}
