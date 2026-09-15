<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan koperasi yang bisa diubah pengurus dari aplikasi (16 Sep 2026).
 *
 * Isinya cuma SATU baris. Kolom pertamanya nomor WhatsApp koperasi, dipakai
 * tombol "Hubungi Pengurus" di halaman anggota & non-anggota. Disimpan di
 * database, bukan di .env, supaya pengurus bisa menggantinya sendiri lewat
 * Admin -> Pengaturan tanpa menyentuh server.
 *
 * Cuma menambah tabel baru; data lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // Kosong = tombol WhatsApp tidak ditampilkan
            $table->string('whatsapp_number', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
