{{--
    Form usulan produk baru dari non-anggota.

    Beda dari versi anggota: ada kolom nama pemohon, karena non-anggota masuk
    pakai kode akses OPD yang dipakai bersama — tanpa nama, pengurus tidak
    tahu usulan itu dari siapa. Tidak ada halaman riwayat usulan, alasannya
    sama dengan pesanan non-anggota (lihat NonMember\ProductRequestController).
--}}
<x-layouts.non-member :opd="$opd" :title="'Usulkan Produk — ' . config('app.name')">

    <div class="py-10">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-800">Usulkan Produk</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Tidak menemukan barang yang kamu cari di katalog? Usulkan ke pengurus koperasi di sini.
                </p>
            </div>

            <x-alert type="success" :message="session('success')" />

            <form
                method="POST"
                action="{{ route('non-member.product-requests.store') }}"
                class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                @csrf

                <div>
                    <x-input-label for="requester_name" value="Nama Kamu" />
                    <x-text-input
                        id="requester_name"
                        name="requester_name"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Contoh: Teti Suryani"
                        :value="old('requester_name')"
                        required
                        autofocus
                    />
                    <p class="mt-1 text-xs text-gray-400">
                        Tercatat sebagai pengusul dari {{ $opd->name }}.
                    </p>
                    <x-input-error :messages="$errors->get('requester_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="product_name" value="Produk yang Diusulkan" />
                    <x-text-input
                        id="product_name"
                        name="product_name"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Contoh: Sabun cuci piring Sunlight 750ml"
                        :value="old('product_name')"
                        required
                    />
                    <p class="mt-1 text-xs text-gray-400">
                        Tulis selengkap mungkin (merek & ukuran) biar pengurus gampang mencarinya.
                    </p>
                    <x-input-error :messages="$errors->get('product_name')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>Kirim Usulan</x-primary-button>
                    <a href="{{ route('non-member.orders.create') }}" class="text-sm text-gray-500 hover:text-gray-700">
                        Kembali ke Pemesanan
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-layouts.non-member>
