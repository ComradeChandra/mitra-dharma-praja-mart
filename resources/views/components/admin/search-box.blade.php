{{--
    Kotak cari di daftar-daftar admin (anggota, produk).

    Sengaja form GET biasa, bukan penyaring di sisi tampilan seperti di form
    pemesanan. Alasannya datanya berhalaman: menyaring di browser cuma akan
    menyaring halaman yang sedang terbuka, bukan seluruh isinya.

    Props:
    - action      : URL tujuan pencarian (biasanya rute index halaman itu)
    - nilai       : kata kunci yang sedang aktif, buat mengisi kotaknya kembali
    - placeholder : contoh kata kunci, disesuaikan tiap halaman
--}}
@props(['action', 'nilai' => '', 'placeholder' => 'Cari...'])

<form method="GET" action="{{ $action }}" class="relative w-full sm:max-w-xs">
    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="7" />
            <path stroke-linecap="round" d="m20 20-3.5-3.5" />
        </svg>
    </span>

    <input
        type="search"
        name="cari"
        value="{{ $nilai }}"
        placeholder="{{ $placeholder }}"
        aria-label="{{ $placeholder }}"
        class="block w-full pl-9 pr-20 py-2 text-sm rounded-lg border-gray-200 focus:border-emerald-600 focus:ring-emerald-600"
    >

    {{-- Tombol "hapus" cuma muncul kalau memang sedang ada kata kunci aktif --}}
    @if ($nilai !== '')
        <a
            href="{{ $action }}"
            class="absolute inset-y-0 right-16 flex items-center text-gray-400 hover:text-gray-600"
            aria-label="Hapus pencarian"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M18 6 6 18M6 6l12 12" />
            </svg>
        </a>
    @endif

    <button
        type="submit"
        class="absolute inset-y-1 right-1 px-3 rounded-md bg-gray-100 text-gray-600 text-xs font-medium hover:bg-gray-200 transition"
    >
        Cari
    </button>
</form>
