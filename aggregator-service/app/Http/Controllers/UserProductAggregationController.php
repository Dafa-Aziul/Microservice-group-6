<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserProductAggregationController extends Controller
{
    use ApiResponse;

    public function getUserWithProducts(Request $request, string $userId)
    {
        $authUser      = $request->auth_user;
        $correlationId = $request->attributes->get("correlation_id");
        $token         = $request->bearerToken();

        // ============================================================
        // 0. CEK ROLE — HANYA ADMIN YANG BOLEH MENGAKSES ENDPOINT INI
        // ============================================================

        if (($authUser["role"] ?? null) !== "admin") {

            Log::warning("ACCESS DENIED: Non-admin mencoba akses user-with-products", [
                "auth_user_id"   => $authUser["id"] ?? null,
                "auth_role"      => $authUser["role"] ?? null,
                "target_user_id" => $userId,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Akses ditolak. Hanya admin yang dapat melihat data user beserta produk.",
                403,
                "FORBIDDEN"
            );
        }

        Log::info("AGGREGATE REQUEST: User With Products", [
            "target_user_id" => $userId,
            "auth_user_id"   => $authUser["id"],
            "correlation_id" => $correlationId
        ]);

        // ============================================================
        // 1. USER SERVICE → GET USER BY ID
        // ============================================================

        try {
            $userResponse = Http::withHeaders([
                "Authorization"    => "Bearer $token",
                "X-Correlation-ID" => $correlationId
            ])
                ->timeout(10)
                ->get(env("USER_SERVICE_URL") . "/users/$userId");
        } catch (\Throwable $e) {

            Log::error("USER SERVICE ERROR", [
                "error"          => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "User Service tidak dapat dihubungi.",
                503,
                "USER_SERVICE_UNAVAILABLE"
            );
        }

        if ($userResponse->failed()) {
            return $this->errorResponse(
                $userResponse->json("error.message") ?? "User tidak ditemukan",
                $userResponse->status(),
                $userResponse->json("error.code") ?? "USER_ERROR"
            );
        }

        $user = $userResponse->json("data");


        // ============================================================
        // 2. PRODUCT SERVICE → GET ALL PRODUCTS
        // ============================================================

        try {
            $productResponse = Http::withHeaders([
                "Authorization"    => "Bearer $token",
                "X-Correlation-ID" => $correlationId
            ])
                ->timeout(10)
                ->get(env("PRODUCT_SERVICE_URL") . "/products");
        } catch (\Throwable $e) {

            Log::error("PRODUCT SERVICE ERROR", [
                "error"          => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Product Service tidak dapat dihubungi.",
                503,
                "PRODUCT_SERVICE_UNAVAILABLE"
            );
        }

        if ($productResponse->failed()) {
            return $this->errorResponse(
                "Gagal mengambil data produk.",
                $productResponse->status(),
                "PRODUCT_ERROR"
            );
        }

        $allProducts = $productResponse->json("data");


        // ============================================================
        // 3. FILTER PRODUK BERDASARKAN userId
        // ============================================================

        $userProducts = array_values(
            array_filter($allProducts, fn($p) => isset($p["user_id"]) && $p["user_id"] === $userId)
        );


        // ============================================================
        // 4. GABUNGKAN DATA
        // ============================================================

        $result = [
            "user"     => $user,
            "products" => $userProducts
        ];

        Log::info("AGGREGATE SUCCESS: User With Products", [
            "target_user_id" => $userId,
            "products_count" => count($userProducts),
            "correlation_id" => $correlationId
        ]);

        return $this->successResponse($result, "Data user + produk berhasil digabungkan");
    }


    public function myProducts(Request $request)
    {
        $authUser      = $request->auth_user;
        $userId        = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");
        $token         = $request->bearerToken();

        Log::info("AGGREGATE REQUEST: My Products", [
            "auth_user_id"   => $userId,
            "correlation_id" => $correlationId
        ]);

        // ============================================================
        // 1. PRODUCT SERVICE → GET ALL PRODUCTS
        // ============================================================

        try {
            $productResponse = Http::withHeaders([
                "Authorization"    => "Bearer $token",
                "X-Correlation-ID" => $correlationId
            ])
                ->timeout(10)
                ->get(env("PRODUCT_SERVICE_URL") . "/products");
        } catch (\Throwable $e) {

            Log::error("PRODUCT SERVICE ERROR", [
                "error"          => $e->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Product Service tidak dapat dihubungi.",
                503,
                "PRODUCT_SERVICE_UNAVAILABLE"
            );
        }

        if ($productResponse->failed()) {
            return $this->errorResponse(
                "Gagal mengambil data produk.",
                $productResponse->status(),
                "PRODUCT_ERROR"
            );
        }

        $allProducts = $productResponse->json("data");

        // ============================================================
        // 2. FILTER PRODUK MILIK USER YANG LOGIN
        // ============================================================

        $myProducts = array_values(
            array_filter(
                $allProducts,
                fn($p) =>
                isset($p["user_id"]) && $p["user_id"] === $userId
            )
        );

        Log::info("AGGREGATE SUCCESS: My Products", [
            "auth_user_id"     => $userId,
            "products_count"   => count($myProducts),
            "correlation_id"   => $correlationId
        ]);

        return $this->successResponse(
            $myProducts,
            "Produk milik user berhasil diambil"
        );
    }
}
