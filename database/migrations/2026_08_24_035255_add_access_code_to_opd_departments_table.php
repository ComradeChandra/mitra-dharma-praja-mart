<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom access_code ke opd_departments, kode akses login non-anggota
 * per OPD (bukan akun personal per orang), sesuai keputusan final soal
 * non-anggota, lihat CLAUDE.md bagian Aktor
 * (lihat CLAUDE.md, Lampiran B).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opd_departments', function (Blueprint $table) {
            // Nullable di level database (jaga-jaga OPD lama belum diisi kode-nya
            // admin), tapi wajib diisi di StoreOpdDepartmentRequest saat OPD
            // baru dibuat, sama seperti pola password anggota. Di-hash sama
            // seperti password anggota, walau ini kode bersama (bukan akun
            // personal), tetap kredensial yang sebaiknya tidak tersimpan polos.
            $table->string('access_code')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('opd_departments', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};
