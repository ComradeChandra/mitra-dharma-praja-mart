<x-layouts.member :title="'Pesan Produk — ' . config('app.name')">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-4">
            <h1 class="font-semibold text-gray-800 text-lg">Pesan Produk</h1>
            <p class="text-sm text-gray-400">Isi jumlah produk yang mau kamu pesan, lalu kirim sekaligus.</p>
        </div>

        <x-alert type="success" :message="session('success')" />
        <x-alert type="error" :message="session('error')" />

        @if (! $period)
            {{-- Belum ada periode pemesanan yang dibuka admin --}}
            <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm">
                <x-admin.empty-state
                    title="Belum ada periode pemesanan yang dibuka"
                    description="Coba cek lagi nanti — admin akan buka periode pemesanan berikutnya."
                />
            </div>
        @else
            {{-- Info periode yang sedang berjalan --}}
            <div class="flex items-center gap-2 mb-4 px-4 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 text-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Periode "{{ $period->label }}" dibuka sampai {{ $period->end_date->format('d M Y') }}
            </div>

            <form method="POST" action="{{ route('member.orders.store') }}">
                @csrf

                <x-input-error :messages="$errors->get('quantity')" class="mb-4" />

                <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    @forelse ($productsByCategory as $category => $products)
                        <div class="px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            {{ $category }}
                        </div>
                        @foreach ($products as $product)
                            <x-order.product-row :product="$product" :last="$loop->last" />
                        @endforeach
                    @empty
                        <x-admin.empty-state
                            title="Belum ada produk tersedia"
                            description="Admin belum menambahkan produk ke katalog."
                        />
                    @endforelse
                </div>

                @if ($productsByCategory->isNotEmpty())
                    {{-- Alamatnya terisi otomatis dari data anggota, tapi tetap bisa diubah --}}
                    <div class="mt-6">
                        <x-order.delivery-picker :alamat-tersimpan="$alamatTersimpan" />
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Kirim Pesanan</x-primary-button>
                        <a href="{{ route('member.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
                    </div>
                @endif
            </form>
        @endif
    </div>
</x-layouts.member>
