<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /categories
    public function index() {
        return response()->json(['data' => Category::all()]);
    }

    // POST /categories
    public function store(Request $request) {
        $validated = $request->validate(['name' => 'required|unique:categories,name']);
        $category = Category::create($validated);
        return response()->json(['message' => 'Kategori dibuat', 'data' => $category], 201);
    }

    // GET /categories/{id}
    public function show($id) {
        return response()->json(['data' => Category::find($id)]);
    }

    // PUT /categories/{id}
    public function update(Request $request, $id) {
        $category = Category::find($id);
        $category->update($request->all());
        return response()->json(['message' => 'Kategori diupdate', 'data' => $category]);
    }

    // DELETE /categories/{id}
    public function destroy($id) {
        Category::destroy($id);
        return response()->json(['message' => 'Kategori dihapus']);
    }
}