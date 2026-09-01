{{--
    Satu baris produk di form pemesanan — dipakai bareng oleh form anggota
    (member/orders/create.blade.php) DAN form non-anggota
    (non-member/orders/create.blade.php).

    Beda dari <x-catalog.product-card> (yang gaya kartu online-shop buat
    katalog publik) — di sini gaya baris/list, karena tiap produk butuh input
    jumlah, jadi lebih enak di-scan dari atas ke bawah daripada grid kartu.

    Props:
    - product : model Product (harus is_active, sudah difilter di controller)
    - last    : boolean, true kalau ini baris terakhir di grup-nya (biar garis
                pembatas bawah tidak dobel sama border kartu pembungkus).
                Dikirim eksplisit dari parent (bukan pakai $loop->last) —
                lebih pasti benar nilainya di semua context pemanggilan.
--}}
@props(['product', 'last' => false])

@php
    // Produk yang tidak tersedia tetap ditampilkan (biar pemesan tau barangnya
    // ada di katalog koperasi), tapi input jumlahnya dimatikan, kalau
    // labelnya bilang "Tidak tersedia" tapi masih bisa diisi jumlah, itu
    // membingungkan dan bikin pesanan yang tidak bisa dipenuhi.
    $bisaDipesan = $product->isAvailable();
@endphp

<div class="flex items-center gap-4 px-4 py-3.5 {{ ! $last ? 'border-b border-gray-50' : '' }} {{ ! $bisaDipesan ? 'opacity-60' : '' }}">
    {{-- Thumbnail foto, atau kotak abu-abu placeholder kalau belum ada foto --}}
    @if ($product->image_path)
        <img
            src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}"
            alt="{{ $product->name }}"
            class="h-12 w-12 rounded-lg object-cover border border-gray-100 shrink-0"
        >
    @else
        <span class="h-12 w-12 rounded-lg bg-amber-50 text-amber-400 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z" /><path d="M4 7l8 4 8-4" /><path d="M12 11v10" />
            </svg>
        </span>
    @endif

    {{-- Nama, kategori, harga/badge fluktuatif, status ketersediaan --}}
    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</p>
        <p class="text-xs text-gray-400">{{ $product->category }}</p>

        @if ($product->is_fluctuating)
            <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-[11px] font-medium rounded-full bg-amber-50 text-amber-700">
                Harga final saat verifikasi admin
            </span>
        @else
            <p class="mt-1 text-sm font-semibold text-gray-900">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</p>
        @endif

        {{--
            SENGAJA cuma status, BUKAN angka stok — angka stok yang sebenarnya
            cuma boleh muncul di dashboard admin (lihat CLAUDE.md +
            Product::isAvailable()). Alasannya: ini sistem pre-order, barang
            sering belum ada di gudang saat dipesan, jadi angka stok cuma
            bikin bingung pemesan.
        --}}
        @if (! $bisaDipesan)
            <p class="mt-0.5 inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                Tidak tersedia
            </p>
        @endif
    </div>

    {{-- Input jumlah, nama field "quantity[{id}]", dibaca StoreOrderRequest::orderedItems() --}}
    <div class="shrink-0">
        <label for="quantity-{{ $product->id }}" class="sr-only">Jumlah {{ $product->name }}</label>
        <input
            type="number"
            id="quantity-{{ $product->id }}"
            name="quantity[{{ $product->id }}]"
            min="0"
            value="{{ old('quantity.'.$product->id, 0) }}"
            @disabled(! $bisaDipesan)
            class="w-20 text-center rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
        >
    </div>
</div>
