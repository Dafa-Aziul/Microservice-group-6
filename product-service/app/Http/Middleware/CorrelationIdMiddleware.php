<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil correlation ID jika ada, jika tidak buat baru
        $correlationId = $request->header('X-Correlation-ID') ?: (string) Str::uuid();

        // 2. Simpan correlation ID ke attributes agar ApiResponse bisa membaca
        $request->attributes->set('correlation_id', $correlationId);

        // 3. Tambahkan ke Log context
        Log::withContext(['correlation_id' => $correlationId]);

        // 4. Lanjutkan proses
        $response = $next($request);

        // 5. Kirim balik correlation ID ke client
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
