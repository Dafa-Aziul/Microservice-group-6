<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use ApiResponse;

    // 1. LIST SEMUA KATEGORI
    public function index(Request $request)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info('Request: Ambil semua kategori', [
            "user_id"   => $userId,
            "correlation_id" => $correlationId
        ]);

        $categories = Category::all();

        Log::info('Response: Daftar kategori berhasil diambil', [
            'count'          => $categories->count(),
            "correlation_id" => $correlationId
        ]);

        return $this->successResponse($categories, 'Daftar kategori berhasil diambil');
    }


    // 2. TAMBAH KATEGORI BARU
    public function store(Request $request)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info('Request: Tambah kategori baru', [
            'payload'        => $request->all(),
            "user_id"   => $userId,
            "correlation_id" => $correlationId
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories,name',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal saat membuat kategori', [
                'errors'        => $validator->errors(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                'Input tidak valid.',
                422,
                'VALIDATION_ERROR',
                $validator->errors()
            );
        }

        try {
            $category = Category::create($validator->validated());

            Log::info('Kategori berhasil dibuat', [
                'id'            => $category->id,
                'name'          => $category->name,
                "user_id"  => $userId,
                "correlation_id"=> $correlationId
            ]);

            return $this->successResponse(
                $category,
                'Kategori berhasil dibuat',
                201
            );

        } catch (\Throwable $th) {
            Log::error('Error: Gagal membuat kategori', [
                'error'         => $th->getMessage(),
                "correlation_id"=> $correlationId
            ]);

            return $this->errorResponse(
                'Gagal membuat kategori.',
                500,
                'SERVER_ERROR',
                $th->getMessage()
            );
        }
    }


    // 3. DETAIL SATU KATEGORI
    public function show(Request $request, $id)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info('Request: Ambil detail kategori', [
            'category_id'    => $id,
            "user_id"   => $userId,
            "correlation_id" => $correlationId
        ]);

        $category = Category::find($id);

        if (!$category) {
            Log::warning('Kategori tidak ditemukan', [
                'category_id'    => $id,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse('Kategori tidak ditemukan.', 404, 'NOT_FOUND');
        }

        Log::info('Detail kategori berhasil diambil', [
            'category_id'    => $category->id,
            "correlation_id" => $correlationId
        ]);

        return $this->successResponse(
            $category,
            'Detail kategori berhasil diambil'
        );
    }


    // 4. UPDATE KATEGORI
    public function update(Request $request, $id)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info('Request: Update kategori', [
            'category_id'    => $id,
            'payload'        => $request->all(),
            "user_id"   => $userId,
            "correlation_id" => $correlationId
        ]);

        $category = Category::find($id);

        if (!$category) {
            Log::warning('Kategori tidak ditemukan saat update', [
                'category_id'    => $id,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse('Kategori tidak ditemukan.', 404, 'NOT_FOUND');
        }

        $validator = Validator::make($request->all(), [
            'name' => "sometimes|string|unique:categories,name,$id",
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal saat update kategori', [
                'errors'        => $validator->errors(),
                'category_id'   => $id,
                "correlation_id"=> $correlationId
            ]);

            return $this->errorResponse(
                'Input tidak valid.',
                422,
                'VALIDATION_ERROR',
                $validator->errors()
            );
        }

        try {
            $category->update($validator->validated());

            Log::info('Kategori berhasil diupdate', [
                'category_id'    => $category->id,
                "correlation_id" => $correlationId
            ]);

            return $this->successResponse(
                $category,
                'Kategori berhasil diupdate'
            );

        } catch (\Throwable $th) {
            Log::error('Error: Gagal mengupdate kategori', [
                'category_id'    => $id,
                'error'          => $th->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                'Gagal mengupdate kategori.',
                500,
                'SERVER_ERROR',
                $th->getMessage()
            );
        }
    }


    // 5. HAPUS KATEGORI
    public function destroy(Request $request, $id)
    {
        $authUser = $request->auth_user;
        $userId = $authUser["id"];
        $correlationId = $request->attributes->get("correlation_id");

        Log::info('Request: Hapus kategori', [
            'category_id'    => $id,
            "user_id"   => $userId,
            "correlation_id" => $correlationId
        ]);

        $category = Category::find($id);

        if (!$category) {
            Log::warning('Kategori tidak ditemukan saat delete', [
                'category_id'    => $id,
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse('Kategori tidak ditemukan.', 404, 'NOT_FOUND');
        }

        try {
            $category->delete();

            Log::info('Kategori berhasil dihapus', [
                'category_id'    => $id,
                "correlation_id" => $correlationId
            ]);

            return $this->successResponse(
                null,
                'Kategori berhasil dihapus'
            );

        } catch (\Throwable $th) {
            Log::error('Error: Gagal menghapus kategori', [
                'category_id'    => $id,
                'error'          => $th->getMessage(),
                "correlation_id" => $correlationId
            ]);

            return $this->errorResponse(
                'Gagal menghapus kategori.',
                500,
                'SERVER_ERROR',
                $th->getMessage()
            );
        }
    }
}
