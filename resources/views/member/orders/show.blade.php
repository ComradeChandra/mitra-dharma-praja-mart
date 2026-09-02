<x-layouts.member :title="'Detail Pesanan — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-back-link href="{{ route('member.orders.index') }}">Kembali ke Pesanan Saya</x-back-link>

        <x-alert type="success" :message="session('success')" />

        <x-card class="overflow-hidden">
            {{-- Header: periode + status --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div>
                    <h1 class="font-semibold text-gray-800">{{ $order->orderPeriod->label }}</h1>
                    <p class="text-xs text-gray-400">Dikirim {{ $order->created_at->format('d M Y, H:i') }}</p>
                </div>
                <x-admin.badge :color="$order->status->badgeColor()">
                    {{ $order->status->label() }}
                </x-admin.badge>
            </div>

            {{-- Pesan penjelas kalau masih ada item yang harganya belum dikunci admin --}}
            @if ($order->status === \App\Enums\OrderStatus::Pending)
                <div class="px-5 py-3 bg-amber-50 text-amber-700 text-xs">
                    Ada produk dengan harga fluktuatif di pesanan ini — totalnya baru final
                    setelah admin mengunci harganya.
                </div>
            @endif

            {{-- Daftar item pesanan --}}
            <div class="divide-y divide-gray-50">
                @foreach ($order->orderItems as $item)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $item->product->name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->product->formatJumlah($item->quantity) }} x
                                {{ $item->price_at_order !== null ? 'Rp'.number_format($item->price_at_order, 0, ',', '.') : 'menunggu harga' }}
                            </p>
                        </div>
                        <span class="text-sm font-medium text-gray-700 shrink-0">
                            {{ $item->price_at_order !== null ? 'Rp'.number_format($item->quantity * $item->price_at_order, 0, ',', '.') : '—' }}
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Cara terima barang, sebelumnya cuma kelihatan admin, padahal
                 pemesannya sendiri yang paling butuh memastikan alamatnya benar. --}}
            <div class="px-5 py-3 border-t border-gray-100">
                <p class="text-xs text-gray-400">Cara terima barang</p>
                <p class="text-sm font-medium text-gray-800">{{ $order->delivery_method->label() }}</p>
                @if ($order->delivery_address)
                    <p class="mt-0.5 text-sm text-gray-600 whitespace-pre-line">{{ $order->delivery_address }}</p>
                @endif
            </div>

            {{-- Total --}}
            <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-t border-gray-100">
                <span class="text-sm font-medium text-gray-600">Total</span>
                <span class="text-lg font-bold text-gray-900">
                    {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : 'Menunggu verifikasi' }}
                </span>
            </div>

            {{--
                Bagikan struk lewat WhatsApp. Tautannya tanpa nomor tujuan, jadi
                WhatsApp membuka daftar kontak — anggota bebas mengirimnya ke
                dirinya sendiri buat arsip, ke pasangan, atau ke pengurus.

                Cuma muncul kalau total sudah final: pesanan yang masih menunggu
                verifikasi harga fluktuatif belum punya angka pasti.
            --}}
            @if ($tautanBagikan)
                <div class="px-5 py-4 border-t border-gray-100">
                    <a
                        href="{{ $tautanBagikan }}"
                        target="_blank"
                        rel="noopener"
                        class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.6-.91-2.2-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.47 0 1.46 1.06 2.87 1.21 3.07.15.2 2.09 3.2 5.08 4.48.71.31 1.26.49 1.69.63.71.23 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35Z"/>
                        </svg>
                        Bagikan Struk lewat WhatsApp
                    </a>
                    <p class="mt-2 text-xs text-gray-400 text-center">
                        Struknya akan terisi otomatis di WhatsApp — kamu tinggal pilih mau dikirim ke siapa.
                    </p>
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.member>
