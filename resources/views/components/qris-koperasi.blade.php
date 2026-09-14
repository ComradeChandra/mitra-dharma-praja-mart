{{--
    Gambar QRIS koperasi. Pola pemasangannya sama dengan logo, supaya
    berkasnya bisa diganti tanpa menyentuh kode.

    CARA MEMASANG: simpan berkasnya di `public/images/` dengan nama
    `qris-koperasi.png` (boleh juga .jpg / .jpeg / .webp). Begitu berkasnya
    ada, otomatis dipakai.

    Kalau berkasnya belum ada, yang tampil keterangan bahwa QRIS-nya belum
    dipasang, BUKAN gambar rusak atau kotak kosong. Ini penting: pemesan harus
    tahu harus bayar ke mana, dan pengurus harus tahu ada yang belum disiapkan.

    SOAL HP: kebanyakan anggota membuka aplikasi dari HP, dan HP tidak bisa
    memindai layarnya sendiri. Caranya di aplikasi bank/e-wallet adalah
    memilih gambar QRIS dari galeri, jadi disediakan tombol simpan gambar.
    Gambarnya juga bisa diketuk untuk dibuka ukuran penuh.
--}}
@php
    // Urutannya png dulu karena QRIS cetakan biasanya berupa png/jpg.
    $berkasQris = collect(['png', 'jpg', 'jpeg', 'webp'])
        ->map(fn (string $ekstensi) => 'images/qris-koperasi.'.$ekstensi)
        ->first(fn (string $path) => file_exists(public_path($path)));
@endphp

@if ($berkasQris)
    <a href="{{ asset($berkasQris) }}" target="_blank" rel="noopener" class="block">
        <img
            src="{{ asset($berkasQris) }}"
            alt="Kode QRIS Koperasi Mitra Dharma Praja"
            {{ $attributes->merge(['class' => 'mx-auto w-full max-w-xs rounded-lg border border-gray-200 bg-white']) }}
        >
    </a>

    <div class="mt-3 text-center">
        <a
            href="{{ asset($berkasQris) }}"
            download="QRIS-Koperasi-Mitra-Dharma-Praja.{{ pathinfo($berkasQris, PATHINFO_EXTENSION) }}"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0-4-4m4 4 4-4M5 19h14" />
            </svg>
            Simpan gambar QRIS
        </a>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'mx-auto w-full max-w-xs rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center']) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <path stroke-linecap="round" d="M14 14h3v3h-3zM19 19h2M14 21h3" />
        </svg>
        <p class="mt-2 text-sm font-medium text-gray-600">Kode QRIS belum dipasang</p>
        <p class="mt-1 text-xs text-gray-500 leading-relaxed">
            Hubungi pengurus koperasi untuk cara pembayarannya.
        </p>
    </div>
@endif
