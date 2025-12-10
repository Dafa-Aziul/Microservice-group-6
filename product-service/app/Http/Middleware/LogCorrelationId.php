<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek apakah ada ID jejak dari pengirim? Kalau ga ada, bikin baru (UUID)
        $correlationId = $request->header('X-Correlation-ID') ?: (string) Str::uuid();

        // 2. Simpan ID ini ke dalam Log sistem (Syarat Poin E)
        Log::withContext(['correlation_id' => $correlationId]);

        // 3. Teruskan ID ini ke respon balik
        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}