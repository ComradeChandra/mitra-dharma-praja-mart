<x-layouts.member :title="'Ajukan Permintaan Produk — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('member.product-requests.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            ← Kembali ke Riwayat Permintaan
        </a>

        <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-6">
            <h1 class="font-semibold text-gray-800 mb-1">Ajukan Permintaan Produk</h1>
            <p class="text-sm text-gray-400 mb-6">
                Nggak nemu produk yang kamu cari di katalog? Kasih tau admin di sini, nanti
                ditinjau dan bisa ditambahkan ke katalog kalau disetujui.
            </p>

            <form method="POST" action="{{ route('member.product-requests.store') }}">
                @csrf

                <div>
                    <x-input-label for="product_name" value="Nama Produk yang Diminta" />
                    <x-text-input
                        id="product_name"
                        name="product_name"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Contoh: Sabun Cuci Piring Merek X"
                        :value="old('product_name')"
                        required
                        autofocus
                    />
                    <x-input-error :messages="$errors->get('product_name')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <x-primary-button>Kirim Permintaan</x-primary-button>
                    <a href="{{ route('member.product-requests.index') }}">
                        <x-secondary-button type="button">Batal</x-secondary-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.member>
