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
        $rawToken = $request->header("Authorization");

        Log::info("Request masuk Product Service: Validasi token dimulai", [
            "correlation_id" => $correlationId
        ]);

        // 1. TOKEN TIDAK ADA
        if (!$rawToken) {
            Log::warning("AUTH INVALID: Token tidak ditemukan", [
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Unauthorized: Token tidak ditemukan",
                401,
                "TOKEN_NOT_FOUND"
            );
        }

        // 2. FORMAT SALAH
        if (!str_starts_with($rawToken, "Bearer ")) {
            Log::warning("AUTH INVALID: Format token salah", [
                "header" => $rawToken,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Format token tidak valid",
                401,
                "INVALID_TOKEN_FORMAT"
            );
        }

        $token = str_replace("Bearer ", "", $rawToken);

        // 3. MENGHUBUNGI USER SERVICE
        Log::info("Mengirim token ke User Service untuk validasi", [
            "correlation_id" => $correlationId
        ]);

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer $token",
                "X-Correlation-ID" => $correlationId
            ])->timeout(5)->get(env("USER_SERVICE_URL") . "/auth/me");

        } catch (\Throwable $e) {

            Log::error("AUTH ERROR: User Service tidak dapat dihubungi", [
                "error" => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "User Service tidak dapat dihubungi",
                503,
                "USER_SERVICE_UNAVAILABLE"
            );
        }

        // 4. TOKEN INVALID DARI USER SERVICE
        if ($response->failed()) {
            Log::warning("AUTH INVALID: Token ditolak User Service", [
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Unauthorized: Token invalid",
                401,
                "TOKEN_INVALID"
            );
        }

        // 5. TOKEN VALID
        $user = $response->json();

        Log::info("AUTH VALID: Token berhasil divalidasi oleh User Service", [
            "user_id" => $user["id"] ?? null,
            "correlation_id" => $correlationId
        ]);

        // Masukkan data user ke request → controller bisa pakai
        $request->merge([
            "auth_user" => $user
        ]);

        return $next($request);
    }
}
