<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // 1. LIHAT SEMUA PRODUK (GET /products)
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json(['status' => 'success', 'data' => $products]);
    }

    // 2. TAMBAH PRODUK BARU (POST /products)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'category_id' => 'required',
            'user_id' => 'required'
        ]);

        $product = Product::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dibuat!',
            'data' => $product
        ], 201);
    }

    // 3. LIHAT DETAIL 1 PRODUK (GET /products/{id})
    public function show($id)
    {
        $product = Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json(['data' => $product]);
    }

    // 4. UPDATE PRODUK (PUT /products/{id})  <-- INI YANG TADI HILANG
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        // Update datanya
        $product->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diupdate', 
            'data' => $product
        ]);
    }

    // 5. HAPUS PRODUK (DELETE /products/{id}) <-- INI JUGA PENTING
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $product->delete();

        return response()->json(['status' => 'success', 'message' => 'Produk berhasil dihapus']);
    }
}