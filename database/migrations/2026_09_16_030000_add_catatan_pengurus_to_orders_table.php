<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan pengurus pada pesanan (16 Sep 2026).
 *
 * Diisi otomatis saat pengurus menghapus satu barang dari pesanan (mis.
 * telurnya habis di grosir), berisi apa yang dihapus dan alasannya. Tampil
 * di halaman pesanan pemesan dan ikut tertulis di invoice WhatsApp, supaya
 * pemesan tahu kenapa isi pesanannya berubah.
 *
 * Cuma menambah satu kolom kosong; data lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('catatan_pengurus')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('catatan_pengurus');
        });
    }
};
