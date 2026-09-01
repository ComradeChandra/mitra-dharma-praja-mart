@php
    // Sesi non-anggota nggak punya OPD terpisah sebagai variabel siap pakai
    // kayak $opd di halaman lain, di sini cukup ambil dari relasi order-nya,
    // karena halaman ini emang tujuannya nunjukin 1 pesanan spesifik.
    $opd = $order->opdDepartment;
@endphp
<x-layouts.non-member :opd="$opd" :title="'Bukti Pesanan — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <a href="{{ route('non-member.orders.create') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            ← Pesan Lagi
        </a>

        <x-alert type="success" :message="session('success')" />

        <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            {{-- Header: periode + status --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div>
                    <h1 class="font-semibold text-gray-800">{{ $order->orderPeriod->label }}</h1>
                    <p class="text-xs text-gray-400">
                        {{ $order->non_member_name }} · {{ $order->opdDepartment->name }} ·
                        Dikirim {{ $order->created_at->format('d M Y, H:i') }}
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
                    {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : 'Menunggu verifikasi' }}
                </span>
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-400 text-center">
            Simpan halaman ini sebagai bukti pesanan. Invoice lengkap akan dikirim admin
            lewat WhatsApp ke nomor yang kamu daftarkan.
        </p>
    </div>
</x-layouts.non-member>
