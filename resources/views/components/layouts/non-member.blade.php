<!doctype html>
{{--
    Layout buat halaman non-anggota yang sudah "login" (lihat NonMemberSessionService).
    Terpisah dari x-layouts.member (itu guard 'member' beneran) — non-anggota
    bukan akun personal, cuma sesi OPD bersama, jadi header-nya lebih
    sederhana: tampilin nama OPD yang lagi "login" + tombol keluar, tanpa
    menu riwayat/profil kayak anggota (non-anggota tidak punya itu, lihat
    CLAUDE.md — non-anggota TIDAK punya akses profil SHU).

    Props:
    - opd: model OpdDepartment yang lagi "login" di sesi ini
--}}
@props(['opd'])

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

        {{-- Tanpa overflow-hidden sengaja, lihat catatan di layouts/navigation.blade.php --}}
        <header class="relative bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 shadow-md sticky top-0 z-40">
            <div class="absolute inset-0 opacity-[0.07] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none"></div>

            <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
                <div class="flex items-center gap-4 min-w-0">
                <div class="flex items-center gap-2.5 font-semibold text-white shrink-0">
                    <span class="p-1 rounded-full bg-white shadow-sm">
                        <x-application-logo class="h-7 w-7" />
                    </span>
                    <span class="hidden sm:inline">Mitra Dharma Praja Mart</span>
                </div>

                {{-- Tautannya dirender x-main-nav, sama seperti di layout
                     anggota dan layout publik. Dulu diketik langsung di sini,
                     akibatnya dari halaman ini tidak ada jalan ke katalog. --}}
                <x-main-nav />
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <span class="text-teal-100 hidden sm:inline">
                        Non-anggota · {{ $opd->name }}
                    </span>
                    <form method="POST" action="{{ route('non-member.logout') }}">
                        @csrf
                        <button type="submit" class="text-teal-200 hover:text-white">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>
    </body>
</html>
