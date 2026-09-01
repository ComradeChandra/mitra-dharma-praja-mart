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
        Schema::create('order_items', function (Blueprint $table) {
            // ID otomatis (primary key)
            $table->id();

            // Pesanan induk dari item ini. cascadeOnDelete: kalau pesanan dihapus,
            // semua item di dalamnya ikut terhapus (item tidak bisa berdiri sendiri).
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Produk yang dipesan
            $table->foreignId('product_id')->constrained('products');

            // Jumlah produk yang dipesan (minimal 1, makanya unsigned)
            $table->unsignedInteger('quantity');

            // Harga produk saat pesanan dibuat atau diverifikasi, dikunci di sini supaya
            // kalau harga produk berubah di kemudian hari, riwayat pesanan lama tidak berubah.
            // Nullable karena untuk produk fluktuatif, kolom ini baru diisi admin saat verifikasi.
            $table->decimal('price_at_order', 12, 2)->nullable();

            // created_at & updated_at otomatis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
