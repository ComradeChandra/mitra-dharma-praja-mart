{{--
    Partial form produk, dipakai bareng create.blade.php & edit.blade.php.
    $product dikirim dari edit.blade.php buat isi value lama.

    Disusun per section (Foto, Info Dasar, Harga, Stok, Status) dikasih judul
    kecil + garis pemisah, biar form panjang ini enak dibaca — bukan tumpukan
    field polos dari atas ke bawah.

    Pakai Alpine.js (x-data) buat show/hide field "Harga Jual" & "Stok" secara
    interaktif tanpa reload halaman, sesuai aturan CSS/JS di CLAUDE.md.
--}}
<div
    x-data="{
        isFluctuating: {{ old('is_fluctuating', $product->is_fluctuating ?? false) ? 'true' : 'false' }},
        hasStockTracking: {{ old('has_stock_tracking', $product->has_stock_tracking ?? false) ? 'true' : 'false' }},
    }"
    class="space-y-6"
>
    {{-- Section: Foto --}}
    <div class="pb-6 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Foto Produk</h3>

        @if (isset($product) && $product->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-20 w-20 object-cover rounded-lg border border-gray-200 mb-3">
        @endif

        <x-file-input name="image" accept="image/jpeg,image/png" />
        <p class="mt-1.5 text-xs text-gray-400">Opsional — JPG/PNG. Foto yang besar otomatis diperkecil.</p>
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    {{-- Section: Informasi Dasar --}}
    <div class="pb-6 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Informasi Dasar</h3>

        {{-- Ditumpuk ke bawah di HP, kolom "Nama Produk" kalau cuma ~160px
             terlalu sempit buat mengetik nama barang yang panjang. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="category" value="Kategori" />
                {{-- list="daftar-kategori": kotak isian biasa yang menampilkan
                     kategori yang sudah ada sebagai saran, tapi tetap bisa
                     diketik kategori baru. Isiannya juga dirapikan di server
                     (Concerns\RapikanKategori) supaya "sembako" ikut masuk
                     kelompok "Sembako" di katalog. --}}
                <x-text-input
                    id="category"
                    name="category"
                    type="text"
                    list="daftar-kategori"
                    autocomplete="off"
                    class="block mt-1 w-full"
                    placeholder="Contoh: Sembako"
                    :value="old('category', $product->category ?? '')"
                    required
                />
                <datalist id="daftar-kategori">
                    @foreach ($daftarKategori ?? [] as $namaKategori)
                        <option value="{{ $namaKategori }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1.5 text-xs text-gray-400">Pilih dari daftar, atau ketik kategori baru.</p>
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="name" value="Nama Produk" />
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="block mt-1 w-full"
                    :value="old('name', $product->name ?? '')"
                    required
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
        </div>
    </div>

    {{-- Section: Harga --}}
    <div class="pb-6 border-b border-gray-100 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">Harga</h3>

        <div>
            <x-input-label for="buy_price" value="Harga Beli (Rp)" />
            <x-text-input
                id="buy_price"
                name="buy_price"
                type="number"
                step="0.01"
                min="0"
                class="block mt-1 w-full"
                :value="old('buy_price', $product->buy_price ?? '')"
                required
            />
            <x-input-error :messages="$errors->get('buy_price')" class="mt-2" />
        </div>

        {{-- Toggle produk fluktuatif: kalau aktif, field harga jual disembunyikan
             karena harganya baru dikunci admin nanti saat verifikasi pesanan --}}
        <x-toggle
            name="is_fluctuating"
            label="Harga fluktuatif (mis. telur, sayur) — harga jual dikunci nanti saat verifikasi"
            x-model="isFluctuating"
            :checked="old('is_fluctuating', $product->is_fluctuating ?? false)"
        />

        {{-- x-transition = efek "pop" muncul/hilang, sama kayak dropdown akun
             di navbar (lihat components/dropdown.blade.php) — biar field ini
             nggak keluar/masuk mendadak pas toggle "Harga fluktuatif" diklik. --}}
        <div
            x-show="! isFluctuating"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <x-input-label for="sell_price" value="Harga Jual (Rp)" />
            <x-text-input
                id="sell_price"
                name="sell_price"
                type="number"
                step="0.01"
                min="0"
                class="block mt-1 w-full"
                :value="old('sell_price', $product->sell_price ?? '')"
            />
            <x-input-error :messages="$errors->get('sell_price')" class="mt-2" />
        </div>
    </div>

    {{-- Section: Stok --}}
    <div class="pb-6 border-b border-gray-100 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">Stok</h3>

        {{-- Toggle pelacakan stok: stok bersifat opsional per produk (bukan wajib
             e-commerce), jadi field jumlah stok cuma muncul kalau ini aktif --}}
        <x-toggle
            name="has_stock_tracking"
            label="Lacak jumlah stok untuk produk ini"
            x-model="hasStockTracking"
            :checked="old('has_stock_tracking', $product->has_stock_tracking ?? false)"
        />

        <div
            x-show="hasStockTracking"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <x-input-label for="stock" value="Jumlah Stok" />
            <x-text-input
                id="stock"
                name="stock"
                type="number"
                min="0"
                class="block mt-1 w-full sm:w-1/2"
                :value="old('stock', $product->stock ?? '')"
            />
            <x-input-error :messages="$errors->get('stock')" class="mt-2" />
        </div>
    </div>

    {{-- Section: Status --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Status</h3>
        <x-toggle
            name="is_active"
            label="Produk aktif (tampil di katalog)"
            :checked="old('is_active', $product->is_active ?? true)"
        />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>Simpan</x-primary-button>
        <a href="{{ route('admin.products.index') }}">
            <x-secondary-button type="button">Batal</x-secondary-button>
        </a>
    </div>
</div>
