<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-head-meta :judul="$title ?? null" />

        <title>{{ $title ?? config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&amp;display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <x-decorative-background />

        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            {{--
                Sebelumnya bar ini solid putih — persis di bawah navbar gradient,
                jadi kesannya "mati" warnanya begitu turun dikit. Translucent +
                wash gradient tipis senada navbar (teal/emerald — identik warna
                koperasi), biar transisi dari navbar berwarna ke konten tetap
                nyambung dan background di belakangnya tetap kelihatan sedikit.
            --}}
            @isset($header)
                <header class="no-print bg-gradient-to-r from-teal-50/80 via-white/80 to-emerald-50/80 backdrop-blur-sm border-b border-teal-100">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Notifikasi pesanan baru. Layout ini cuma dipakai halaman admin,
             jadi cukup dipasang di sini sekali. --}}
        <x-admin.notif-pesanan />
    </body>
</html>
