<x-layouts.member :title="'Beranda Anggota — ' . config('app.name')">
    {{-- Banner sambutan --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-teal-600 to-teal-800 text-white">
        <div class="absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px]"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <h1 class="text-2xl font-bold">Halo, {{ $member->full_name }}! 👋</h1>
            <p class="mt-1 text-teal-100">Selamat datang di portal anggota Koperasi Mitra Dharma Praja.</p>
        </div>
    </section>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ==================== KOLOM UTAMA ==================== --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Kartu profil singkat --}}
                <x-card class="p-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Profil Kamu</h2>
                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs text-gray-400">Kode Anggota</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $member->member_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Nomor WhatsApp</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $member->whatsapp_number }}</dd>
                        </div>
                    </dl>
                </x-card>

                {{--
                    Belanja tahun berjalan, dasar hitung SHU (Modul 9 di CLAUDE.md).
                    Cukup angka totalnya saja, tanpa rincian per pesanan, sesuai
                    permintaan pengurus. Estimasi SHU ditulis sebagai rentang
                    0,5%-1% karena persentase finalnya belum ditetapkan koperasi.
                --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-teal-600 to-teal-800 rounded-xl shadow-sm p-6 text-white">
                    <div class="absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px]"></div>
                    <div class="relative">
                        <p class="text-xs font-medium uppercase tracking-wide text-teal-100">Belanja Kamu Tahun {{ now()->year }}</p>
                        <p class="mt-1 text-2xl font-bold">Rp{{ number_format($belanjaTahunIni, 0, ',', '.') }}</p>
                        @if ($belanjaTahunIni > 0)
                            <p class="mt-2 text-sm text-teal-100">
                                Estimasi SHU: Rp{{ number_format($belanjaTahunIni * 0.005, 0, ',', '.') }}
                                – Rp{{ number_format($belanjaTahunIni * 0.01, 0, ',', '.') }}
                                <span class="block text-xs text-teal-200 mt-0.5">(0,5%–1% dari total belanja, persentase pasti ditentukan koperasi di akhir tahun)</span>
                            </p>
                        @else
                            <p class="mt-2 text-sm text-teal-100">Belum ada belanja terverifikasi tahun ini.</p>
                        @endif
                    </div>
                </div>

                {{-- Ajakan pesan produk. CTA utama sekarang pemesanan sudah aktif (Modul 3) --}}
                <a href="{{ route('member.orders.create') }}"
                   class="relative overflow-hidden block bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-sm p-6 text-white hover:shadow-lg transition group">
                    <div class="absolute -right-6 -bottom-6 h-28 w-28 rounded-full bg-white/10"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">Pesan Produk</h2>
                            <p class="mt-1 text-sm text-white/90">Isi jumlah produk yang mau kamu pesan bulan ini.</p>
                        </div>
                        <span class="text-xl group-hover:translate-x-1 transition">→</span>
                    </div>
                </a>

                {{-- Dua ajakan sekunder berdampingan: lihat katalog & riwayat pesanan --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('catalog.index') }}"
                       class="flex items-center justify-between gap-3 bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition group">
                        <span class="text-sm font-medium text-gray-700">Lihat Katalog Produk</span>
                        <span class="text-gray-300 group-hover:text-gray-500 group-hover:translate-x-1 transition">→</span>
                    </a>
                    <a href="{{ route('member.orders.index') }}"
                       class="flex items-center justify-between gap-3 bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition group">
                        <span class="text-sm font-medium text-gray-700">Pesanan Saya</span>
                        <span class="text-gray-300 group-hover:text-gray-500 group-hover:translate-x-1 transition">→</span>
                    </a>
                </div>

                {{-- Ajakan ajukan permintaan produk baru --}}
                <a href="{{ route('member.product-requests.index') }}"
                   class="relative overflow-hidden block bg-gradient-to-br from-teal-500 to-emerald-600 rounded-xl shadow-sm p-6 text-white hover:shadow-lg transition group">
                    <div class="absolute -right-6 -bottom-6 h-28 w-28 rounded-full bg-white/10"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">Ajukan Permintaan Produk</h2>
                            <p class="mt-1 text-sm text-white/90">Nggak nemu produk yang kamu cari? Usulkan ke pengurus di sini.</p>
                        </div>
                        <span class="text-xl group-hover:translate-x-1 transition">→</span>
                    </div>
                </a>

            </div>

            {{-- ==================== KOLOM SAMPING: Cara Kerja ==================== --}}
            <x-card class="p-6 h-fit">
                <h3 class="font-semibold text-gray-800 text-sm mb-4">Cara Kerja Koperasi</h3>
                <ol class="space-y-5">
                    @foreach ([
                        ['title' => 'Cek Katalog', 'desc' => 'Lihat produk yang tersedia bulan ini.'],
                        ['title' => 'Kirim Pesanan', 'desc' => 'Sampaikan produk & jumlah yang mau dipesan ke pengurus.'],
                        ['title' => 'Koperasi Belanja', 'desc' => 'Setelah periode ditutup, koperasi belanjakan barangnya.'],
                        ['title' => 'Terima Invoice', 'desc' => 'Invoice & rincian tagihan dikirim lewat WhatsApp.'],
                    ] as $index => $step)
                        <li class="flex gap-3">
                            <span class="shrink-0 h-6 w-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $step['title'] }}</p>
                                <p class="text-xs text-gray-400">{{ $step['desc'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>
    </div>
</x-layouts.member>
