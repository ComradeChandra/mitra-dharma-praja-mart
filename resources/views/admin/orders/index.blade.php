<x-app-layout :title="'Pesanan — ' . config('app.name')">
    <x-slot name="header">
        <div>
            <x-page-heading>{{ __('Pesanan') }}</x-page-heading>
            <p class="text-sm text-gray-400">{{ $orders->total() }} pesanan masuk</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <x-alert type="success" :message="session('success')" />

            {{-- Tab filter status, link sederhana pakai query string, bukan JS/Alpine,
                 biar hasil filternya juga langsung ke-bookmark/refresh dengan benar. --}}
            <div class="flex items-center gap-1 mb-4">
                @foreach ([
                    null => 'Semua',
                    \App\Enums\OrderStatus::Pending->value => 'Menunggu Verifikasi',
                    \App\Enums\OrderStatus::Verified->value => 'Terverifikasi',
                    \App\Enums\OrderStatus::Invoiced->value => 'Invoice Terkirim',
                ] as $value => $label)
                    {{-- animate-[pop-in_...] = tombol tab muncul gantian berurutan
                         pas halaman kebuka, lihat @keyframes di resources/css/app.css --}}
                    <a
                        href="{{ route('admin.orders.index', array_filter(['status' => $value, 'bayar' => $statusBayar])) }}"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition animate-[pop-in_0.3s_ease-out_backwards] {{ $status === $value ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-100' }}"
                        style="animation-delay: {{ $loop->index * 40 }}ms"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Bilah kedua: status pembayaran. Dipisah dari status pesanan
                 karena keduanya bergerak sendiri-sendiri, dan pengurus sering
                 perlu menyaring "menunggu konfirmasi" saja buat dicocokkan
                 ke mutasi rekening. Filter yang satu tidak menghapus yang lain. --}}
            <div class="flex items-center gap-1 mb-4 flex-wrap">
                <span class="text-xs text-gray-400 mr-1">Pembayaran:</span>
                @foreach ([
                    null => 'Semua',
                    \App\Enums\PaymentStatus::Unpaid->value => 'Belum Dibayar',
                    \App\Enums\PaymentStatus::AwaitingConfirmation->value => 'Menunggu Konfirmasi',
                    \App\Enums\PaymentStatus::Paid->value => 'Lunas',
                ] as $value => $label)
                    <a
                        href="{{ route('admin.orders.index', array_filter(['status' => $status, 'bayar' => $value])) }}"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $statusBayar === $value ? 'bg-emerald-600 text-white' : 'text-gray-500 hover:bg-gray-100' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <x-admin.table-card>
                @if ($orders->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-admin.th>Pemesan</x-admin.th>
                                <x-admin.th>Periode</x-admin.th>
                                <x-admin.th>Tanggal</x-admin.th>
                                <x-admin.th>Total</x-admin.th>
                                <x-admin.th>Status</x-admin.th>
                                <x-admin.th>Bayar</x-admin.th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($orders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                        {{ $order->member->full_name ?? $order->non_member_name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $order->orderPeriod->label }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $order->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : '—' }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                        <x-admin.badge :color="$order->status->badgeColor()">
                                            {{ $order->status->label() }}
                                        </x-admin.badge>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                        <x-admin.badge :color="$order->payment_status->color()">
                                            {{ $order->payment_status->label() }}
                                        </x-admin.badge>
                                        {{-- Penanda kecil kalau pemesan melampirkan bukti transfer,
                                             biar pengurus tahu mana yang lebih cepat dicocokkan --}}
                                        @if ($order->payment_proof_path)
                                            <span class="block text-[11px] text-gray-400 mt-0.5">ada bukti</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Lihat →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-admin.empty-state
                        title="Belum ada pesanan"
                        description="Pesanan dari anggota akan muncul di sini setelah mereka kirim lewat halaman Pesan Produk."
                    />
                @endif
            </x-admin.table-card>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
