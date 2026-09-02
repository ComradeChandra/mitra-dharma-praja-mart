<x-app-layout :title="'Periode Pemesanan — ' . config('app.name')">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-page-heading>{{ __('Periode Pemesanan') }}</x-page-heading>
                <p class="text-sm text-gray-400">{{ $orderPeriods->total() }} periode dibuat</p>
            </div>
            <a href="{{ route('admin.order-periods.create') }}">
                <x-primary-button type="button">+ Buat Periode</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <x-alert type="success" :message="session('success')" />
            <x-alert type="error" :message="session('error')" />

            <x-admin.table-card>
                @if ($orderPeriods->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-admin.th>Label</x-admin.th>
                                <x-admin.th>Tanggal</x-admin.th>
                                <x-admin.th>Status</x-admin.th>
                                <x-admin.th align="right">Aksi</x-admin.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($orderPeriods as $orderPeriod)
                                @php
                                    $isOpen = $orderPeriod->status === \App\Enums\OrderPeriodStatus::Open;
                                    // Sisa hari cuma relevan buat periode yang lagi dibuka & belum lewat tanggal selesai
                                    $daysLeft = $isOpen ? now()->startOfDay()->diffInDays($orderPeriod->end_date, false) : null;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $orderPeriod->label }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $orderPeriod->start_date->format('d M Y') }} – {{ $orderPeriod->end_date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                        <x-admin.badge :color="$isOpen ? 'green' : 'gray'">
                                            {{ $isOpen ? 'Dibuka' : 'Ditutup' }}
                                        </x-admin.badge>
                                        @if ($isOpen && $daysLeft !== null)
                                            <span class="ml-1 text-xs text-gray-400">
                                                @if ($daysLeft > 0)
                                                    · {{ $daysLeft }} hari lagi
                                                @elseif ($daysLeft === 0)
                                                    · berakhir hari ini
                                                @else
                                                    · terlambat ditutup
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="{{ route('admin.order-periods.rekap', $orderPeriod) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Rekap</a>
                                            <x-admin.edit-link :href="route('admin.order-periods.edit', $orderPeriod)" />
                                            <x-admin.delete-form
                                                :action="route('admin.order-periods.destroy', $orderPeriod)"
                                                confirm="Yakin mau hapus periode ini?"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-admin.empty-state
                        title="Belum ada periode pemesanan"
                        description='Klik "+ Buat Periode" buat mulai.'
                    />
                @endif
            </x-admin.table-card>

            <div class="mt-4">
                {{ $orderPeriods->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
