@php
    // Sesi non-anggota nggak punya OPD terpisah sebagai variabel siap pakai
    // kayak $opd di halaman lain, di sini cukup ambil dari relasi order-nya,
    // karena halaman ini emang tujuannya nunjukin 1 pesanan spesifik.
    $opd = $order->opdDepartment;
@endphp
<x-layouts.non-member :opd="$opd" :title="'Bukti Pesanan — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-back-link href="{{ route('non-member.orders.create') }}">Pesan Lagi</x-back-link>

        <x-alert type="success" :message="session('success')" />

        {{-- Muncul cuma kalau pesanannya sudah dibatalkan --}}
        <x-order.cancelled-notice :order="$order" class="mb-6" />

        {{-- Muncul kalau pengurus pernah mengubah isi pesanan ini --}}
        <x-order.admin-note :order="$order" class="mb-6" />

        {{-- Catatan halus kalau sebagian barang melebihi persediaan (tanpa angka stok) --}}
        <x-order.catatan-stok :order="$order" class="mb-6" />

        <x-card class="overflow-hidden">
            {{-- Header: periode + status --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div>
                    <h1 class="font-semibold text-gray-800">{{ $order->orderPeriod->label }}</h1>
                    <p class="text-xs text-gray-400">
                        {{ $order->non_member_name }} · {{ $order->opdDepartment->name }} ·
                        Dikirim {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                    </p>
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
                            <p class="text-xs text-gray-400">{{ $item->quantity }} x
                                {{ $item->price_at_order !== null ? 'Rp'.number_format($item->price_at_order, 0, ',', '.') : 'menunggu harga' }}
                            </p>
                        </div>
                        <span class="text-sm font-medium text-gray-700 shrink-0">
                            {{ $item->price_at_order !== null ? 'Rp'.number_format($item->quantity * $item->price_at_order, 0, ',', '.') : '—' }}
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Total --}}
            <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-t border-gray-100">
                <span class="text-sm font-medium text-gray-600">Total</span>
                <span class="text-lg font-bold text-gray-900">
                    {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : ($order->dibatalkan() ? '—' : 'Menunggu verifikasi') }}
                </span>
            </div>
        </x-card>

        {{-- Pesanan yang dibatalkan tidak butuh struk --}}
        @unless ($order->dibatalkan())
            <div class="mt-4">
                {{-- Struk resmi: halaman tersendiri yang siap dicetak atau
                     disimpan jadi PDF lewat dialog cetak browser. --}}
                <a
                    href="{{ route('non-member.orders.struk', $order) }}"
                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H4v-6h16v6h-2M8 14h8v6H8z" />
                    </svg>
                    Lihat Struk Resmi
                </a>
            </div>
        @endunless

        <div class="mt-6">
            <x-order.payment-panel :order="$order" :action="route('non-member.orders.declare-paid', $order)" :bukti-url="route('non-member.orders.payment-proof', $order)" />
        </div>

        {{-- Batalkan pesanan: boleh sendiri selama periode masih dibuka dan
             belum dibayar, selebihnya lewat pengurus. --}}
        <x-order.cancel-panel
            class="mt-6"
            :order="$order"
            :action="route('non-member.orders.cancel', $order)"
            :alasan="$alasanTidakBisaBatal"
        />

        <p class="mt-4 text-xs text-gray-400 text-center">
            {{-- Non-anggota tidak punya halaman riwayat pesanan, jadi mereka perlu
                 tahu jalan kembalinya: tautan di invoice WhatsApp. --}}
            Simpan halaman ini sebagai bukti pesanan. Invoice lengkap beserta tautan untuk
            membayar akan dikirim pengurus lewat WhatsApp ke nomor yang kamu isi.
        </p>
    </div>
</x-layouts.non-member>
