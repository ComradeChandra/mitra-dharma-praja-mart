<!doctype html>
{{--
    Layout buat halaman PUBLIK (bisa dibuka tanpa login) — beda dari
    x-app-layout yang khusus admin (butuh Auth::user() buat navbar).
    Dipakai pertama kali di halaman katalog produk (resources/views/catalog/index.blade.php).
--}}
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&amp;display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <x-decorative-background />

        {{--
            Header katalog publik — disamakan dengan gradient navbar admin
            (teal→emerald, identik warna koperasi) supaya seluruh aplikasi
            (bukan cuma sisi admin) kerasa satu identitas warna yang sama.
        --}}
        {{-- Tanpa overflow-hidden sengaja, lihat catatan di layouts/navigation.blade.php,
             overflow-hidden di header bisa motong dropdown/menu yang nongol
             di bawah tinggi header. inset-0 di bawah udah otomatis pas tanpa itu. --}}
        <header class="no-print relative bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 shadow-md sticky top-0 z-40">
            <div class="absolute inset-0 opacity-[0.07] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none"></div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                <a href="{{ route('catalog.index') }}" class="flex items-center gap-2.5 font-semibold text-white">
                    <span class="p-1 rounded-full bg-white shadow-sm">
                        <x-application-logo class="h-7 w-7" />
                    </span>
                    <span class="hidden sm:inline">Mitra Dharma Praja Mart</span>
                </a>

                {{-- Tautan navigasi menyesuaikan siapa yang masuk. Tanpa ini,
                     anggota yang menekan "Katalog" kehilangan seluruh navnya,
                     karena halaman katalog memakai layout ini, bukan layout
                     anggota. --}}
                <x-main-nav />
                {{--
                    Sisi kanan header menyesuaikan siapa yang sedang masuk.
                    Sebelumnya di sini selalu ada 3 tautan berjajar ("Login
                    Anggota / Non-Anggota / Login Admin") — tetap muncul walau
                    orangnya sudah masuk, dan pintu pengurus ikut dipajang ke
                    semua orang. Sekarang: satu tombol "Masuk" kalau belum
                    masuk, atau menu profil kalau sudah.
                --}}
                <x-user-menu />
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="no-print mt-12 py-6 text-center text-xs text-gray-400">
            {{-- Semboyan resmi koperasi, diambil dari logonya --}}
            <p class="text-gray-500 font-medium tracking-wide">Kebersamaan untuk Kesejahteraan</p>
            <p class="mt-1">&copy; {{ date("Y") }} Koperasi Mitra Dharma Praja</p>
        </footer>
    </body>
</html>
