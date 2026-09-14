{{--
    Kerangka semua halaman error (403, 404, 419, 429, 500, 503, dst).

    Halaman error bawaan Laravel (401.blade.php, 404.blade.php, dst di dalam
    framework) semuanya memakai layout bernama "errors::minimal". Berkas ini
    menggantikan layout itu, jadi SEMUA halaman error ikut berubah tanpa perlu
    menyalin satu per satu. Judul dan pesannya diterjemahkan lewat lang/id.json.

    KENAPA DIBUAT SENDIRI: bawaannya berbahasa Inggris ("Not Found",
    "Forbidden", "Page Expired"), tertulis lang="en", dan tidak ada tombol
    untuk kembali. Pemesan yang tersesat cuma melihat angka dan satu kata
    asing.

    SENGAJA BERDIRI SENDIRI: CSS-nya ditulis di sini, tidak memakai @vite atau
    komponen Blade, dan tidak menyentuh database. Halaman error bisa muncul
    justru karena bagian-bagian itu yang sedang rusak (mis. error 500), dan
    halaman error tidak boleh ikut gagal tampil.
--}}
@php
    $kode = trim($__env->yieldContent('code'));

    // Satu kalimat saran per jenis error, supaya pemesan tahu harus apa.
    $saran = match ($kode) {
        '401' => 'Silakan masuk dulu untuk membuka halaman ini.',
        '403' => 'Halaman ini bukan untuk akun yang sedang kamu pakai.',
        '404' => 'Alamatnya mungkin salah ketik, atau datanya sudah dihapus pengurus.',
        '419' => 'Halaman terlalu lama dibiarkan terbuka. Muat ulang halamannya, lalu coba lagi.',
        '429' => 'Tunggu sebentar, lalu coba lagi.',
        '500', '503' => 'Coba lagi beberapa saat lagi. Kalau terus terjadi, hubungi pengurus koperasi.',
        default => 'Kalau ini terus terjadi, hubungi pengurus koperasi.',
    };

    // Logo dipasang dengan cara yang sama seperti x-application-logo, tapi
    // ditulis langsung supaya halaman error tidak bergantung pada komponen.
    $logo = collect(['svg', 'png', 'jpg', 'jpeg', 'webp'])
        ->map(fn (string $ekstensi) => 'images/logo-koperasi.'.$ekstensi)
        ->first(fn (string $path) => file_exists(public_path($path)));
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') · Mitra Dharma Praja Mart</title>

        <style>
            *, ::before, ::after { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                /* Warna latar & aksen mengikuti aplikasinya (krem + hijau teal) */
                background: #FAF6EF;
                color: #1f2937;
                -webkit-font-smoothing: antialiased;
            }
            .kartu {
                width: 100%;
                max-width: 420px;
                background: #fff;
                border: 1px solid #eef0ee;
                border-radius: 16px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
                padding: 36px 28px 28px;
                text-align: center;
            }
            .logo { width: 64px; height: 64px; object-fit: contain; margin: 0 auto 12px; display: block; }
            .kode { font-size: 13px; font-weight: 600; letter-spacing: .12em; color: #0f766e; margin: 0; }
            h1 { font-size: 20px; line-height: 1.35; margin: 6px 0 0; text-wrap: balance; }
            .saran { font-size: 14px; line-height: 1.6; color: #6b7280; margin: 10px 0 0; }
            .aksi { display: flex; flex-direction: column; gap: 8px; margin-top: 24px; }
            .tombol {
                display: block;
                padding: 11px 16px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                border: 0;
                font-family: inherit;
            }
            .tombol-utama { background: #059669; color: #fff; }
            .tombol-utama:hover { background: #047857; }
            .tombol-kedua { background: transparent; color: #4b5563; }
            .tombol-kedua:hover { background: #f3f4f6; }
            .tombol:focus-visible { outline: 2px solid #059669; outline-offset: 2px; }
            .kaki { margin-top: 20px; font-size: 12px; color: #9ca3af; text-wrap: balance; }
        </style>
    </head>
    <body>
        <main class="kartu">
            @if ($logo)
                <img src="{{ asset($logo) }}" alt="" class="logo">
            @endif

            <p class="kode">KODE {{ $kode }}</p>
            <h1>@yield('message')</h1>
            <p class="saran">{{ $saran }}</p>

            <div class="aksi">
                <a href="{{ url('/') }}" class="tombol tombol-utama">Ke halaman utama</a>
                <button type="button" class="tombol tombol-kedua" onclick="history.back()">Kembali ke halaman sebelumnya</button>
            </div>

            <p class="kaki">Mitra Dharma Praja Mart · Koperasi Mitra Dharma Praja</p>
        </main>
    </body>
</html>
