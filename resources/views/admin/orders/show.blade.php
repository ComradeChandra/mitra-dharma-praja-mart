@php
    // Item yang masih butuh diisi harga admin (produk fluktuatif yang belum
    // dikunci), dipakai buat nentuin apakah form verifikasi perlu ditampilkan.
    $itemBelumBerharga = $order->orderItems->whereNull('price_at_order');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Detail Pesanan') }}
                </h2>
                <p class="text-sm text-gray-400">{{ $order->orderPeriod->label }}</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert type="success" :message="session('success')" />

            {{-- Info pemesan --}}
            <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-6">
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
                        <dd class="font-medium text-gray-800">{{ $order->created_at->format('d M Y, H:i') }}</dd>
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
            </div>

            {{-- Daftar item + form verifikasi harga (kalau ada yang masih kosong) --}}
            <form method="POST" action="{{ route('admin.orders.verify', $order) }}">
                @csrf
                @method('PATCH')

                <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Item Pesanan</h3>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach ($order->orderItems as $item)
                            <div class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $item->product->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $item->quantity }} {{ $item->product->category }}</p>
                                </div>

                                @if ($item->price_at_order !== null)
                                    <span class="text-sm font-medium text-gray-700 shrink-0">
                                        Rp{{ number_format($item->price_at_order, 0, ',', '.') }} / pcs
                                    </span>
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
                                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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
                            {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : 'Menunggu harga fluktuatif' }}
                        </span>
                    </div>
                </div>

                @if ($itemBelumBerharga->isNotEmpty())
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
                <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm overflow-hidden">
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

                    <div class="flex items-center gap-3 px-5 py-4 bg-gray-50 border-t border-gray-100">
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
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
