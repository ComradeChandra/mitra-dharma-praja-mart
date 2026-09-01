<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah alamat anggota.
 *
 * Dipakai sebagai isian awal kolom alamat di form pemesanan. Anggota tidak
 * perlu mengetik ulang alamatnya tiap kali memesan, tapi tetap boleh
 * menggantinya kalau kali ini mau dikirim ke tempat lain (mis. ke kantor,
 * bukan ke rumah). Alamat yang dipakai per pesanan disimpan terpisah di
 * `orders.delivery_address`, jadi mengubah alamat profil tidak mengubah
 * riwayat pesanan lama.
 *
 * Nullable karena data anggota diinput admin dan alamatnya belum tentu
 * langsung tersedia, anggota bisa mengetiknya sendiri saat memesan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('address')->nullable()->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
