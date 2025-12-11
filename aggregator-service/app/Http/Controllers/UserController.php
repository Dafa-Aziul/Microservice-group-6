<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AggregatorBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserController extends AggregatorBaseController
{
    public function index(Request $request)
    {
        return $this->sendToService($request, 'get', env('USER_SERVICE_URL') . '/users');
    }

    public function show(Request $request, $id)
    {
        return $this->sendToService($request, 'get', env('USER_SERVICE_URL') . "/users/$id");
    }

    public function update(Request $request, $id)
    {
        return $this->sendToService($request, 'put', env('USER_SERVICE_URL') . "/users/$id", $request->all());
    }

    public function destroy(Request $request, $id)
    {
        $authUser      = $request->auth_user;
        $correlationId = $request->attributes->get("correlation_id");

        // --------------------------------------------------------------------
        // 1. Validasi role dan larangan hapus diri sendiri
        // --------------------------------------------------------------------
        if ($authUser["role"] !== "admin") {
            return $this->errorResponse(
                "Hanya admin yang bisa menghapus user.",
                403,
                "FORBIDDEN"
            );
        }

        if ($authUser["id"] === $id) {
            return $this->errorResponse(
                "Anda tidak boleh menghapus akun anda sendiri.",
                400,
                "INVALID_ACTION"
            );
        }

        // --------------------------------------------------------------------
        // 2. Cek apakah user punya produk — panggil PRODUCT SERVICE
        // --------------------------------------------------------------------
        try {
            $productResponse = Http::withHeaders([
                "Authorization"     => "Bearer " . $request->bearerToken(),
                "X-Correlation-ID"  => $correlationId
            ])->get(env("PRODUCT_SERVICE_URL") . "/products?owner_id=$id");
        } catch (\Throwable $e) {
            return $this->errorResponse(
                "Product Service tidak dapat dihubungi.",
                503,
                "PRODUCT_SERVICE_UNAVAILABLE",
                $e->getMessage()
            );
        }

        $productList = $productResponse->json("data") ?? [];

        if (count($productList) > 0) {
            return $this->errorResponse(
                "User tidak dapat dihapus karena masih memiliki produk.",
                400,
                "USER_HAS_PRODUCTS",
                [
                    "product_count" => count($productList)
                ]
            );
        }

        // --------------------------------------------------------------------
        // 3. Tidak punya produk → lanjutkan forward request ke User Service
        // --------------------------------------------------------------------
        return $this->sendToService(
            $request,
            'delete',
            env('USER_SERVICE_URL') . "/users/$id"
        );
    }
}
