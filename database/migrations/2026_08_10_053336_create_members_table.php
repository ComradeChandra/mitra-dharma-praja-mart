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
        Schema::create('members', function (Blueprint $table) {
            // ID otomatis (primary key)
            $table->id();

            // Kode unik anggota yang diinput admin sendiri, contoh: "0010 A".
            // Unik supaya tidak ada dua anggota dengan kode sama.
            $table->string('member_code')->unique();

            // Nama lengkap anggota, ditampilkan di daftar pilih nama saat login tanpa password
            $table->string('full_name');

            // Nomor WhatsApp anggota, dipakai buat kirim invoice via link wa.me
            $table->string('whatsapp_number');

            // Status aktif/nonaktif anggota. Anggota nonaktif tidak muncul di daftar pilih
            // nama saat pemesanan, tapi datanya tetap tersimpan (riwayat belanja/SHU).
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
        Schema::dropIfExists('members');
    }
};
