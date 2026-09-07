<x-layouts.member :title="'Pesanan Saya — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="font-semibold text-gray-800">Pesanan Saya</h1>
                <p class="text-sm text-gray-400">Riwayat pesanan yang pernah kamu kirim.</p>
            </div>
            <a href="{{ route('member.orders.create') }}">
                <x-primary-button type="button">+ Pesan Produk</x-primary-button>
            </a>
        </div>

        <x-alert type="success" :message="session('success')" />

        <x-card class="overflow-hidden">
            @forelse ($orders as $order)
                <a href="{{ route('member.orders.show', $order) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $order->orderPeriod->label }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $order->created_at->format('d M Y') }}
                            @if ($order->total_amount !== null)
                                <span class="text-gray-300">·</span>
                                Rp{{ number_format($order->total_amount, 0, ',', '.') }}
                            @endif
                        </p>
                    </div>
                    {{-- Dua status ditampilkan berdampingan: perjalanan pesanan
                         dan perjalanan uang. Keduanya bergerak sendiri-sendiri. --}}
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <x-admin.badge :color="$order->status->badgeColor()">
                            {{ $order->status->label() }}
                        </x-admin.badge>
                        <x-admin.badge :color="$order->payment_status->color()">
                            {{ $order->payment_status->label() }}
                        </x-admin.badge>
                    </div>
                </a>
            @empty
                <x-admin.empty-state
                    title="Belum ada pesanan"
                    description='Klik "+ Pesan Produk" kalau periode pemesanan sedang dibuka.'
                />
            @endforelse
        </x-card>
    </div>
</x-layouts.member>
