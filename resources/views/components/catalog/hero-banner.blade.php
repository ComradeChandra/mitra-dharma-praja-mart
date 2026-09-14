{{--
    Hero banner di atas halaman katalog. Menampilkan status periode pemesanan
    yang sedang berjalan (kalau ada), biar pengunjung langsung tahu apa masih
    bisa pesan atau belum, tanpa harus scroll ke bawah dulu.

    Props:
    - openPeriod: model OrderPeriod yang statusnya "open", atau null kalau
      tidak ada periode yang sedang dibuka.
--}}
@props(['openPeriod'])

<section class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 text-white">
    {{-- Tekstur titik-titik halus di seluruh banner, biar permukaannya tidak flat
         polos — murni CSS gradient (radial-gradient sebagai dot pattern), tidak
         ada file gambar tambahan yang perlu dimuat. --}}
    <div class="absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px]"></div>

    {{-- Lingkaran dekoratif samar di background, biar tidak flat, murni CSS, tidak ada gambar/asset tambahan --}}
    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5"></div>
    <div class="absolute -right-4 top-24 h-40 w-40 rounded-full bg-white/5"></div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
        <h1 class="mt-1 text-2xl sm:text-4xl font-bold">Katalog Produk Koperasi</h1>
        <p class="mt-2 text-teal-100 max-w-xl">
            Pilih kebutuhan sehari-hari kamu di sini. Koperasi akan belanjakan barangnya
            setelah pesanan dari semua anggota/non-anggota terkumpul dalam satu periode.
        </p>

        <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm text-sm">
            @if ($openPeriod)
                <span class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
                Periode "{{ $openPeriod->label }}" dibuka sampai {{ $openPeriod->end_date->translatedFormat('d M Y') }}
            @else
                <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                Belum ada periode pemesanan yang sedang dibuka saat ini
            @endif
        </div>
    </div>
</section>
