{{--
    Bilah pencarian + tab kategori di form pemesanan. Menempel di atas saat
    halaman digulir, jadi penyaringnya tetap terjangkau walau daftar produknya
    panjang.

    Diberi x-cloak: tanpa JavaScript, penyaringnya memang tidak berfungsi, dan
    kotak pencarian yang tidak melakukan apa-apa lebih membingungkan daripada
    tidak ada sama sekali. Daftar produknya sendiri tetap tampil utuh.

    Props:
    - categories : daftar nama kategori yang ada di halaman ini
--}}
@props(['categories'])

<div x-cloak class="sticky top-16 z-20 -mx-4 px-4 sm:mx-0 sm:px-0 py-3 bg-gray-50/95 backdrop-blur-sm">
    {{-- Kotak cari --}}
    <div class="relative">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7" />
                <path stroke-linecap="round" d="m20 20-3.5-3.5" />
            </svg>
        </span>

        <input
            type="search"
            x-model="cari"
            placeholder="Cari produk, mis. beras"
            aria-label="Cari produk"
            class="block w-full pl-9 pr-9 py-2 text-sm rounded-lg border-gray-200 bg-white focus:border-emerald-500 focus:ring-emerald-500"
        >

        {{-- Tombol bersihkan, cuma muncul kalau kotaknya ada isinya --}}
        <button
            type="button"
            x-show="cari !== ''"
            @click="cari = ''"
            aria-label="Bersihkan pencarian"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Tab kategori, digulir menyamping di layar sempit --}}
    <div class="mt-2.5 flex gap-1.5 overflow-x-auto pb-0.5 -mx-1 px-1">
        <button
            type="button"
            @click="kategori = 'semua'"
            :class="kategori === 'semua' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
            class="shrink-0 px-3 py-1 text-xs font-medium rounded-full border transition"
        >
            Semua
        </button>

        @foreach ($categories as $kategori)
            <button
                type="button"
                @click="kategori = {{ Illuminate\Support\Js::from($kategori) }}"
                :class="kategori === {{ Illuminate\Support\Js::from($kategori) }} ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                class="shrink-0 px-3 py-1 text-xs font-medium rounded-full border transition whitespace-nowrap"
            >
                {{ $kategori }}
            </button>
        @endforeach
    </div>

    {{-- Saringan "yang sudah diisi saja", buat memeriksa ulang sebelum kirim --}}
    <div class="mt-2 flex items-center justify-between gap-3">
        <label class="inline-flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
            <input type="checkbox" x-model="hanyaDipilih" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            Tampilkan yang sudah saya isi saja
            <span x-show="banyakDipilih > 0" class="text-gray-400">(<span x-text="banyakDipilih"></span>)</span>
        </label>

        <button
            type="button"
            x-show="sedangMenyaring"
            @click="aturUlang()"
            class="text-xs text-gray-400 hover:text-gray-600 underline shrink-0"
        >
            Atur ulang
        </button>
    </div>
</div>
