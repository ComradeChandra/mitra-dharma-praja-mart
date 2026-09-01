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
            // ID otomatis (primary key)
            $table->id();

            // Kategori produk, contoh: "Sembako", "Sayur", "Alat Tulis".
            // Disimpan sebagai teks bebas (bukan tabel terpisah) karena skala produk masih
            // kecil (±10 produk di awal), jangan over-engineer.
            $table->string('category');

            // Nama produk
            $table->string('name');

            // Harga beli (harga kulakan koperasi ke pemasok), dipakai internal admin
            $table->decimal('buy_price', 12, 2);

            // Harga jual ke anggota/non-anggota. Nullable karena produk fluktuatif (mis. telur,
            // sayur) belum punya harga jual pasti sampai admin verifikasi setelah barang datang.
            $table->decimal('sell_price', 12, 2)->nullable();

            // Path file foto produk (opsional, bisa upload screenshot JPG/PNG)
            $table->string('image_path')->nullable();

            // Tandai produk dengan harga yang naik-turun (misal sayur/telur). Kalau true,
            // harga final dikunci manual oleh admin saat proses verifikasi pesanan.
            $table->boolean('is_fluctuating')->default(false);

            // Tandai apakah produk ini melacak stok atau tidak. Stok sifatnya opsional
            // per produk sesuai konsep pre-order (bukan wajib e-commerce).
            $table->boolean('has_stock_tracking')->default(false);

            // Jumlah stok, hanya relevan kalau has_stock_tracking = true
            $table->integer('stock')->nullable();

            // Status aktif/nonaktif. Produk nonaktif tidak muncul di katalog pemesanan,
            // tapi tetap tersimpan supaya riwayat pesanan lama tidak rusak.
            $table->boolean('is_active')->default(true);

            // created_at & updated_at otomatis
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
