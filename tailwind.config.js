import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Aplikasi ini sengaja satu tampilan terang. Bawaan Tailwind v3 ('media')
    // mengikuti mode gelap HP/laptop, dan paginasi bawaan Laravel punya gaya
    // gelap: pemakai mode gelap melihat tombol halaman hitam di halaman terang.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Kelas yang ditambahkan lewat JavaScript (mis. tombol yang diredupkan
        // saat form sedang dikirim, resources/js/cegah-kirim-ganda.js) juga harus
        // dipindai, kalau tidak kelasnya dibuang dari CSS hasil build.
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Bayangan kartu bersama — lembut & berlapis, biar kartu terasa
            // sedikit mengambang di atas latar krem tanpa terlihat berat.
            // Didefinisikan sekali di sini supaya seragam di semua kartu
            // (x-card, tabel, statistik, kartu produk). Ganti di sini kalau
            // mau menyetel bobot bayangannya.
            boxShadow: {
                card: '0 1px 2px 0 rgb(16 24 40 / 0.04), 0 12px 28px -12px rgb(16 24 40 / 0.10)',
                'card-hover': '0 2px 6px 0 rgb(16 24 40 / 0.06), 0 18px 40px -16px rgb(16 24 40 / 0.16)',
            },
        },
    },

    plugins: [forms],
};
