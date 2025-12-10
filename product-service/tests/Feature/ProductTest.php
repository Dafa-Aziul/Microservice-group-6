<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Category; // Panggil Model Kategori
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    // Test 1: Cek apakah bisa ambil data produk (GET /products)
    public function test_can_get_all_products()
    {
        // Kita coba akses halaman /api/products
        $response = $this->get('/api/products');

        // Kita berharap server membalas dengan status 200 (OK)
        $response->assertStatus(200);
    }

    // Test 2: Cek apakah bisa tambah kategori (biar kategori ga kosong)
    public function test_can_create_category()
    {
        $response = $this->post('/api/categories', [
            'name' => 'Kategori Test ' . rand(1, 1000) // Nama acak biar unik
        ]);

        $response->assertStatus(201); // Berharap status 201 (Created)
    }
}