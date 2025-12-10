# Product Service (Microservice Kelompok 6)

Service ini menangani manajemen Produk dan Kategori Bengkel.

## Cara Install
1. `composer install`
2. Copy `.env.example` jadi `.env`, atur database.
3. `php artisan migrate`
4. `php artisan serve`

## Daftar Endpoint
- GET /api/products (Lihat semua)
- POST /api/products (Tambah)
- PUT /api/products/{id} (Edit)
- DELETE /api/products/{id} (Hapus)

## Status
- CRUD: ✅ Complete
- Database: ✅ UUID Ready
- Testing: ✅ Passed