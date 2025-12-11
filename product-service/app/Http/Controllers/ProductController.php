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
    public function index()
    {
        Log::info('Request: Ambil semua produk');

        $products = Product::with('category')->get();

        Log::info('Response: Daftar produk berhasil diambil', [
            'count' => $products->count()
        ]);

        return $this->successResponse(
            $products,
            'Daftar produk berhasil diambil'
        );
    }


    // 2. TAMBAH PRODUK BARU
    public function store(Request $request)
    {
        // Ambil payload dengan fallback JSON
        $payload = $request->all();
        if (empty($payload)) {
            $payload = $request->json()->all();
        }

        Log::info('Request: Tambah produk baru', [
            'payload' => $payload
        ]);

        // VALIDASI INPUT
        $validator = Validator::make($payload, [
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|uuid',
            'user_id'     => 'required|uuid'
        ]);

        // Jika gagal validasi
        if ($validator->fails()) {
            Log::warning('Validasi gagal saat membuat produk', [
                'errors' => $validator->errors()
            ]);

            return $this->errorResponse(
                'Input tidak valid.',
                422,
                'VALIDATION_ERROR',
                $validator->errors()
            );
        }

        try {
            $product = Product::create($validator->validated());

            Log::info('Produk berhasil dibuat', [
                'id' => $product->id,
                'name' => $product->name
            ]);

            return $this->successResponse(
                $product,
                'Produk berhasil dibuat!',
                201
            );
        } catch (\Throwable $th) {
            Log::error('Error: Gagal membuat produk', [
                'error' => $th->getMessage()
            ]);

            return $this->errorResponse(
                'Gagal membuat produk.',
                500,
                'SERVER_ERROR',
                $th->getMessage()
            );
        }
    }



    // 3. DETAIL SATU PRODUK
    public function show($id)
    {
        Log::info('Request: Ambil detail produk', [
            'id' => $id
        ]);

        $product = Product::with('category')->find($id);

        if (!$product) {
            Log::warning('Produk tidak ditemukan', [
                'id' => $id
            ]);

            return $this->errorResponse('Produk tidak ditemukan.', 404, 'NOT_FOUND');
        }

        Log::info('Detail produk berhasil diambil', [
            'id' => $product->id
        ]);

        return $this->successResponse(
            $product,
            'Detail produk berhasil diambil'
        );
    }


    // 4. UPDATE PRODUK
    public function update(Request $request, $id)
    {
        Log::info('Request: Update produk', [
            'id'      => $id,
            'payload' => $request->all()
        ]);

        $product = Product::find($id);

        if (!$product) {
            Log::warning('Produk tidak ditemukan saat update', [
                'id' => $id
            ]);

            return $this->errorResponse('Produk tidak ditemukan.', 404, 'NOT_FOUND');
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string',
            'price'       => 'sometimes|numeric',
            'category_id' => 'sometimes|uuid',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal saat update produk', [
                'id'     => $id,
                'errors' => $validator->errors()
            ]);

            return $this->errorResponse(
                'Input tidak valid.',
                422,
                'VALIDATION_ERROR',
                $validator->errors()
            );
        }

        try {
            $product->update($validator->validated());

            Log::info('Produk berhasil diupdate', [
                'id' => $product->id
            ]);

            return $this->successResponse(
                $product,
                'Produk berhasil diupdate'
            );
        } catch (\Throwable $th) {
            Log::error('Error: Gagal mengupdate produk', [
                'id'    => $id,
                'error' => $th->getMessage()
            ]);

            return $this->errorResponse(
                'Gagal mengupdate produk.',
                500,
                'SERVER_ERROR',
                $th->getMessage()
            );
        }
    }


    // 5. HAPUS PRODUK
    public function destroy($id)
    {
        Log::info('Request: Hapus produk', [
            'id' => $id
        ]);

        $product = Product::find($id);

        if (!$product) {
            Log::warning('Produk tidak ditemukan saat delete', [
                'id' => $id
            ]);

            return $this->errorResponse('Produk tidak ditemukan.', 404, 'NOT_FOUND');
        }

        try {
            $product->delete();

            Log::info('Produk berhasil dihapus', [
                'id' => $id
            ]);

            return $this->successResponse(
                null,
                'Produk berhasil dihapus'
            );
        } catch (\Throwable $th) {
            Log::error('Error: Gagal menghapus produk', [
                'id'    => $id,
                'error' => $th->getMessage()
            ]);

            return $this->errorResponse(
                'Gagal menghapus produk.',
                500,
                'SERVER_ERROR',
                $th->getMessage()
            );
        }
    }
}
