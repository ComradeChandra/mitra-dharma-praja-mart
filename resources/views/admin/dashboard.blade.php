<x-app-layout :title="'Dashboard Admin — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Dashboard Admin') }}</x-page-heading>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kartu status periode pemesanan, paling atas karena ini info
                 yang paling sering dicek admin tiap hari. Pakai gradient biar
                 beda bobot visualnya dari kartu statistik di bawahnya. --}}
            <div class="relative overflow-hidden rounded-xl p-6 text-white
                        {{ $periodeAktif ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : 'bg-gradient-to-br from-gray-500 to-gray-600' }}">
                <div class="absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px]"></div>
                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/10"></div>
                <div class="relative flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-white/80">
                            Status Periode Pemesanan
                        </p>
                        @if ($periodeAktif)
                            <p class="mt-1 text-xl font-bold">
                                "{{ $periodeAktif->label }}" sedang dibuka
                            </p>
                            <p class="text-sm text-white/80">
                                Sampai {{ $periodeAktif->end_date->translatedFormat('d M Y') }}
                            </p>
                        @else
                            <p class="mt-1 text-xl font-bold">
                                Belum ada periode yang dibuka
                            </p>
                        @endif
                    </div>
                    <a href="{{ route('admin.order-periods.index') }}"
                       class="shrink-0 inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 transition text-sm font-medium">
                        Kelola Periode →
                    </a>
                </div>
            </div>

            {{--
                ==================== REKAPITULASI: GRAFIK & PRODUK TERLARIS ====================
                Cuma menghitung pesanan yang statusnya sudah final
                (verified/invoiced) — lihat RecapService. Cakupan tipe pemesan
                beda-beda per grafik: keuntungan & produk terlaris menghitung
                SEMUA pemesan (anggota+non-anggota), "Anggota Paling Sering
                Belanja" & "Distribusi per OPD" masing-masing khusus satu tipe.
                Grafiknya SELALU ditampilkan (bukan diganti teks polos) biar
                layout dashboard konsisten sejak awal — kalau datanya belum
                ada, dikasih placeholder "Belum ada data", bukan disembunyikan.
            --}}
            {{--
                Grafik HARUS tetap kelihatan (bentuk sumbu/kotaknya) walaupun
                belum ada satupun pesanan terverifikasi — bukan diganti jadi
                teks polos. Kalau labels kosong, dikasih 1 kategori placeholder
                "Belum ada data" dengan nilai 0, jadi Chart.js tetap gambar
                kerangka grafiknya (sumbu, grid) dan label itu sendiri sudah
                menjelaskan kenapa masih kosong — tidak perlu teks terpisah.
            --}}
            @php
                $revenueKosong = empty($revenueChart['labels']);
                $topMembersKosong = empty($topMembersChart['labels']);
                $opdKosong = empty($opdChart['labels']);
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-admin.chart-card
                    title="Keuntungan per Periode"
                    subtitle="Pendapatan kotor dan modal beli per periode · garis = pendapatan bersih"
                    chartId="revenue-chart"
                >
                    {{--
                        PENTING: data-chart di sini pakai json_encode() + {{ }}
                        (BUKAN Illuminate\Support\Js::from()). Js::from() itu buat
                        ditaruh di dalam tag <script> (hasilnya kode JS
                        "JSON.parse('...')"), kalau ditaruh di atribut HTML malah
                        bikin HTML-nya kepotong gara-gara ada tanda kutip di tengah
                        nilainya. json_encode() + {{ }} justru pas: {{ }} otomatis
                        meng-HTML-escape hasil json_encode() (kutip dua jadi
                        &quot;, dst), browser yang nanti decode balik pas dibaca
                        lewat dataset.chart di JS.
                    --}}
                    <canvas id="revenue-chart" data-chart="{{ json_encode([
                        'labels' => $revenueKosong ? ['Belum ada data'] : $revenueChart['labels'],
                        'format' => 'rupiah',
                        'bars' => [
                            ['label' => 'Pendapatan Kotor', 'data' => $revenueKosong ? [0] : $revenueChart['gross'], 'color' => '#059669'],
                            ['label' => 'Modal Beli', 'data' => $revenueKosong ? [0] : $revenueChart['modal'], 'color' => '#b45309'],
                        ],
                        'line' => ['label' => 'Pendapatan Bersih', 'data' => $revenueKosong ? [0] : $revenueChart['net'], 'color' => '#2a78d6'],
                    ]) }}"></canvas>
                </x-admin.chart-card>

                <x-admin.chart-card
                    title="Anggota Paling Sering Belanja"
                    subtitle="Jumlah pesanan per anggota · sentuh batang untuk melihat total belanjanya"
                    chartId="top-members-chart"
                >
                    <canvas id="top-members-chart" data-chart="{{ json_encode([
                        'labels' => $topMembersKosong ? ['Belum ada data'] : $topMembersChart['labels'],
                        'format' => 'angka',
                        'mendatar' => true,
                        'bars' => [
                            ['label' => 'Jumlah pesanan', 'data' => $topMembersKosong ? [0] : $topMembersChart['orderCounts'], 'color' => '#059669'],
                        ],
                        // Satu sumbu saja: total belanja pindah ke tooltip, bukan garis di sumbu kedua.
                        'keterangan' => $topMembersKosong ? [] : collect($topMembersChart['totalValues'])->map(fn ($nilai) => 'Total belanja Rp'.number_format($nilai, 0, ',', '.'))->all(),
                    ]) }}"></canvas>
                </x-admin.chart-card>

                {{-- Distribusi per OPD (Modul 6, "per-OPD"). khusus non-anggota,
                     baru bisa dibangun sekarang setelah non-anggota SUDAH FIX
                     24 Agt 2026 (lihat CLAUDE.md, Lampiran B).
                     Selebar dua kolom: grafik ketiga di grid dua kolom tadinya
                     menyisakan sel kosong, dan nama OPD yang panjang jadi lega. --}}
                <x-admin.chart-card
                    class="lg:col-span-2"
                    title="Distribusi per OPD"
                    subtitle="Jumlah pesanan non-anggota per OPD · sentuh batang untuk melihat total belanjanya"
                    chartId="opd-chart"
                >
                    <canvas id="opd-chart" data-chart="{{ json_encode([
                        'labels' => $opdKosong ? ['Belum ada data'] : $opdChart['labels'],
                        'format' => 'angka',
                        'mendatar' => true,
                        'bars' => [
                            ['label' => 'Jumlah pesanan', 'data' => $opdKosong ? [0] : $opdChart['orderCounts'], 'color' => '#059669'],
                        ],
                        'keterangan' => $opdKosong ? [] : collect($opdChart['totalValues'])->map(fn ($nilai) => 'Total belanja Rp'.number_format($nilai, 0, ',', '.'))->all(),
                    ]) }}"></canvas>
                </x-admin.chart-card>
            </div>

            {{-- Produk paling laku. Dibuat tabel, bukan grafik, sesuai permintaan;
                 biar nama produknya gampang dibaca persis (bukan cuma batang). --}}
            <x-card class="overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800 text-sm">Produk Paling Laku</h3>
                    <p class="text-xs text-gray-400">Diurutkan dari jumlah terjual terbanyak, dari pesanan yang sudah terverifikasi.</p>
                </div>
                @if ($topProducts->isNotEmpty())
                    {{-- overflow-x-auto: di layar HP tabel bisa digeser, bukan terpotong --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Terjual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($topProducts as $produk)
                                    <tr>
                                        <td class="px-5 py-2.5 text-sm font-medium text-gray-800">{{ $produk['nama'] }}</td>
                                        <td class="px-5 py-2.5 text-sm text-gray-400">{{ $produk['kategori'] }}</td>
                                        <td class="px-5 py-2.5 text-sm text-gray-700 text-right font-semibold">{{ $produk['jumlahTerjual'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-admin.empty-state
                        title="Belum ada produk yang terjual"
                        description="Data ini muncul begitu ada pesanan anggota yang sudah terverifikasi."
                    />
                @endif
            </x-card>

            {{-- Layout 2 kolom: konten utama (kiri, lebih lebar) + panel ringkasan (kanan) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ==================== KOLOM UTAMA ==================== --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Ringkasan angka data master, tiap kartu klik langsung ke halaman kelolanya --}}
                    <div class="grid grid-cols-2 gap-4">
                        <x-admin.stat-card
                            label="Anggota"
                            :value="$stats['totalAnggota']"
                            :hint="$stats['anggotaAktif'] . ' aktif'"
                            :href="route('admin.members.index')"
                            color="emerald"
                        >
                            <x-slot:icon>
                                <circle cx="9" cy="7" r="3" />
                                <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" />
                                <circle cx="17" cy="8" r="2.3" />
                                <path d="M14.7 20c.25-2.35 1.7-4.25 3.65-5" />
                            </x-slot:icon>
                        </x-admin.stat-card>

                        <x-admin.stat-card
                            label="Produk Aktif"
                            :value="$stats['totalProduk']"
                            :href="route('admin.products.index')"
                            color="amber"
                        >
                            <x-slot:icon>
                                <path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z" />
                                <path d="M4 7l8 4 8-4" />
                                <path d="M12 11v10" />
                            </x-slot:icon>
                        </x-admin.stat-card>

                        <x-admin.stat-card
                            label="OPD"
                            :value="$stats['totalOpd']"
                            :href="route('admin.opd-departments.index')"
                            color="teal"
                        >
                            <x-slot:icon>
                                <rect x="5" y="3.5" width="14" height="17" rx="1.2" />
                                <path d="M9 7.5h1M14 7.5h1M9 11h1M14 11h1M9 14.5h1M14 14.5h1" />
                                <path d="M10.5 20.5v-3a1.5 1.5 0 0 1 3 0v3" />
                            </x-slot:icon>
                        </x-admin.stat-card>

                        <x-admin.stat-card
                            label="Lihat Katalog"
                            value="Buka"
                            hint="Tampilan publik"
                            :href="route('catalog.index')"
                            color="violet"
                        >
                            <x-slot:icon>
                                <path d="M14 5h5v5" />
                                <path d="M19 5 10 14" />
                                <path d="M8 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-2" />
                            </x-slot:icon>
                        </x-admin.stat-card>
                    </div>

                    {{-- Panel Anggota Terbaru --}}
                    <x-card class="overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800 text-sm">Anggota Terbaru</h3>
                            <a href="{{ route('admin.members.index') }}" class="text-xs font-medium text-emerald-700 hover:text-emerald-900">Lihat semua →</a>
                        </div>
                        @forelse ($anggotaTerbaru as $anggota)
                            <div class="flex items-center gap-3 px-5 py-3 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                                <span class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-semibold shrink-0">
                                    {{ Str::upper(Str::substr($anggota->full_name, 0, 1)) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $anggota->full_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $anggota->member_code }}</p>
                                </div>
                                <x-admin.active-badge :active="$anggota->is_active" />
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-400 text-center">Belum ada anggota diinput.</p>
                        @endforelse
                    </x-card>

                    {{-- Panel Produk Terbaru --}}
                    <x-card class="overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800 text-sm">Produk Terbaru</h3>
                            <a href="{{ route('admin.products.index') }}" class="text-xs font-medium text-emerald-700 hover:text-emerald-900">Lihat semua →</a>
                        </div>
                        @forelse ($produkTerbaru as $produk)
                            <div class="flex items-center gap-3 px-5 py-3 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                                @if ($produk->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($produk->image_path) }}" alt="{{ $produk->name }}" class="h-9 w-9 rounded-lg object-cover border border-gray-200 shrink-0">
                                @else
                                    <span class="h-9 w-9 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z" /><path d="M4 7l8 4 8-4" /><path d="M12 11v10" />
                                        </svg>
                                    </span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $produk->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $produk->category }}</p>
                                </div>
                                <span class="text-sm font-medium text-gray-700 shrink-0">
                                    {{ $produk->is_fluctuating ? 'Fluktuatif' : 'Rp' . number_format($produk->sell_price, 0, ',', '.') }}
                                </span>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-400 text-center">Belum ada produk diinput.</p>
                        @endforelse
                    </x-card>
                </div>

                {{-- ==================== KOLOM SAMPING ==================== --}}
                <div class="space-y-6">

                    {{-- Widget rasio anggota aktif/nonaktif, visual bar sederhana (CSS murni) --}}
                    <x-card class="p-5">
                        <h3 class="font-semibold text-gray-800 text-sm mb-4">Status Anggota</h3>

                        @php
                            $persenAktif = $stats['totalAnggota'] > 0
                                ? round($stats['anggotaAktif'] / $stats['totalAnggota'] * 100)
                                : 0;
                        @endphp

                        <div class="flex items-end justify-between mb-2">
                            <span class="text-2xl font-bold text-gray-900">{{ $persenAktif }}%</span>
                            <span class="text-xs text-gray-400">{{ $stats['anggotaAktif'] }} dari {{ $stats['totalAnggota'] }} aktif</span>
                        </div>
                        <div class="h-2.5 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-teal-500 to-teal-600 rounded-full transition-all" style="width: {{ $persenAktif }}%"></div>
                        </div>
                    </x-card>

                    {{--
                        Progres belanja periode berjalan. Cuma muncul
                        kalau memang ada periode yang sedang dibuka; kalau tidak, angkanya
                        tidak ada artinya. Klik buat lihat daftar namanya di halaman rekap.
                    --}}
                    @if ($periodeAktif)
                        @php
                            $persenBelanja = $progresBelanja['total'] > 0
                                ? round($progresBelanja['sudah'] / $progresBelanja['total'] * 100)
                                : 0;
                        @endphp

                        <a href="{{ route('admin.order-periods.rekap', ['orderPeriod' => $periodeAktif, 'tab' => 'anggota']) }}"
                           class="block bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition duration-150">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold text-gray-800 text-sm">Sudah Belanja</h3>
                                <span class="text-xs text-emerald-700 font-medium">Lihat daftar →</span>
                            </div>

                            <div class="flex items-end justify-between mb-2">
                                <span class="text-2xl font-bold text-gray-900">{{ $persenBelanja }}%</span>
                                <span class="text-xs text-gray-400">
                                    {{ $progresBelanja['sudah'] }} dari {{ $progresBelanja['total'] }} anggota aktif
                                </span>
                            </div>
                            <div class="h-2.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-full transition-all" style="width: {{ $persenBelanja }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-400 truncate">Periode {{ $periodeAktif->label }}</p>
                        </a>
                    @endif

                    {{-- Pembayaran yang menunggu dicocokkan ke mutasi rekening --}}
                    @if ($pembayaranMenunggu > 0)
                        <a href="{{ route('admin.orders.index', ['bayar' => App\Enums\PaymentStatus::AwaitingConfirmation->value]) }}"
                           class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 hover:bg-emerald-100 transition">
                            <div class="flex items-center gap-3">
                                <span class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
                                    {{ $pembayaranMenunggu }}
                                </span>
                                <span class="text-sm font-medium text-emerald-800">
                                    Pembayaran menunggu dicocokkan
                                </span>
                            </div>
                            <span class="text-emerald-600">&rarr;</span>
                        </a>
                    @endif

                    {{-- Permintaan produk yang belum ditinjau, ditonjolkan kalau ada --}}
                    @if ($permintaanMenunggu > 0)
                        <a href="{{ route('admin.product-requests.index') }}"
                           class="flex items-center justify-between gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 hover:bg-amber-100 transition">
                            <div class="flex items-center gap-3">
                                <span class="h-9 w-9 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm font-bold shrink-0">
                                    {{ $permintaanMenunggu }}
                                </span>
                                <span class="text-sm font-medium text-amber-800">
                                    Permintaan produk menunggu ditinjau
                                </span>
                            </div>
                            <span class="text-amber-600">→</span>
                        </a>
                    @endif

                    {{-- Aksi cepat --}}
                    <x-card class="p-5">
                        <h3 class="font-semibold text-gray-800 text-sm mb-4">Aksi Cepat</h3>
                        <div class="space-y-1.5">
                            @foreach ([
                                ['label' => 'Lihat Pesanan Masuk', 'href' => route('admin.orders.index'), 'color' => 'text-rose-600 bg-rose-50', 'icon' => '→'],
                                ['label' => 'Tambah Produk', 'href' => route('admin.products.create'), 'color' => 'text-amber-600 bg-amber-50'],
                                ['label' => 'Tambah Anggota', 'href' => route('admin.members.create'), 'color' => 'text-emerald-700 bg-emerald-50'],
                                ['label' => 'Buat Periode', 'href' => route('admin.order-periods.create'), 'color' => 'text-emerald-600 bg-emerald-50'],
                                ['label' => 'Tambah OPD', 'href' => route('admin.opd-departments.create'), 'color' => 'text-teal-600 bg-teal-50'],
                                ['label' => 'Lihat Permintaan Produk', 'href' => route('admin.product-requests.index'), 'color' => 'text-violet-600 bg-violet-50', 'icon' => '→'],
                            ] as $aksi)
                                <a href="{{ $aksi['href'] }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition group">
                                    <span class="h-7 w-7 rounded-md {{ $aksi['color'] }} flex items-center justify-center text-sm font-bold">{{ $aksi['icon'] ?? '+' }}</span>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $aksi['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-card>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
