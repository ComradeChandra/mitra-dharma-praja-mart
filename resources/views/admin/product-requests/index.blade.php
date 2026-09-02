<x-app-layout>
    <x-slot name="header">
        <div>
            <x-page-heading>{{ __('Permintaan Produk') }}</x-page-heading>
            <p class="text-sm text-gray-400">{{ $productRequests->total() }} permintaan masuk</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <x-alert type="success" :message="session('success')" />

            <x-admin.table-card>
                @if ($productRequests->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-admin.th>Produk Diminta</x-admin.th>
                                <x-admin.th>Pemohon</x-admin.th>
                                <x-admin.th>Tanggal</x-admin.th>
                                <x-admin.th>Status</x-admin.th>
                                <x-admin.th align="right">Aksi</x-admin.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($productRequests as $productRequest)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $productRequest->product_name }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $productRequest->requester_label }}
                                        <span class="text-gray-300">·</span>
                                        <span class="text-xs">{{ $productRequest->user_type->label() }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $productRequest->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                        <x-admin.badge :color="$productRequest->status->badgeColor()">
                                            {{ $productRequest->status->label() }}
                                        </x-admin.badge>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                        @if ($productRequest->status === \App\Enums\ProductRequestStatus::Pending)
                                            <div class="flex items-center justify-end gap-3">
                                                <form method="POST" action="{{ route('admin.product-requests.approve', $productRequest) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-medium">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.product-requests.reject', $productRequest) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Tolak</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-300">Sudah ditinjau</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-admin.empty-state
                        title="Belum ada permintaan produk"
                        description="Permintaan dari anggota/non-anggota akan muncul di sini."
                    />
                @endif
            </x-admin.table-card>

            <div class="mt-4">
                {{ $productRequests->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
