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
    | CARA MENGUBAHNYA setelah pengurus memutuskan: cukup ganti dua nilai di
    | bawah ini (atau isi SHU_PERSEN_MIN & SHU_PERSEN_MAKS di berkas .env).
    | Tidak perlu menyentuh kode atau tampilan mana pun.
    |
    | Kalau nanti diputuskan satu angka pasti, isi keduanya sama — tampilannya
    | otomatis berubah dari rentang jadi satu angka.
    |
    */

    'shu' => [
        'persen_min' => (float) env('SHU_PERSEN_MIN', 0.5),
        'persen_maks' => (float) env('SHU_PERSEN_MAKS', 1.0),

        // Ditampilkan di bawah angka estimasi. Kalau persentasenya sudah pasti,
        // kalimat ini sebaiknya ikut diganti.
        'sudah_final' => (bool) env('SHU_SUDAH_FINAL', false),
    ],

];
