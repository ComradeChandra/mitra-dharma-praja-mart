{{--
    Sisi kanan header halaman publik — menyesuaikan siapa yang sedang masuk.

    Empat kemungkinan keadaan:
    1. Belum masuk       -> satu tombol "Masuk" (menuju halaman masuk terpadu).
    2. Anggota           -> avatar + nama, dropdown berisi menu anggota.
    3. Non-anggota       -> nama OPD + tombol keluar (tidak punya beranda/profil).
    4. Pengurus (admin)  -> tautan ke dashboard + tombol keluar.

    Pintu masuk pengurus SENGAJA tidak dipajang di sini. Dulu ada tautan
    "Login Admin" berjajar dengan tautan anggota, jadi seolah-olah menawarkan
    halaman pengurus ke semua pengunjung. Sekarang pilihan itu ada di dalam
    halaman masuk, di balik dropdown peran.

    Data $anggota / $admin / $opdNonAnggota disiapkan di
    App\View\Components\UserMenu — file ini murni merender, tidak query
    database sendiri (aturan CLAUDE.md).
--}}

@if ($anggota)
    {{-- ---------- ANGGOTA ---------- --}}
    <div class="relative" x-data="{ buka: false }" x-on:keydown.escape.window="buka = false">
        <button
            type="button"
            x-on:click="buka = ! buka"
            class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-full text-white hover:bg-white/10 transition"
            x-bind:aria-expanded="buka"
        >
            @if ($anggota->photo_path)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::url($anggota->photo_path) }}"
                    alt="Foto profil"
                    class="h-8 w-8 rounded-full object-cover ring-2 ring-white/70 shrink-0"
                >
            @else
                <span class="h-8 w-8 rounded-full bg-white text-emerald-700 flex items-center justify-center text-sm font-semibold shrink-0">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($anggota->full_name, 0, 1)) }}
                </span>
            @endif
            <span class="hidden sm:inline text-sm max-w-[10rem] truncate">{{ $anggota->full_name }}</span>
            <svg class="h-4 w-4 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>

        {{-- Klik di luar menu ikut menutupnya --}}
        <div
            x-show="buka"
            x-cloak
            x-on:click.outside="buka = false"
            class="absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-xl border border-gray-100 py-2 z-50"
        >
            <div class="px-4 pb-2 mb-1 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $anggota->full_name }}</p>
                <p class="text-xs text-gray-400">Anggota · {{ $anggota->member_code }}</p>
            </div>

            <a href="{{ route('member.dashboard') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Beranda Saya</a>
            <a href="{{ route('member.orders.create') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Pesan Produk</a>
            <a href="{{ route('member.orders.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Pesanan Saya</a>
            <a href="{{ route('member.product-requests.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Permintaan Produk</a>
            <a href="{{ route('member.profile.edit') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">Profil Saya</a>

            <form method="POST" action="{{ route('member.logout') }}" class="mt-1 pt-1 border-t border-gray-100">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                    Keluar
                </button>
            </form>
        </div>
    </div>

@elseif ($opdNonAnggota)
    {{-- ---------- NON-ANGGOTA ---------- --}}
    <div class="flex items-center gap-3 text-sm">
        <span class="hidden sm:inline text-teal-100 max-w-[14rem] truncate">{{ $opdNonAnggota->name }}</span>
        <a href="{{ route('non-member.orders.create') }}" class="px-3 py-1.5 rounded-lg text-white hover:bg-white/10 transition">Pesan</a>
        {{-- Label dipendekkan di HP biar header tidak sesak (lihat catatan sama
             di components/layouts/non-member.blade.php) --}}
        <a href="{{ route('non-member.product-requests.create') }}" class="px-3 py-1.5 rounded-lg text-teal-100 hover:text-white hover:bg-white/10 transition">
            <span class="sm:hidden">Usul</span>
            <span class="hidden sm:inline">Usulkan Produk</span>
        </a>
        <form method="POST" action="{{ route('non-member.logout') }}">
            @csrf
            <button type="submit" class="px-3 py-1.5 rounded-lg text-teal-100 hover:text-white hover:bg-white/10 transition">Keluar</button>
        </form>
    </div>

@elseif ($admin)
    {{-- ---------- PENGURUS ---------- --}}
    <div class="flex items-center gap-3 text-sm">
        <span class="hidden sm:inline text-teal-100">{{ $admin->name }}</span>
        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg bg-white/15 text-white hover:bg-white/25 transition">Kelola Koperasi</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="px-3 py-1.5 rounded-lg text-teal-100 hover:text-white hover:bg-white/10 transition">Keluar</button>
        </form>
    </div>

@else
    {{-- ---------- BELUM MASUK ---------- --}}
    <a
        href="{{ route('masuk') }}"
        class="px-4 py-2 rounded-xl bg-white text-emerald-800 text-sm font-semibold hover:bg-emerald-50 shadow-sm transition"
    >
        Masuk
    </a>
@endif
