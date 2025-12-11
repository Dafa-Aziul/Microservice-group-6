<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AggregatorBaseController extends Controller
{
    use ApiResponse;

    protected function sendToService(Request $request, string $method, string $url, array $data = [])
    {
        $authUser = $request->auth_user;
        $correlationId = $request->attributes->get("correlation_id");

        Log::info("AGGREGATOR REQUEST → SERVICE", [
            "method"         => $method,
            "url"            => $url,
            "payload"        => $data,
            "user_id"        => $authUser["id"] ?? null,
            "correlation_id" => $correlationId
        ]);

        try {

            $response = Http::withHeaders([
                "Authorization"     => "Bearer " . $request->bearerToken(),
                "X-Correlation-ID"  => $correlationId
            ])
            ->timeout(10)
            ->$method($url, $data);

        } catch (\Throwable $e) {

            Log::error("AGGREGATOR ERROR CALLING SERVICE", [
                "url"            => $url,
                "error"          => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Service tidak dapat dihubungi",
                503,
                "SERVICE_UNAVAILABLE",
                $e->getMessage()
            );
        }

        if ($response->failed()) {

            Log::warning("AGGREGATOR SERVICE FAILED RESPONSE", [
                "url"            => $url,
                "service_error"  => $response->json(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                $response->json('error.message') ?? 'Request gagal',
                $response->status(),
                $response->json('error.code') ?? 'SERVICE_ERROR',
                $response->json('error.details') ?? []
            );
        }

        Log::info("AGGREGATOR RESPONSE SUCCESS", [
            "url"            => $url,
            "correlation_id" => $correlationId
        ]);

        return $this->successResponse(
            $response->json()['data'] ?? null,
            $response->json()['message'] ?? "Success",
            $response->status()
        );
    }
}
