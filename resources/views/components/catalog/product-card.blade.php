{{--
    Kartu 1 produk di grid katalog, gaya online shop (foto besar di atas,
    info di bawah) — bukan baris tabel seperti di halaman admin.

    Tombol di bawah tergantung status login (3 kemungkinan, SUDAH FIX 24 Agt
    2026 — lihat CLAUDE.md Lampiran B soal non-anggota):
    - Anggota yang sedang login     -> link ke form pemesanan anggota.
    - Non-anggota yang sedang "login" (sesi OPD) -> link ke form pemesanan non-anggota.
    - Belum login sama sekali        -> link ke halaman login anggota (link
      "Login Non-Anggota" tersedia terpisah di header katalog).

    Semua produk dipesan sekaligus di 1 form, bukan per-kartu — lihat catatan
    "tanpa istilah keranjang" di CLAUDE.md, jadi tombol ini cuma ANTAR ke form-nya.

    Props:
    - product: model Product (harus is_active, sudah difilter di controller)
--}}
@props(['product'])

<div class="group bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col hover:shadow-lg hover:border-gray-200 hover:-translate-y-0.5 transition duration-200">
    {{-- Foto produk, atau kotak abu-abu placeholder kalau belum ada foto.
         Sedikit zoom pas di-hover (group-hover) biar terasa hidup kayak online shop. --}}
    {{-- Foto dan nama mengantar ke halaman detail produk. Tombol "Pesan" di
         bawah TIDAK ikut, karena tujuannya beda: yang satu melihat-lihat,
         yang satu langsung ke form pemesanan. --}}
    <a href="{{ route('catalog.show', $product) }}" class="relative aspect-square bg-gray-50 overflow-hidden block">
        @if ($product->image_path)
            <img
                src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}"
                alt="{{ $product->name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
            >
        @else
            {{--
                Produk tanpa foto: monogram huruf pertama di atas rona kategori.
                Warnanya diambil dari nama kategori lewat crc32,
                jadi tiap kategori dapat rona sendiri yang konsisten. Tanpa ini,
                katalog yang produknya belum berfoto tampil sebagai deretan
                kotak abu-abu identik dan terkesan rusak.
            --}}
            @php
                $rona = [
                    ['bg-emerald-50', 'text-emerald-300'],
                    ['bg-amber-50', 'text-amber-300'],
                    ['bg-sky-50', 'text-sky-300'],
                    ['bg-rose-50', 'text-rose-300'],
                    ['bg-violet-50', 'text-violet-300'],
                    ['bg-lime-50', 'text-lime-300'],
                ];
                [$latar, $tinta] = $rona[crc32($product->category) % count($rona)];
            @endphp

            {{-- Tulisan "Belum ada foto" sengaja dihapus: di produk jadi itu terbaca
                 seperti halaman yang belum selesai. --}}
            <div class="w-full h-full flex items-center justify-center {{ $latar }}" aria-hidden="true">
                <span class="text-5xl font-semibold tracking-tight {{ $tinta }}">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($product->name, 0, 1)) }}
                </span>
            </div>
        @endif

        {{-- Badge kategori, ditumpuk di pojok kiri-atas foto (pola umum online shop) --}}
        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[11px] font-medium bg-white/90 backdrop-blur-sm text-gray-600 shadow-sm">
            {{ $product->category }}
        </span>
    </a>

    <div class="p-3.5 flex flex-col flex-1">
        <h3 class="font-medium text-sm text-gray-900 line-clamp-2 min-h-[2.5rem]">
            <a href="{{ route('catalog.show', $product) }}" class="hover:text-emerald-700 transition">{{ $product->name }}</a>
        </h3>

        {{-- Harga: badge "Fluktuatif" kalau harganya belum pasti, atau angka Rupiah besar --}}
        <div class="mt-1.5">
            @if ($product->is_fluctuating)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-amber-50 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 0 1 4-4 5 5 0 0 1 9.9-1A4.5 4.5 0 0 1 18.5 19H6a3 3 0 0 1-3-4Z" />
                    </svg>
                    Harga fluktuatif
                </span>
            @else
                <p class="text-lg font-bold text-gray-900">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</p>
            @endif
        </div>

        {{--
            Cuma status tersedia atau tidak, tanpa angka stok. Angka stoknya
            sendiri hanya boleh muncul di dashboard admin, lihat CLAUDE.md dan
            Product::isAvailable().
        --}}
        @if ($product->isAvailable())
            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-teal-700">
                <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                Tersedia
            </p>
        @else
            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                Tidak tersedia
            </p>
        @endif

        <div class="mt-3 pt-3 border-t border-gray-50">
            <x-catalog.order-cta />
        </div>
    </div>
</div>
