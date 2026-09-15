<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tingkatan akun pengurus (16 Sep 2026).
 *
 * Sebelumnya semua akun di tabel users punya akses penuh, dan tidak ada
 * halaman untuk menambah akun, jadi semua staf memakai satu login bersama.
 * Sekarang ada dua peran (lihat App\Enums\AdminRole):
 * - admin_utama : akses penuh + kelola akun pengurus + pengaturan koperasi
 * - pengurus    : semua pekerjaan harian (pesanan, produk, anggota, rekap)
 *
 * is_active dipakai untuk menonaktifkan akun staf yang sudah tidak bertugas,
 * tanpa menghapusnya (catatan siapa menangani apa tetap utuh).
 *
 * Akun yang SUDAH ADA dijadikan admin_utama, karena selama ini memang
 * aksesnya penuh. Akun baru bawaannya pengurus (hak paling kecil).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('pengurus')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')->update(['role' => 'admin_utama']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
