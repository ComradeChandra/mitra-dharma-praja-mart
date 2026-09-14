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
        },
    },

    plugins: [forms],
};
