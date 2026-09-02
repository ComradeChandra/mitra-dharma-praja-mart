{{--
    Layout halaman login (dipakai bertiga: admin, anggota, non-anggota).

    Props:
    - subtitle : keterangan kecil di bawah nama koperasi. WAJIB diisi tiap
                 halaman login, karena dulu ketiganya ikut menulis "Portal
                 Pengurus Koperasi" — bikin anggota biasa bingung ("saya kan
                 bukan pengurus, salah halaman ya?").
--}}
@props(['subtitle' => 'Koperasi Mitra Dharma Praja'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <title>{{ config('app.name', 'Mitra Dharma Praja Mart') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-decorative-background />

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 pb-10">
            <div class="flex flex-col items-center">
                {{-- Logo dibungkus "halo" gradient bulat (senada navbar, teal/hijau
                     identik warna koperasi) — sebelumnya logo polos tanpa aksen warna
                     apa pun di halaman paling pertama yang dilihat orang. --}}
                {{-- Wadah PUTIH, bukan hijau: logo koperasi punya garis tepi navy
                     gelap & isian putih, jadi kalau ditaruh di atas hijau tua
                     detailnya tenggelam. Di halaman masuk ruangnya lega, jadi
                     logonya ditampilkan agak besar. --}}
                <a href="{{ route('catalog.index') }}" class="p-3 rounded-full bg-white shadow-lg shadow-emerald-200/60 ring-1 ring-emerald-100">
                    <x-application-logo class="w-16 h-16" />
                </a>
                <p class="mt-3 font-semibold text-gray-700">Mitra Dharma Praja Mart</p>
                <p class="text-xs text-gray-400">{{ $subtitle }}</p>
            </div>

            {{-- Kartu form: garis aksen gradient tipis di atas + translucent (bukan
                 putih solid) biar background yang bergerak di belakang tetap kebaca. --}}
            <div class="w-full sm:max-w-md mt-6 overflow-hidden sm:rounded-2xl shadow-xl shadow-teal-100/50 border border-white/60">
                <div class="h-1.5 bg-gradient-to-r from-teal-500 via-emerald-500 to-teal-600"></div>
                <div class="px-6 py-6 bg-white/95 backdrop-blur-sm">
                    {{ $slot }}
                </div>
            </div>

            {{--
                Tombol kembali — kalau orang salah pilih pintu masuk (mis. anggota
                kepencet "Login Admin"), dia bisa balik tanpa harus tau tombol back
                bawaan browser/HP. Sengaja teks + panah yang jelas, bukan cuma ikon,
                dan area sentuhnya dilebarkan (px-4 py-2) biar gampang dipencet jempol.
            --}}
            <a
                href="{{ route('catalog.index') }}"
                class="mt-6 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-800 hover:bg-white/70 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m0 0 7 7m-7-7 7-7" />
                </svg>
                Kembali ke Katalog
            </a>
        </div>
    </body>
</html>
