@php
    // Item yang masih butuh diisi harga admin (produk fluktuatif yang belum
    // dikunci), dipakai buat nentuin apakah form verifikasi perlu ditampilkan.
    // Pesanan yang sudah dibatalkan tidak perlu dikunci harganya.
    $itemBelumBerharga = $order->orderItems->whereNull('price_at_order');
    $bisaDikunci = $itemBelumBerharga->isNotEmpty() && ! $order->dibatalkan();

    // Ringkasan pesanan LAIN yang ikut terisi kalau pengurus memilih
    // "terapkan ke semua", mis. "Telur Ayam 1kg: 12 pesanan lain". Angkanya
    // dihitung OrderService::pesananLainMenungguHarga().
    $ringkasanPesananLain = $itemBelumBerharga->unique('product_id')
        ->filter(fn ($item) => ($pesananLainMenunggu[$item->product_id] ?? 0) > 0)
        ->map(fn ($item) => $item->product->name.': '.$pesananLainMenunggu[$item->product_id].' pesanan lain')
        ->implode(' · ');
@endphp

<x-app-layout :title="'Detail Pesanan — ' . config('app.name')">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-page-heading>{{ __('Detail Pesanan') }}</x-page-heading>
                <p class="text-sm text-gray-400">{{ $order->orderPeriod->label }}</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert type="success" :message="session('success')" />

            {{-- Muncul cuma kalau pesanannya sudah dibatalkan --}}
            <x-order.cancelled-notice :order="$order" />

            {{-- Riwayat perubahan isi pesanan, sama dengan yang dilihat pemesan --}}
            <x-order.admin-note :order="$order" />

            {{-- Tinjauan stok: muncul kalau pesanan melebihi stok tercatat dan
                 belum diputuskan (Setujui: belanja lebih / Tolak: sesuaikan). --}}
            <x-admin.tinjauan-stok :order="$order" />

            {{-- Pesanan batal yang pembayarannya sempat berjalan. Kartu invoice
                 (tempat info bayar biasanya) tidak tampil untuk pesanan batal,
                 padahal pengurus masih butuh status & bukti transfernya untuk
                 mengembalikan uang. --}}
            @if ($order->dibatalkan() && $order->payment_status !== \App\Enums\PaymentStatus::Unpaid)
                <x-card class="px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700">Pembayaran sebelum dibatalkan</p>
                            <p class="text-xs text-gray-400 mt-0.5">Uang yang sudah masuk perlu dikembalikan ke pemesan.</p>
                        </div>
                        <x-admin.badge :color="$order->payment_status->color()">
                            {{ $order->payment_status->label() }}
                        </x-admin.badge>
                    </div>
                    <x-admin.payment-proof-link :order="$order" class="mt-3" />
                </x-card>
            @endif

            {{-- Info pemesan --}}
            <x-card class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800 text-sm">Info Pemesan</h3>
                    <x-admin.badge :color="$order->status->badgeColor()">
                        {{ $order->status->label() }}
                    </x-admin.badge>
                </div>
                {{-- Ditumpuk ke bawah di HP, nama OPD bisa panjang
                     ("Dinas Pekerjaan Umum dan Penataan Ruang"), kalau
                     dipaksa 2 kolom di layar sempit jadi patah-patah. --}}
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400">Nama</dt>
                        <dd class="font-medium text-gray-800">{{ $order->member->full_name ?? $order->non_member_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Nomor WhatsApp</dt>
                        <dd class="font-medium text-gray-800">{{ $order->whatsapp_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Tipe</dt>
                        <dd class="font-medium text-gray-800">{{ $order->user_type->label() }}</dd>
                    </div>
                    {{-- Instansi cuma ada di pesanan non-anggota. Ditampilkan supaya
                         bisa ditelusur OPD mana saja yang belanja. --}}
                    @if ($order->opdDepartment)
                        <div>
                            <dt class="text-xs text-gray-400">Instansi (OPD)</dt>
                            <dd class="font-medium text-gray-800">{{ $order->opdDepartment->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs text-gray-400">Tanggal Pesan</dt>
                        <dd class="font-medium text-gray-800">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                    {{-- Cara terima barang, kalau diantar, alamatnya ikut ditampilkan
                         supaya admin tidak perlu bertanya lagi lewat WhatsApp. --}}
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-gray-400">Cara Terima Barang</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $order->delivery_method->label() }}
                        </dd>
                        @if ($order->delivery_address)
                            <dd class="mt-1 text-sm text-gray-600 whitespace-pre-line">
                                {{ $order->delivery_address }}
                            </dd>
                        @endif
                    </div>
                </dl>
            </x-card>

            {{-- Daftar item + form verifikasi harga (kalau ada yang masih kosong) --}}
            <form method="POST" action="{{ route('admin.orders.verify', $order) }}">
                @csrf
                @method('PATCH')

                <x-card class="overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Item Pesanan</h3>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach ($order->orderItems as $item)
                            <div class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $item->product->name }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ $item->quantity }} pcs · {{ $item->product->category }}
                                        @if ($item->melebihiStok())
                                            <span class="text-amber-700">· melebihi stok tercatat ({{ $item->stok_saat_pesan }})</span>
                                        @endif
                                    </p>
                                </div>

                                @if ($item->price_at_order !== null)
                                    <span class="text-sm font-medium text-gray-700 shrink-0">
                                        Rp{{ number_format($item->price_at_order, 0, ',', '.') }} / pcs
                                    </span>
                                @elseif (! $bisaDikunci)
                                    {{-- Pesanan batal: harganya tidak perlu diisi lagi --}}
                                    <span class="text-sm text-gray-400 shrink-0">—</span>
                                @else
                                    {{-- Input harga buat produk fluktuatif yang belum dikunci --}}
                                    <div class="shrink-0 w-40">
                                        <div class="flex items-center gap-1">
                                            <span class="text-sm text-gray-400">Rp</span>
                                            <input
                                                type="number"
                                                name="prices[{{ $item->id }}]"
                                                min="0"
                                                step="1"
                                                value="{{ old('prices.'.$item->id) }}"
                                                placeholder="Harga per pcs"
                                                class="w-full rounded-lg border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 text-sm"
                                            >
                                        </div>
                                        <x-input-error :messages="$errors->get('prices.'.$item->id)" class="mt-1" />
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-t border-gray-100">
                        <span class="text-sm font-medium text-gray-600">Total</span>
                        <span class="text-lg font-bold text-gray-900">
                            {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : ($order->dibatalkan() ? '—' : 'Menunggu harga fluktuatif') }}
                        </span>
                    </div>
                </x-card>

                {{--
                    Harga ini berlaku ke mana. "Semua" mengisi harga yang sama ke
                    pesanan lain di periode ini yang masih kosong harganya, supaya
                    30 pesanan telur tidak berarti mengetik harga 30 kali.
                    Cuma muncul kalau memang ada pesanan lain yang menunggu.
                    Bawaannya "pesanan ini saja", pilihan yang paling aman.
                --}}
                @if ($bisaDikunci && $ringkasanPesananLain !== '')
                    <x-card class="mt-4 p-4">
                        <fieldset>
                            <legend class="text-sm font-medium text-gray-800">Terapkan harga ini ke</legend>
                            <div class="mt-3 space-y-3">
                                <label class="flex items-start gap-2.5 text-sm text-gray-700 cursor-pointer">
                                    <input type="radio" name="terapkan" value="pesanan-ini"
                                           class="mt-0.5 border-gray-300 text-emerald-700 focus:ring-emerald-600"
                                           @checked(old('terapkan', 'pesanan-ini') !== 'semua')>
                                    <span>Pesanan ini saja</span>
                                </label>
                                <label class="flex items-start gap-2.5 text-sm text-gray-700 cursor-pointer">
                                    <input type="radio" name="terapkan" value="semua"
                                           class="mt-0.5 border-gray-300 text-emerald-700 focus:ring-emerald-600"
                                           @checked(old('terapkan') === 'semua')>
                                    <span>
                                        Semua pesanan di periode ini yang harganya masih kosong
                                        <span class="block mt-0.5 text-xs text-gray-500">{{ $ringkasanPesananLain }}</span>
                                        <span class="block mt-0.5 text-xs text-gray-400">Pesanan yang harganya sudah diisi tidak ditimpa.</span>
                                    </span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('terapkan')" class="mt-2" />
                        </fieldset>
                    </x-card>
                @endif

                @if ($bisaDikunci)
                    <div class="mt-4">
                        <x-primary-button>Verifikasi & Kunci Harga</x-primary-button>
                    </div>
                @endif
            </form>

            {{--
                Invoice WhatsApp (Modul 7) — cuma muncul kalau pesanan sudah
                final (verified/invoiced), soalnya pesanan "pending" belum
                punya total yang pasti (lihat Admin\OrderController::show()).
            --}}
            @if ($invoiceText)
                <x-card class="overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Invoice WhatsApp</h3>
                        <p class="text-xs text-gray-400">
                            Preview teks di bawah ini yang akan terisi otomatis di WhatsApp — kamu masih
                            harus klik "Kirim" sendiri di WhatsApp-nya, sistem tidak kirim otomatis.
                        </p>
                    </div>

                    <div class="p-5">
                        <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 rounded-lg p-4 border border-gray-100 font-sans">{{ $invoiceText }}</pre>
                    </div>

                    {{--
                        Status pembayaran QRIS. Terpisah dari status pesanan karena
                        dua hal berbeda: yang satu perjalanan pesanan, yang satu
                        perjalanan uang. Pesanan bisa terverifikasi tapi belum dibayar.

                        QRIS koperasi itu QRIS statis, jadi tidak ada webhook yang
                        memberi tahu aplikasi kalau ada yang bayar. Pengurus yang
                        mencocokkan ke mutasi rekening lalu menandai lunas di sini.
                    --}}
                    <div class="px-5 py-4 border-t border-gray-100">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-700">Pembayaran</p>
                                @if ($order->paid_declared_at)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Pemesan menyatakan bayar {{ $order->paid_declared_at->translatedFormat('d M Y, H:i') }}
                                    </p>
                                @endif
                                @if ($order->payment_confirmed_at)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Dikonfirmasi {{ $order->payment_confirmed_at->translatedFormat('d M Y, H:i') }}
                                    </p>
                                @endif
                            </div>
                            <x-admin.badge :color="$order->payment_status->color()">
                                {{ $order->payment_status->label() }}
                            </x-admin.badge>
                        </div>

                        {{-- Bukti transfer, kalau pemesan melampirkannya --}}
                        <x-admin.payment-proof-link :order="$order" class="mt-3" />

                        @if ($order->payment_status !== \App\Enums\PaymentStatus::Paid && $order->total_amount !== null)
                            <form method="POST" action="{{ route('admin.orders.confirm-payment', $order) }}" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                                    Konfirmasi Lunas
                                </button>
                                <span class="ml-2 text-xs text-gray-400">Tandai setelah uangnya kelihatan di rekening koperasi.</span>
                            </form>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-3 px-5 py-4 bg-gray-50 border-t border-gray-100">
                        {{-- Struk resmi buat dicetak atau disimpan jadi PDF, mis.
                             kalau pemesan minta bukti tertulis. --}}
                        <a href="{{ route('admin.orders.struk', $order) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-white transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H4v-6h16v6h-2M8 14h8v6H8z" />
                            </svg>
                            Struk Resmi
                        </a>
                        <a href="{{ $whatsAppLink }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2Z" opacity=".14"/>
                                <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.6-.91-2.2-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.47 0 1.46 1.06 2.87 1.21 3.07.15.2 2.09 3.2 5.08 4.48.71.31 1.26.49 1.69.63.71.23 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35Z"/>
                            </svg>
                            Kirim via WhatsApp
                        </a>

                        @if ($order->status === \App\Enums\OrderStatus::Verified)
                            <form method="POST" action="{{ route('admin.orders.mark-invoiced', $order) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-800 underline">
                                    Sudah dikirim, tandai selesai →
                                </button>
                            </form>
                        @else
                            <span class="text-sm text-teal-700 font-medium">✓ Sudah ditandai terkirim</span>
                        @endif
                    </div>
                </x-card>
            @endif

            {{-- Hapus satu barang: cuma kalau pesanannya masih berlaku, belum
                 dibayar, dan barangnya lebih dari satu --}}
            @if ($alasanTidakBisaHapusBarang === null)
                <x-admin.order-remove-item :order="$order" />
            @endif

            {{-- Batalkan pesanan (tidak tampil kalau sudah dibatalkan) --}}
            <x-admin.order-cancel :order="$order" />
        </div>
    </div>
</x-app-layout>
