<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasFactory, HasUuids;

    // Daftar kolom yang boleh diisi
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'user_id'
    ];

    // Relasi: Produk ini milik satu Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}