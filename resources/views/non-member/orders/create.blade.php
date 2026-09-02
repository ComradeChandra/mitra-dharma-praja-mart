<x-layouts.non-member :opd="$opd" :title="'Pesan Produk — ' . config('app.name')">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-4">
            <h1 class="font-semibold text-gray-800 text-lg">Pesan Produk</h1>
            <p class="text-sm text-gray-400">Isi jumlah produk yang mau dipesan, lalu kirim sekaligus.</p>
        </div>

        <x-alert type="success" :message="session('success')" />
        <x-alert type="error" :message="session('error')" />

        @if (! $period)
            {{-- Belum ada periode pemesanan yang dibuka admin --}}
            <x-card>
                <x-admin.empty-state
                    title="Belum ada periode pemesanan yang dibuka"
                    description="Coba cek lagi nanti — admin akan buka periode pemesanan berikutnya."
                />
            </x-card>
        @else
            {{-- Info periode yang sedang berjalan --}}
            <div class="flex items-center gap-2 mb-4 px-4 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 text-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Periode "{{ $period->label }}" dibuka sampai {{ $period->end_date->format('d M Y') }}
            </div>

            <form method="POST" action="{{ route('non-member.orders.store') }}">
                @csrf

                {{-- Non-anggota tidak punya akun personal, jadi nama & nomor WA
                     diketik manual di sini (beda dari anggota yang datanya sudah
                     ada di akun) — dipakai admin buat kirim invoice nanti. --}}
                <x-card class="p-5 mb-4 space-y-4">
                    <div>
                        <x-input-label for="non_member_name" value="Nama Kamu" />
                        <x-text-input
                            id="non_member_name"
                            name="non_member_name"
                            type="text"
                            class="block mt-1 w-full"
                            placeholder="Nama lengkap"
                            :value="old('non_member_name')"
                            required
                            autofocus
                        />
                        <x-input-error :messages="$errors->get('non_member_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="whatsapp_number" value="Nomor WhatsApp" />
                        <x-text-input
                            id="whatsapp_number"
                            name="whatsapp_number"
                            type="text"
                            class="block mt-1 w-full"
                            placeholder="Contoh: 6281234567890"
                            :value="old('whatsapp_number')"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-400">Buat kirim invoice belanja kamu nanti.</p>
                        <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
                    </div>
                </x-card>

                <x-input-error :messages="$errors->get('quantity')" class="mb-4" />

                <x-card class="overflow-hidden">
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
                </x-card>

                @if ($productsByCategory->isNotEmpty())
                    {{-- Non-anggota tidak punya alamat tersimpan, jadi kolomnya kosong --}}
                    <div class="mt-6">
                        <x-order.delivery-picker />
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-primary-button>Kirim Pesanan</x-primary-button>
                        <a href="{{ route('catalog.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
                    </div>
                @endif
            </form>
        @endif
    </div>
</x-layouts.non-member>
