<!doctype html>
{{--
    Layout buat halaman anggota yang SUDAH LOGIN (guard 'member'). Terpisah
    dari x-app-layout (itu bacanya guard 'web'/admin) supaya info nama &
    tombol logout di sini benar-benar punya anggota yang login, bukan admin.
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
            Header anggota — disamakan dengan gradient navbar admin (teal→emerald,
            identik warna koperasi) biar area anggota nggak kerasa "beda aplikasi"
            sama area admin.
        --}}
        {{-- Tanpa overflow-hidden sengaja, lihat catatan di layouts/navigation.blade.php,
             overflow-hidden di header bisa motong dropdown/menu yang nongol
             di bawah tinggi header. inset-0 di bawah udah otomatis pas tanpa itu. --}}
        {{-- x-data dipakai buat buka-tutup menu hamburger, panelnya ada di
             bagian bawah header. Penting karena sebagian besar anggota
             memesan dari HP. --}}
        <header
            x-data="{ open: false }"
            class="relative bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 shadow-md sticky top-0 z-40"
        >
            <div class="absolute inset-0 opacity-[0.07] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none"></div>

            @php
                $anggotaLogin = Auth::guard('member')->user();
            @endphp

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                <div class="flex items-center gap-6 min-w-0">
                    <a href="{{ route('member.dashboard') }}" class="flex items-center gap-2.5 font-semibold text-white shrink-0">
                        <span class="p-1 rounded-full bg-white shadow-sm">
                            <x-application-logo class="h-7 w-7" />
                        </span>
                        {{-- Nama aplikasi disembunyikan di layar sempit, di HP
                             logonya saja sudah cukup sebagai penanda. --}}
                        <span class="hidden sm:inline whitespace-nowrap">Mitra Dharma Praja Mart</span>
                    </a>

                    {{--
                        Nav mendatar muncul dari 1024px. Di bawah itu, lima tautan
                        plus nama anggota dan tombol keluar tidak muat, tulisannya
                        patah dua baris dan saling tabrakan. Jadi di layar sempit
                        semuanya masuk menu hamburger.
                    --}}
                    <nav class="hidden lg:flex items-center gap-5 text-sm nav-pop-in">
                        <a href="{{ route('member.dashboard') }}" class="whitespace-nowrap {{ request()->routeIs('member.dashboard') ? 'text-white font-semibold' : 'text-teal-100 hover:text-white' }}">Beranda</a>
                        <a href="{{ route('catalog.index') }}" class="whitespace-nowrap text-teal-100 hover:text-white">Katalog</a>
                        <a href="{{ route('member.orders.create') }}" class="whitespace-nowrap {{ request()->routeIs('member.orders.create') ? 'text-white font-semibold' : 'text-teal-100 hover:text-white' }}">Pesan Produk</a>
                        <a href="{{ route('member.orders.index') }}" class="whitespace-nowrap {{ request()->routeIs('member.orders.index') || request()->routeIs('member.orders.show') ? 'text-white font-semibold' : 'text-teal-100 hover:text-white' }}">Pesanan Saya</a>
                        <a href="{{ route('member.product-requests.index') }}" class="whitespace-nowrap {{ request()->routeIs('member.product-requests.*') ? 'text-white font-semibold' : 'text-teal-100 hover:text-white' }}">Permintaan Produk</a>
                    </nav>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    {{--
                        Profil dan keluar ditaruh di dropdown avatar, tidak
                        berjajar di navbar, supaya tidak menabrak avatar di layar
                        menengah.
                    --}}
                    <div class="relative hidden lg:block" x-data="{ menu: false }" x-on:keydown.escape.window="menu = false">
                        <button
                            type="button"
                            x-on:click="menu = ! menu"
                            class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full text-white hover:bg-white/10 transition"
                            x-bind:aria-expanded="menu"
                        >
                            @if ($anggotaLogin->photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($anggotaLogin->photo_path) }}"
                                     alt="Foto profil"
                                     class="h-8 w-8 rounded-full object-cover ring-2 ring-white/70 shrink-0">
                            @else
                                <span class="h-8 w-8 rounded-full bg-white text-teal-700 flex items-center justify-center text-sm font-semibold shrink-0">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($anggotaLogin->full_name, 0, 1)) }}
                                </span>
                            @endif
                            <span class="text-sm max-w-[9rem] truncate">{{ $anggotaLogin->full_name }}</span>
                            <svg class="h-4 w-4 text-white/70 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div x-show="menu" x-cloak x-on:click.outside="menu = false"
                             class="absolute right-0 mt-2 w-52 rounded-2xl bg-white shadow-xl border border-gray-100 py-2 z-50">
                            <div class="px-4 pb-2 mb-1 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $anggotaLogin->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $anggotaLogin->member_code }}</p>
                            </div>
                            <a href="{{ route('member.profile.edit') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Profil Saya</a>
                            <form method="POST" action="{{ route('member.logout') }}" class="mt-1 pt-1 border-t border-gray-100">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-900">Keluar</button>
                            </form>
                        </div>
                    </div>

                    {{-- Di bawah 1024px: avatar polos (tanpa nama) + hamburger --}}
                    @if ($anggotaLogin->photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($anggotaLogin->photo_path) }}"
                             alt="Foto profil"
                             class="lg:hidden h-8 w-8 rounded-full object-cover ring-2 ring-white/70">
                    @else
                        <span class="lg:hidden h-8 w-8 rounded-full bg-white text-teal-700 flex items-center justify-center text-sm font-semibold">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($anggotaLogin->full_name, 0, 1)) }}
                        </span>
                    @endif

                    <button
                        type="button"
                        x-on:click="open = ! open"
                        class="lg:hidden p-2 -mr-2 rounded-lg text-teal-100 hover:text-white hover:bg-white/10 transition"
                        x-bind:aria-expanded="open"
                        aria-label="Buka menu"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            {{-- Ikon garis tiga saat tertutup, ikon silang saat terbuka --}}
                            <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{--
                Panel menu versi HP — isinya sama persis dengan nav mendatar di
                atas, cuma disusun ke bawah biar gampang disentuh jempol.
                Pakai <x-responsive-nav-link> yang sudah ada (dipakai juga di
                navbar admin) supaya gayanya konsisten, bukan bikin gaya baru.
            --}}
            <div x-show="open" x-cloak class="lg:hidden relative border-t border-white/10 pb-3 pt-2 space-y-1">
                <x-responsive-nav-link :href="route('member.dashboard')" :active="request()->routeIs('member.dashboard')">
                    Beranda
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.index')">
                    Katalog
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('member.orders.create')" :active="request()->routeIs('member.orders.create')">
                    Pesan Produk
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('member.orders.index')" :active="request()->routeIs('member.orders.index') || request()->routeIs('member.orders.show')">
                    Pesanan Saya
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('member.product-requests.index')" :active="request()->routeIs('member.product-requests.*')">
                    Permintaan Produk
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('member.profile.edit')" :active="request()->routeIs('member.profile.*')">
                    Profil Saya
                </x-responsive-nav-link>

                {{-- Nama anggota + tombol keluar ditaruh di sini, karena di layar
                     sempit keduanya disembunyikan dari header (cuma avatar). --}}
                <div class="px-4 pt-3 mt-2 border-t border-white/10">
                    <p class="text-sm text-teal-200">Masuk sebagai {{ $anggotaLogin->full_name }}</p>
                    <form method="POST" action="{{ route('member.logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-white hover:text-teal-100">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>
    </body>
</html>
