<x-layouts.public :title="$product->name . ' — ' . config('app.name')">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <x-back-link :href="route('catalog.index')">Kembali ke Katalog</x-back-link>

        <x-card class="overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2">
                {{-- Foto produk. aspect-square supaya ruangnya sudah dipesan
                     sebelum gambarnya selesai dimuat, jadi isi di sebelahnya
                     tidak melompat. --}}
                <div class="aspect-square bg-gray-50 border-b sm:border-b-0 sm:border-r border-gray-100">
                    @if ($product->image_path)
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}"
                            alt="{{ $product->name }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <div class="h-full w-full flex items-center justify-center text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 16.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3 15 4.5-4.5L12 15l3-3 6 6" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="p-5 sm:p-6 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $product->category }}</p>
                    <h1 class="mt-1 text-xl font-semibold text-gray-900">{{ $product->name }}</h1>

                    {{-- Harga: produk fluktuatif belum punya angka pasti sampai
                         pengurus mengunci harganya saat verifikasi. --}}
                    <div class="mt-3">
                        @if ($product->is_fluctuating)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-amber-50 text-amber-700">
                                Harga fluktuatif
                                @if ($product->unit)
                                    &middot; per {{ $product->unit }}
                                @endif
                            </span>
                            <p class="mt-2 text-xs text-gray-500 leading-relaxed">
                                Harga barang ini naik-turun mengikuti pasar, jadi belum bisa dipastikan sekarang.
                                Harga finalnya diisi pengurus setelah barangnya dibelanjakan, dan ikut tertulis di invoice yang dikirim ke WhatsApp kamu.
                            </p>
                        @else
                            <p class="text-2xl font-bold text-gray-900">
                                Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                @if ($product->unit)
                                    <span class="text-sm font-normal text-gray-500">/ {{ $product->unit }}</span>
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Cuma status, TANPA angka stok. Angka stok yang sebenarnya
                         hanya boleh muncul di dashboard admin (lihat CLAUDE.md
                         dan Product::isAvailable()). --}}
                    <div class="mt-4">
                        @if ($product->isAvailable())
                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700">
                                <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                                Tersedia
                            </p>
                        @else
                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-400">
                                <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                                Tidak tersedia
                            </p>
                        @endif
                    </div>

                    {{-- Pengingat bahwa ini pemesanan, bukan belanja yang selesai
                         saat itu juga — supaya tidak ada yang menunggu barangnya
                         langsung dikirim. --}}
                    <div class="mt-5 p-3 rounded-lg bg-gray-50 text-xs text-gray-600 leading-relaxed">
                        Ini sistem pemesanan, bukan belanja langsung. Barang dibelanjakan koperasi setelah
                        pesanan semua orang terkumpul dalam satu periode. Tagihannya menyusul lewat WhatsApp.
                    </div>

                    <div class="mt-auto pt-5">
                        <x-catalog.order-cta />
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Produk lain di kategori yang sama, biar bisa lanjut melihat-lihat --}}
        @if ($serupa->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Lainnya di {{ $product->category }}</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($serupa as $lain)
                        <x-catalog.product-card :product="$lain" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.public>
