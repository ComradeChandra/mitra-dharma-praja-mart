{{--
    Kartu pembungkus 1 grafik di dashboard admin — DRY dipakai buat 2 grafik
    (Keuntungan per Periode & Anggota Paling Sering Belanja) yang layoutnya
    sama persis (judul, subjudul, area canvas dengan tinggi tetap), biar
    tidak copy-paste markup kartu yang sama 2x.

    Props:
    - title    : judul grafik
    - subtitle : penjelas singkat apa arti batang/garisnya
    - chartId  : id yang sama persis dengan id <canvas> di dalam slot — dipakai
                 buat bikin id kotak legend di bawahnya ("{chartId}-legend").
                 Legend-nya SENGAJA dibikin manual pakai HTML biasa (bukan
                 legend bawaan Chart.js yang digambar di dalam <canvas>),
                 soalnya elemen yang digambar di canvas itu piksel gambar,
                 bukan elemen HTML — nggak bisa dikasih animasi CSS pop-in.
                 Diisi dari JS, lihat resources/js/dashboard-charts.js.
--}}
@props(['title', 'subtitle' => null, 'chartId'])

<div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-semibold text-gray-800 text-sm">{{ $title }}</h3>
    @if ($subtitle)
        <p class="text-xs text-gray-400 mb-3">{{ $subtitle }}</p>
    @endif

    {{-- Tinggi tetap (bukan auto). Chart.js butuh parent dengan tinggi pasti
         supaya 'maintainAspectRatio: false' bisa kerja dengan benar. --}}
    <div class="h-48">
        {{ $slot }}
    </div>

    {{-- Kotak legend manual, diisi JS begitu grafiknya selesai digambar --}}
    <div id="{{ $chartId }}-legend" class="flex flex-wrap items-center justify-center gap-2 mt-3"></div>
</div>
