{{--
    Isi halaman "Pesan Produk" — dipakai bareng oleh form anggota
    (member/orders/create.blade.php) dan non-anggota
    (non-member/orders/create.blade.php).

    Dulu seluruh isinya diketik ulang di kedua berkas itu, dan sekitar 60%
    baris-barisnya sama persis. Setiap perbaikan jadi harus dikerjakan dua kali,
    dan sempat kejadian satu halaman ketinggalan.

    Yang membedakan kedua peran tinggal empat hal, semuanya dioper sebagai
    prop, plus satu slot buat kolom identitas non-anggota.

    Seluruh isinya dibungkus komponen Alpine "formPesan"
    (resources/js/order-form.js). Yang diurus di sana cuma penyaringan tampilan
    dan ringkasan di bawah, bukan pengiriman datanya, jadi halaman ini tetap
    bisa dipakai memesan walau JavaScript mati.

    Props:
    - period            : periode yang sedang dibuka, atau null
    - productsByCategory: produk aktif, sudah dikelompokkan per kategori
    - action            : URL tujuan form
    - batal             : URL tombol "Batal"
    - alamatTersimpan   : alamat bawaan pemesan (anggota punya, non-anggota tidak)
    - keterangan        : kalimat pendek di bawah judul

    Slot opsional:
    - identitas : kolom tambahan sebelum daftar produk (dipakai non-anggota
                  buat mengisi nama & nomor WhatsApp)
--}}
@props([
    'period',
    'productsByCategory',
    'action',
    'batal',
    'alamatTersimpan' => null,
    'keterangan' => 'Isi jumlah produk yang mau dipesan, lalu kirim sekaligus.',
    'identitas' => null,
])

<div x-data="formPesan" class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-4">
        <h1 class="font-semibold text-gray-800 text-lg">Pesan Produk</h1>
        <p class="text-sm text-gray-400">{{ $keterangan }}</p>
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

        <form method="POST" action="{{ $action }}">
            @csrf

            {{ $identitas }}

            <x-input-error :messages="$errors->get('quantity')" class="mb-4" />

            {{-- Kesalahan per produk ditampilkan di barisnya masing-masing,
                 tapi barisnya bisa sedang tersembunyi oleh penyaring, jadi
                 diberi penanda di atas juga. --}}
            @if ($errors->has('quantity.*'))
                <x-alert type="error" message="Ada produk yang jumlahnya melebihi stok. Cek keterangan merah di daftar produk di bawah." />
            @endif

            @if ($productsByCategory->isNotEmpty())
                <x-order.filter-bar :categories="$productsByCategory->keys()" />
            @endif

            <x-card class="overflow-hidden">
                @forelse ($productsByCategory as $category => $products)
                    <x-order.category-group :category="$category" :products="$products" />
                @empty
                    <x-admin.empty-state
                        title="Belum ada produk tersedia"
                        description="Admin belum menambahkan produk ke katalog."
                    />
                @endforelse

                {{-- Muncul kalau kata pencarian tidak cocok dengan produk mana pun --}}
                <div x-show="tidakAdaHasil" x-cloak>
                    <x-admin.empty-state
                        title="Produk tidak ditemukan"
                        description="Coba ganti kata pencarian, atau pilih kategori lain."
                    />
                </div>
            </x-card>

            @if ($productsByCategory->isNotEmpty())
                <div class="mt-6">
                    <x-order.delivery-picker :alamat-tersimpan="$alamatTersimpan" />
                </div>

                <x-order.summary-bar :batal="$batal" />
            @endif
        </form>
    @endif
</div>
