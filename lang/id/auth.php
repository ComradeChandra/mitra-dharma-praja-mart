<?php

/*
| Pesan login bawaan Laravel, dipakai form login admin
| (App\Http\Requests\Auth\LoginRequest). Tanpa berkas ini, salah ketik
| password memunculkan "These credentials do not match our records.".
| Login anggota dan non-anggota menulis pesannya sendiri di Form Request
| masing-masing.
*/

return [
    'failed' => 'Email atau password salah.',
    'password' => 'Password salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',
];
