<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Persentase SHU
    |--------------------------------------------------------------------------
    |
    | Sisa Hasil Usaha yang dikembalikan ke anggota, dihitung dari total
    | belanjanya selama setahun.
    |
    | ANGKANYA BELUM DITETAPKAN KOPERASI. Di rapat requirement disebut kisaran
    | 0,5%-1% dengan catatan "nanti kita ngobrol", jadi sampai sekarang yang
    | ditampilkan ke anggota masih berupa perkiraan rentang, bukan angka pasti.
    |
    | CARA MENGUBAHNYA: sekarang paling gampang lewat aplikasi —
    | Admin Utama -> Pengaturan -> "Persentase SHU". Nilai dari sana disimpan
    | di tabel settings dan menimpa angka bawaan di bawah ini (lihat ShuService).
    |
    | Angka di bawah cuma dipakai sebagai NILAI BAWAAN selama koperasi belum
    | mengisinya dari aplikasi (mis. tepat setelah hosting, sebelum ada yang
    | membuka halaman Pengaturan).
    |
    | Kalau nanti diputuskan satu angka pasti, isi keduanya sama — tampilannya
    | otomatis berubah dari rentang jadi satu angka.
    |
    */

    'shu' => [
        'persen_min' => (float) env('SHU_PERSEN_MIN', 0.5),
        'persen_maks' => (float) env('SHU_PERSEN_MAKS', 1.0),
    ],
    /*
    |--------------------------------------------------------------------------
    | Akun pengurus pertama
    |--------------------------------------------------------------------------
    | Dipakai AdminUserSeeder. Di server WAJIB diisi lewat .env: ADMIN_EMAIL dan
    | ADMIN_PASSWORD. Nilai bawaan di bawah cuma untuk laptop pengembang, dan
    | seeder menolak jalan di server kalau password-nya masih bawaan.
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@mitradharma.test'),
        'password' => env('ADMIN_PASSWORD', 'admin12345'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identitas aplikasi
    |--------------------------------------------------------------------------
    | Ditampilkan di halaman "Tentang Aplikasi" (khusus pengurus). Pencipta dan
    | tahun sesuai berkas LICENSE, jangan dihapus (lihat butir Atribusi di
    | LICENSE). Naikkan 'versi' kalau ada perubahan besar.
    */
    'aplikasi' => [
        'versi' => '1.0',
        'pembuat' => 'Chandra Harkat Raharja',
        'peran_pembuat' => 'PKL di UPTD Cimahi Technopark',
        'tahun' => 2026,
    ],
];
