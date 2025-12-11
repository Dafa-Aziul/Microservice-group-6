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
    public function index()
    {
        Log::info('Request: Ambil semua kategori');

        $categories = Category::all();

        Log::info('Response: Daftar kategori berhasil diambil', [
            'count' => $categories->count()
        ]);

        return $this->successResponse(
            $categories,
            'Daftar kategori berhasil diambil'
        );
    }

    // 2. TAMBAH KATEGORI BARU
    public function store(Request $request)
    {
        Log::info('Request: Tambah kategori baru', [
            'payload' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories,name',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal saat membuat kategori', [
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
            $category = Category::create($validator->validated());

            Log::info('Kategori berhasil dibuat', [
                'id' => $category->id,
                'name' => $category->name
            ]);

            return $this->successResponse(
                $category,
                'Kategori berhasil dibuat',
                201
            );

        } catch (\Throwable $th) {
            Log::error('Error: Gagal membuat kategori', [
                'error' => $th->getMessage()
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
    public function show($id)
    {
        Log::info('Request: Ambil detail kategori', [
            'id' => $id
        ]);

        $category = Category::find($id);

        if (!$category) {
            Log::warning('Kategori tidak ditemukan', [
                'id' => $id
            ]);

            return $this->errorResponse('Kategori tidak ditemukan.', 404, 'NOT_FOUND');
        }

        Log::info('Detail kategori berhasil diambil', [
            'id' => $category->id
        ]);

        return $this->successResponse(
            $category,
            'Detail kategori berhasil diambil'
        );
    }

    // 4. UPDATE KATEGORI
    public function update(Request $request, $id)
    {
        Log::info('Request: Update kategori', [
            'id' => $id,
            'payload' => $request->all()
        ]);

        $category = Category::find($id);

        if (!$category) {
            Log::warning('Kategori tidak ditemukan saat update', [
                'id' => $id
            ]);

            return $this->errorResponse('Kategori tidak ditemukan.', 404, 'NOT_FOUND');
        }

        $validator = Validator::make($request->all(), [
            'name' => "sometimes|string|unique:categories,name,$id",
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal saat update kategori', [
                'errors' => $validator->errors(),
                'id' => $id
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
                'id' => $category->id
            ]);

            return $this->successResponse(
                $category,
                'Kategori berhasil diupdate'
            );

        } catch (\Throwable $th) {
            Log::error('Error: Gagal mengupdate kategori', [
                'id' => $id,
                'error' => $th->getMessage()
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
    public function destroy($id)
    {
        Log::info('Request: Hapus kategori', [
            'id' => $id
        ]);

        $category = Category::find($id);

        if (!$category) {
            Log::warning('Kategori tidak ditemukan saat delete', [
                'id' => $id
            ]);

            return $this->errorResponse('Kategori tidak ditemukan.', 404, 'NOT_FOUND');
        }

        try {
            $category->delete();

            Log::info('Kategori berhasil dihapus', [
                'id' => $id
            ]);

            return $this->successResponse(
                null,
                'Kategori berhasil dihapus'
            );

        } catch (\Throwable $th) {
            Log::error('Error: Gagal menghapus kategori', [
                'id' => $id,
                'error' => $th->getMessage()
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
