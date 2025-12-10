<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // <--- PENTING: Panggil Jimat UUID

class Category extends Model
{
    use HasFactory, HasUuids; // <--- Aktifkan Jimatnya

    protected $fillable = ['name']; // Izinkan kolom 'name' diisi
}