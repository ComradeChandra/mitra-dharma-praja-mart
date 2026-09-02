<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom satuan pada produk.
 *
 * Isinya kata satuan apa adanya seperti yang diketik admin: "renceng",
 * "karung", "botol", "dus", "kg". Dipakai buat memperjelas angka jumlah di
 * form pesan, rekap belanja, dan invoice WhatsApp.
 *
 * Sengaja nullable. Sebelum ini satuan biasa dititipkan di nama produk
 * ("Beras Pandan Wangi 5kg") dan cara itu masih jalan, jadi produk lama tidak
 * perlu diapa-apakan. Kalau kolomnya kosong, tampilannya persis seperti
 * sebelum kolom ini ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit', 20)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
