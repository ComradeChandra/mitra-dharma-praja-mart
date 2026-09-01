{{--
    Logo koperasi. Dipakai di navbar admin, navbar anggota/non-anggota, dan
    halaman login.

    CARA MENGGANTI DENGAN LOGO ASLI (tidak perlu sentuh kode ini):
    simpan berkas logonya di folder `public/images/` dengan nama
    `logo-koperasi.png` (boleh juga .svg / .jpg / .jpeg / .webp).
    Begitu berkasnya ada, logo itu otomatis dipakai di seluruh aplikasi.

    Selama berkasnya belum ada, dipakai logo sementara (ikon tas belanja)
    supaya tampilan tidak rusak / kosong.
--}}
@php
    // Cari berkas logo di public/images dengan nama "logo-koperasi.*".
    // Urutannya: svg lebih dulu (paling tajam di layar apa pun), baru format foto.
    $logoAsli = collect(['svg', 'png', 'jpg', 'jpeg', 'webp'])
        ->map(fn (string $ekstensi) => 'images/logo-koperasi.'.$ekstensi)
        ->first(fn (string $path) => file_exists(public_path($path)));
@endphp

@if ($logoAsli)
    {{-- object-contain: logo tidak gepeng/terpotong berapa pun rasio aslinya --}}
    <img
        src="{{ asset($logoAsli) }}"
        alt="Logo Koperasi Mitra Dharma Praja"
        {{ $attributes->merge(['class' => 'object-contain']) }}
    >
@else
    {{-- Logo sementara: badge membulat berisi ikon tas belanja. viewBox persegi
         (40x40) supaya proporsional berapa pun ukuran class yang dipasang. --}}
    <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
        <rect width="40" height="40" rx="10" fill="#4f46e5" />
        <g transform="translate(8,8)" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            {{-- Badan tas belanja --}}
            <path d="M2 8h20l-1.6 12.2a2 2 0 0 1-2 1.8H5.6a2 2 0 0 1-2-1.8L2 8Z" />
            {{-- Gagang tas --}}
            <path d="M7 8V6a5 5 0 0 1 10 0v2" />
        </g>
    </svg>
@endif
