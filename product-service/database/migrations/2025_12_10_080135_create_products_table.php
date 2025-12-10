<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Wajib UUID
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Angka desimal (harga)

            // Relasi ke tabel categories (Foreign Key)
            $table->foreignUuid('category_id')
                ->constrained('categories')
                ->onDelete('cascade');

            // User ID disimpan sebagai UUID biasa (Tanpa Foreign Key ke tabel user)
            // Karena tabel User ada di database temanmu, bukan di sini.
            $table->uuid('user_id'); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
