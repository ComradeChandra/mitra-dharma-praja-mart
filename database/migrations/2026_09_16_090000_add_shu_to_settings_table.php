<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persentase SHU di tabel settings (16 Sep 2026).
 *
 * Sebelumnya persentase SHU cuma bisa diubah lewat berkas config/koperasi.php
 * di server. Sekarang Admin Utama bisa mengaturnya sendiri dari halaman
 * Admin -> Pengaturan, dan nilainya disimpan di sini.
 *
 * Nullable: selama belum diisi dari aplikasi, perhitungan SHU memakai nilai
 * bawaan di config/koperasi.php (lihat ShuService). Jadi data lama dan
 * pemasangan baru tetap berjalan tanpa harus mengisi apa pun dulu.
 *
 * Rentang perkiraan: kalau min dan maks sama, artinya persentasenya sudah
 * pasti dan tampilan anggota berhenti berupa rentang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // 0,00 sampai 999,99 persen; jauh lebih dari cukup (SHU kisaran 0,5-1%)
            $table->decimal('shu_persen_min', 5, 2)->nullable()->after('whatsapp_number');
            $table->decimal('shu_persen_maks', 5, 2)->nullable()->after('shu_persen_min');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['shu_persen_min', 'shu_persen_maks']);
        });
    }
};
