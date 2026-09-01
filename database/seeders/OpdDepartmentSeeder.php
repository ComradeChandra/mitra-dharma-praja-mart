<?php

namespace Database\Seeders;

use App\Models\OpdDepartment;
use Illuminate\Database\Seeder;

/**
 * Seeder daftar OPD (Organisasi Perangkat Daerah) Kota Cimahi, dipakai buat
 * dropdown pilihan non-anggota saat memesan.
 *
 * nama-nama OPD di bawah ini adalah CONTOH/PLACEHOLDER (saya tidak
 * punya daftar OPD resmi Kota Cimahi yang terverifikasi). Silakan cek ke
 * struktur OPD resmi Pemkot Cimahi dan edit daftar ini lewat halaman admin
 * nanti (bukan lewat seeder ini) supaya sesuai kondisi asli.
 */
class OpdDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $namaOpd = [
            'Sekretariat Daerah',
            'Dinas Pendidikan',
            'Dinas Kesehatan',
            'Dinas Perhubungan',
            'Dinas Pekerjaan Umum dan Penataan Ruang',
            'Badan Pengelolaan Keuangan dan Aset Daerah',
            'Dinas Kependudukan dan Pencatatan Sipil',
            'Dinas Sosial',
            'Satuan Polisi Pamong Praja',
            'Kecamatan Cimahi Tengah',
        ];

        foreach ($namaOpd as $nama) {
            // updateOrCreate: kalau nama OPD ini sudah ada, tidak dibuat dobel
            // (aman dijalankan berkali-kali tanpa bikin data duplikat).
            // Kode akses contoh buat testing login non-anggota: "opd12345"
            // (di-hash otomatis lewat cast 'hashed' di model OpdDepartment).
            // Di dunia nyata, kode ini dibuatkan admin per OPD lewat form.
            OpdDepartment::updateOrCreate(
                ['name' => $nama],
                ['access_code' => 'opd12345']
            );
        }
    }
}
