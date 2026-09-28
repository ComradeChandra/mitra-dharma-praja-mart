{{--
    Isi <head> yang sama di semua layout: ikon, deskripsi, warna bilah browser
    di HP, dan pratinjau tautan (Open Graph).

    KENAPA ADA: tautan aplikasi ini disebar lewat WhatsApp. Tanpa Open Graph,
    pratinjaunya di WhatsApp cuma alamat polos tanpa judul maupun logo.

    Sebelumnya lima layout menulis ikonnya sendiri-sendiri dengan alamat
    "/favicon.svg", yang rusak kalau aplikasi dipasang di subfolder. asset()
    selalu menunjuk ke tempat yang benar.

    Props:
    - judul : judul halaman, dipakai sebagai judul pratinjau tautan
--}}
@props(['judul' => null])

@php
    $deskripsi = 'Pesan kebutuhan harian lewat Koperasi Mitra Dharma Praja: pilih barang dan jumlahnya, koperasi yang membelanjakan. Kebersamaan untuk Kesejahteraan.';

    // Logo dipakai sebagai gambar pratinjau & ikon layar utama iPhone.
    $logo = collect(['png', 'jpg', 'jpeg', 'webp'])
        ->map(fn (string $ekstensi) => 'images/logo-koperasi.'.$ekstensi)
        ->first(fn (string $path) => file_exists(public_path($path)));
@endphp

<meta name="description" content="{{ $deskripsi }}">
{{-- Warna bilah alamat browser di HP, sama dengan ujung kiri header --}}
<meta name="theme-color" content="#10b981">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@if ($logo)
    <link rel="apple-touch-icon" href="{{ asset($logo) }}">
@endif

<meta property="og:type" content="website">
<meta property="og:locale" content="id_ID">
<meta property="og:site_name" content="Mitra Dharma Praja Mart">
<meta property="og:title" content="{{ $judul ?? 'Mitra Dharma Praja Mart' }}">
<meta property="og:description" content="{{ $deskripsi }}">
<meta property="og:url" content="{{ url()->current() }}">
@if ($logo)
    <meta property="og:image" content="{{ asset($logo) }}">
@endif
