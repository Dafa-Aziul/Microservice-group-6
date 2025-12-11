<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ApiResponse;

    // 1. LIST SEMUA PRODUK
    public function index(Request $request)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info('Request: Ambil semua produk', [
            "user_id" => $userId,
            "correlation_id" => $correlationId
        ]);

        $products = Product::with('category')->get();

        Log::info('Response: Daftar produk berhasil diambil', [
            'count' => $products->count(),
            "correlation_id" => $correlationId
        ]);

        return $this->successResponse($products, 'Daftar produk berhasil diambil');
    }


    // 2. TAMBAH PRODUK BARU
    public function store(Request $request)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info("Request: Tambah produk baru", [
            "payload" => $request->all(),
            "user_id" => $userId,
            "correlation_id" => $correlationId
        ]);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            Log::warning("Validasi gagal saat membuat produk", [
                "errors" => $validator->errors(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse('Input tidak valid.', 422, 'VALIDATION_ERROR', $validator->errors());
        }

        try {
            $data = $validator->validated();
            $data["user_id"] = $userId;

            $product = Product::create($data);

            Log::info("Produk berhasil dibuat", [
                "product_id" => $product->id,
                "user_id" => $userId,
                "correlation_id" => $correlationId
            ]);

            return $this->successResponse($product, 'Produk berhasil dibuat!', 201);
        } catch (\Throwable $th) {
            Log::error("Error: Gagal membuat produk", [
                "error" => $th->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse('Gagal membuat produk.', 500, 'SERVER_ERROR', $th->getMessage());
        }
    }


    // 3. DETAIL PRODUK
    public function show(Request $request, $id)
    {
        $authUser = $request->auth_user;
        $correlationId = $request->attributes->get("correlation_id");

        Log::info("Request: Ambil detail produk", [
            "product_id" => $id,
            "user_id" => $authUser["id"],
            "correlation_id" => $correlationId
        ]);

        $product = Product::with('category')->find($id);

        if (!$product) {
            Log::warning("Produk tidak ditemukan", [
                "product_id" => $id,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse("Produk tidak ditemukan.", 404, "NOT_FOUND");
        }

        Log::info("Detail produk berhasil diambil", [
            "product_id" => $id,
            "correlation_id" => $correlationId
        ]);

        return $this->successResponse($product, "Detail produk berhasil diambil");
    }


    // 4. UPDATE PRODUK
    public function update(Request $request, $id)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $role = $authUser["role"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info("Request: Update produk", [
            "product_id" => $id,
            "payload" => $request->all(),
            "user_id" => $userId,
            "role" => $role,
            "correlation_id" => $correlationId
        ]);

        $product = Product::find($id);

        if (!$product) {
            return $this->errorResponse("Produk tidak ditemukan.", 404, "NOT_FOUND");
        }

        // =====================================================
        // 1. USER BIASA TIDAK BOLEH UPDATE PRODUK ORANG LAIN
        // =====================================================
        if ($role !== "admin" && $product->user_id !== $userId) {

            Log::warning("Akses ditolak: User mencoba update produk milik orang lain", [
                "product_id" => $id,
                "product_owner" => $product->user_id,
                "request_user" => $userId,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Anda tidak memiliki izin untuk mengupdate produk ini.",
                403,
                "FORBIDDEN"
            );
        }

        // =====================================================
        // 2. VALIDASI INPUT (user_id TIDAK BOLEH DIPERBOLEHKAN)
        // =====================================================
        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string',
            'price'       => 'sometimes|numeric',
            'category_id' => 'sometimes|uuid',
            // user_id tidak boleh divalidasi atau diterima
        ]);

        if ($validator->fails()) {
            Log::warning("Validasi update produk gagal", [
                "errors" => $validator->errors(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse('Input tidak valid.', 422, 'VALIDATION_ERROR', $validator->errors());
        }

        try {
            $data = $validator->validated();

            // =======================================================
            // 3. JIKA ADMIN UPDATE → user_id TIDAK BOLEH DIUBAH
            // =======================================================
            if ($role === "admin") {
                unset($data["user_id"]); // pastikan tidak bisa diubah
            }

            // =======================================================
            // 4. USER BIASA JUGA TIDAK BOLEH MENGUBAH user_id
            // =======================================================
            unset($data["user_id"]);

            $product->update($data);

            Log::info("Produk berhasil diupdate", [
                "product_id" => $id,
                "updated_by" => $userId,
                "role" => $role,
                "correlation_id" => $correlationId
            ]);

            return $this->successResponse($product, "Produk berhasil diupdate");
        } catch (\Throwable $th) {

            Log::error("Gagal update produk", [
                "product_id"   => $id,
                "error"        => $th->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse("Gagal mengupdate produk.", 500, "SERVER_ERROR", $th->getMessage());
        }
    }


    // 5. HAPUS PRODUK
    public function destroy(Request $request, $id)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $role = $authUser["role"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info("Request: Hapus produk", [
            "product_id"     => $id,
            "user_id"        => $userId,
            "role"           => $role,
            "correlation_id" => $correlationId
        ]);

        $product = Product::find($id);

        if (!$product) {
            Log::warning("Produk tidak ditemukan saat delete", [
                "product_id"     => $id,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse("Produk tidak ditemukan.", 404, "NOT_FOUND");
        }

        // =====================================================
        // 1. USER BIASA TIDAK BOLEH HAPUS PRODUK ORANG LAIN
        // =====================================================
        if ($role !== "admin" && $product->user_id !== $userId) {

            Log::warning("Akses ditolak: User mencoba menghapus produk milik orang lain", [
                "product_id"     => $id,
                "product_owner"  => $product->user_id,
                "request_user"   => $userId,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Anda tidak memiliki izin untuk menghapus produk ini.",
                403,
                "FORBIDDEN"
            );
        }

        // =====================================================
        // 2. HAPUS PRODUK
        // =====================================================
        try {
            $product->delete();

            Log::info("Produk berhasil dihapus", [
                "product_id"     => $id,
                "deleted_by"     => $userId,
                "role"           => $role,
                "correlation_id" => $correlationId
            ]);

            return $this->successResponse(null, "Produk berhasil dihapus");
        } catch (\Throwable $th) {

            Log::error("Error: Gagal menghapus produk", [
                "product_id"     => $id,
                "error"          => $th->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                "Gagal menghapus produk.",
                500,
                "SERVER_ERROR",
                $th->getMessage()
            );
        }
    }
}
