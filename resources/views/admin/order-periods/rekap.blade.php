@php
    // Ringkasan kecil buat header tab "Status Anggota", dihitung di sini
    // (bukan di Service) karena ini murni turunan tampilan dari data yang
    // sudah dikirim controller, bukan query baru ke database.
    $anggotaSudah = $memberStatus->where('sudahPesan', true);
    $anggotaBelum = $memberStatus->where('sudahPesan', false);
@endphp

<x-app-layout :title="'Rekap Periode — ' . config('app.name')">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-page-heading>{{ __('Rekap Periode') }}</x-page-heading>
                <p class="text-sm text-gray-400">{{ $orderPeriod->label }}</p>
            </div>
            <a href="{{ route('admin.order-periods.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-10">
        {{-- 3 jenis rekap (Modul 6 CLAUDE.md) dijadikan tab, biar admin tidak
             perlu buka-tutup halaman berbeda pas lagi siap-siap belanja. --}}
        {{-- Tab awal bisa ditentukan lewat query string (?tab=anggota), dipakai
             kartu progres belanja di dashboard biar langsung mendarat di tab
             yang relevan. --}}
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ tab: '{{ in_array(request('tab'), ['produk', 'opd', 'anggota'], true) ? request('tab') : 'produk' }}' }">

            {{--
                Kop lembar cetak. Cuma muncul di kertas (class print-only) —
                di layar sudah ada judul halaman, kalau ditampilkan juga malah
                dobel. Yang tercetak adalah tab yang sedang dibuka, karena tab
                lain memang disembunyikan Alpine.
            --}}
            <div class="print-only mb-4 pb-3 border-b-2 border-gray-800">
                <p class="font-bold">Koperasi Mitra Dharma Praja</p>
                <p class="text-sm">Rekap Periode: {{ $orderPeriod->label }}</p>
                <p class="text-sm">
                    Rentang: {{ $orderPeriod->start_date->translatedFormat('d F Y') }}
                    &ndash; {{ $orderPeriod->end_date->translatedFormat('d F Y') }}
                </p>
                <p class="text-xs text-gray-600 mt-1">Dicetak {{ now()->translatedFormat('d F Y, H:i') }}</p>
            </div>

            {{-- Tab switcher. Diberi no-print karena tombolnya tidak ada gunanya
                 di kertas, dan daftar belanjanya jadi lebih lega. --}}
            <div class="no-print flex items-center gap-1 flex-wrap">
                @foreach ([
                    'produk' => 'Belanja Grosir',
                    'opd' => 'Distribusi per OPD',
                    'anggota' => 'Status Anggota',
                ] as $key => $label)
                    <button
                        type="button"
                        x-on:click="tab = '{{ $key }}'"
                        x-bind:class="tab === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-100'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                    >
                        {{ $label }}
                    </button>
                @endforeach

                {{--
                    Daftar belanja ini dibawa ke grosir, jadi perlu bisa
                    dicetak. Tombolnya memanggil dialog cetak bawaan browser,
                    yang juga punya pilihan "Save as PDF".
                --}}
                <button
                    type="button"
                    onclick="window.print()"
                    class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H4v-6h16v6h-2M8 14h8v6H8z" />
                    </svg>
                    Cetak
                </button>
            </div>

            {{-- ========== TAB 1: Belanja grosir per produk ========== --}}
            <div x-show="tab === 'produk'" x-cloak class="space-y-4">
                <div class="rounded-lg bg-teal-50 border border-teal-100 px-4 py-3 text-sm text-teal-800">
                    Total kebutuhan tiap produk dari SEMUA pesanan terverifikasi di periode ini
                    (anggota + non-anggota) — ini yang dipakai buat belanja ke grosir.
                    Klik satu baris buat lihat siapa saja yang memesannya.
                </div>

                <x-card class="overflow-hidden">
                    @if ($products->isNotEmpty())
                        <div class="divide-y divide-gray-100">
                            @foreach ($products as $produk)
                                {{-- Tiap baris bisa dibuka buat lihat rincian pemesannya
                                     (jawaban pertanyaan "yang beli beras itu siapa aja?"). --}}
                                <div x-data="{ open: false }">
                                    <button
                                        type="button"
                                        x-on:click="open = ! open"
                                        class="w-full flex items-center justify-between gap-4 px-5 py-3.5 text-left hover:bg-gray-50 transition"
                                    >
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-800">{{ $produk['nama'] }}</p>
                                            <p class="text-xs text-gray-400">
                                                {{ $produk['kategori'] }} · {{ $produk['pemesan']->count() }} pemesan
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="text-sm font-semibold text-gray-800">{{ $produk['jumlahDibutuhkan'] }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform"
                                                 x-bind:class="open ? 'rotate-180' : ''"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </button>

                                    <div x-show="open" x-cloak class="bg-gray-50 border-t border-gray-100 px-5 py-3">
                                        <ul class="space-y-1.5">
                                            @foreach ($produk['pemesan'] as $pemesan)
                                                <li class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-700">
                                                        {{ $pemesan['nama'] }}
                                                        <span class="text-xs text-gray-400">· {{ $pemesan['asal'] }}</span>
                                                    </span>
                                                    <span class="text-gray-500 font-medium">{{ $pemesan['jumlah'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-admin.empty-state
                            title="Belum ada yang bisa direkap"
                            description="Rekap muncul begitu ada pesanan terverifikasi di periode ini."
                        />
                    @endif
                </x-card>
            </div>

            {{-- ========== TAB 2: Distribusi per OPD ========== --}}
            <div x-show="tab === 'opd'" x-cloak class="space-y-4">
                <div class="rounded-lg bg-orange-50 border border-orange-100 px-4 py-3 text-sm text-orange-800">
                    Pesanan non-anggota dikelompokkan per instansi — dipakai buat tau
                    barang diantar ke OPD mana saja dan siapa penerimanya.
                </div>

                @if ($opdRecap->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($opdRecap as $opd)
                            <x-card class="overflow-hidden">
                                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $opd['nama'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $opd['jumlahPesanan'] }} pesanan</p>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">
                                        Rp{{ number_format($opd['totalBelanja'], 0, ',', '.') }}
                                    </span>
                                </div>
                                <ul class="divide-y divide-gray-50">
                                    @foreach ($opd['pemesan'] as $pemesan)
                                        <li class="flex items-center justify-between px-5 py-2.5 text-sm">
                                            <a href="{{ route('admin.orders.show', $pemesan['id']) }}"
                                               class="text-indigo-600 hover:text-indigo-800 font-medium">
                                                {{ $pemesan['nama'] }}
                                            </a>
                                            <span class="text-gray-500">
                                                Rp{{ number_format($pemesan['total'], 0, ',', '.') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-card>
                        @endforeach
                    </div>
                @else
                    <x-card class="overflow-hidden">
                        <x-admin.empty-state
                            title="Belum ada pesanan non-anggota"
                            description="Rekap per-OPD muncul begitu ada staf OPD yang memesan di periode ini."
                        />
                    </x-card>
                @endif
            </div>

            {{-- ========== TAB 3: Status belanja anggota ========== --}}
            <div x-show="tab === 'anggota'" x-cloak class="space-y-4">
                <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 text-sm text-indigo-800">
                    <span class="font-semibold">{{ $anggotaSudah->count() }} dari {{ $memberStatus->count() }}</span>
                    anggota aktif sudah kirim pesanan di periode ini. Daftar "belum" di bawah
                    bisa dipakai buat mengingatkan lewat WhatsApp.
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    {{-- Sudah belanja --}}
                    <x-card class="overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-sm">Sudah Belanja</h3>
                            <x-admin.badge color="green">{{ $anggotaSudah->count() }}</x-admin.badge>
                        </div>
                        @if ($anggotaSudah->isNotEmpty())
                            <ul class="divide-y divide-gray-50">
                                @foreach ($anggotaSudah as $baris)
                                    {{-- Anggota boleh memesan lebih dari sekali per periode,
                                         jadi SEMUA pesanannya ditautkan, bukan cuma satu. Dulu
                                         pesanan kedua dan seterusnya tidak kelihatan di sini. --}}
                                    <li class="px-5 py-2.5 text-sm flex items-start justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="text-gray-800">{{ $baris['nama'] }}</span>
                                            <span class="text-xs text-gray-400 block">
                                                {{ $baris['kode'] }}
                                                @if ($baris['pesanan']->count() > 1)
                                                    · {{ $baris['pesanan']->count() }} pesanan
                                                @endif
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-right">
                                            {{-- Kalau semua pesanannya masih menunggu harga, "Rp0"
                                                 terbaca seperti belanjanya nol, jadi ditulis apa adanya. --}}
                                            @if ($baris['totalBelanja'] > 0)
                                                <span class="block text-gray-800 tabular-nums">
                                                    Rp{{ number_format($baris['totalBelanja'], 0, ',', '.') }}
                                                </span>
                                                @if ($baris['adaHargaMenyusul'])
                                                    <span class="block text-[11px] text-amber-600">+ harga menyusul</span>
                                                @endif
                                            @else
                                                <span class="block text-xs text-amber-600">Menunggu harga</span>
                                            @endif
                                            <span class="mt-0.5 flex justify-end gap-2 text-xs">
                                                @foreach ($baris['pesanan'] as $pesanan)
                                                    <a href="{{ route('admin.orders.show', $pesanan) }}"
                                                       class="text-indigo-600 hover:text-indigo-800 font-medium">
                                                        {{ $baris['pesanan']->count() > 1 ? $pesanan->created_at->translatedFormat('d M') : 'Lihat' }} →
                                                    </a>
                                                @endforeach
                                            </span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="px-5 py-6 text-sm text-gray-400 text-center">Belum ada yang memesan.</p>
                        @endif
                    </x-card>

                    {{-- Belum belanja --}}
                    <x-card class="overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-sm">Belum Belanja</h3>
                            <x-admin.badge color="gray">{{ $anggotaBelum->count() }}</x-admin.badge>
                        </div>
                        @if ($anggotaBelum->isNotEmpty())
                            <ul class="divide-y divide-gray-50">
                                @foreach ($anggotaBelum as $baris)
                                    <li class="px-5 py-2.5 text-sm">
                                        <span class="text-gray-800">{{ $baris['nama'] }}</span>
                                        <span class="text-xs text-gray-400 block">{{ $baris['kode'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="px-5 py-6 text-sm text-gray-400 text-center">Semua anggota aktif sudah memesan.</p>
                        @endif
                    </x-card>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
