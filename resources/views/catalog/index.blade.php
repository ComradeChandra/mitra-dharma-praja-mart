<x-layouts.public :title="'Katalog Produk — ' . config('app.name')">
    <x-catalog.hero-banner :open-period="$openPeriod" />

    {{--
        Kotak cari produk. Sengaja form biasa (GET, reload halaman), BUKAN
        pencarian langsung pakai JS — hasilnya bisa di-bookmark & dibagikan
        lewat tautan, dan tetap jalan di HP dengan koneksi lemot.
    --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        {{-- Di HP disusun ke bawah: kalau input, tombol Cari, dan Reset dipaksa
             sejajar di layar 375px, kotak ketiknya tinggal ~200px dan tombolnya
             kecil-kecil susah dipencet. Di layar lebar baru sejajar. --}}
        <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-col sm:flex-row gap-2">
            <label for="cari" class="sr-only">Cari produk</label>
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    id="cari"
                    name="cari"
                    type="search"
                    value="{{ $cari }}"
                    placeholder="Cari nama produk atau kategori…"
                    class="w-full pl-9 pr-3 py-2.5 rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm shadow-sm"
                >
            </div>
            <div class="flex gap-2">
                <x-primary-button class="flex-1 sm:flex-none justify-center">Cari</x-primary-button>
                @if ($cari !== '')
                    <a href="{{ route('catalog.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @if ($cari !== '' && $productsByCategory->isNotEmpty())
            <p class="mt-3 text-sm text-gray-500">
                Hasil pencarian untuk "<span class="font-medium text-gray-700">{{ $cari }}</span>"
            </p>
        @endif
    </div>

    @if ($productsByCategory->isNotEmpty())
        {{--
            Filter kategori pakai Alpine.js (x-data di wrapper, x-show tiap section) —
            murni tampilan di sisi browser, tidak perlu reload halaman/query ulang ke
            server. Sesuai aturan CSS/JS di CLAUDE.md: interaktivitas ringan pakai Alpine.
        --}}
        <div x-data="{ activeCategory: 'Semua' }">
            {{-- Bar filter kategori, nempel di atas (sticky) pas discroll --}}
            <div class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm border-b border-gray-100">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex gap-2 overflow-x-auto">
                    {{--
                        Semua tombol tab di sini dikasih animasi "pop-in" (lihat
                        @keyframes di resources/css/app.css) yang jalan sekali pas
                        halaman kebuka — animation-delay dihitung dari $loop->index
                        biar tombolnya muncul gantian berurutan, bukan barengan.
                    --}}
                    <button
                        type="button"
                        @click="activeCategory = 'Semua'"
                        :class="activeCategory === 'Semua' ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition animate-[pop-in_0.3s_ease-out_backwards]"
                    >
                        Semua
                    </button>
                    @foreach ($productsByCategory->keys() as $category)
                        <button
                            type="button"
                            @click="activeCategory = {{ \Illuminate\Support\Js::from($category) }}"
                            :class="activeCategory === {{ \Illuminate\Support\Js::from($category) }} ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition animate-[pop-in_0.3s_ease-out_backwards]"
                            style="animation-delay: {{ ($loop->index + 1) * 40 }}ms"
                        >
                            {{ $category }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                @foreach ($productsByCategory as $category => $products)
                    {{--
                        Satu section per kategori, sesuai Modul 2 (Katalog Produk per kategori)
                        di CLAUDE.md. x-transition = fade pas ganti-ganti tab kategori — sengaja
                        cuma fade (bukan scale kayak dropdown/field toggle) karena ini blok besar
                        isi grid produk, scale di elemen segede ini kelihatan janggal.
                    --}}
                    <section
                        x-show="activeCategory === 'Semua' || activeCategory === {{ \Illuminate\Support\Js::from($category) }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mb-10"
                    >
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ $category }}</h2>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach ($products as $product)
                                <x-catalog.product-card :product="$product" />
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @elseif ($cari !== '')
        {{-- Kosong KARENA pencarian, beda pesan dengan katalog yang memang
             masih kosong, biar orang tidak salah sangka koperasinya belum
             punya barang sama sekali. --}}
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <p class="text-gray-700 font-medium">Produk "{{ $cari }}" tidak ditemukan</p>
            <p class="mt-1 text-sm text-gray-500">
                Coba kata kunci lain, atau usulkan produk ini ke pengurus koperasi.
            </p>
            <a href="{{ route('catalog.index') }}"
               class="inline-block mt-4 text-sm font-medium text-emerald-700 hover:text-emerald-900">
                Lihat semua produk
            </a>
        </div>
    @else
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center text-gray-500">
            Belum ada produk yang tersedia di katalog saat ini.
        </div>
    @endif
</x-layouts.public>
