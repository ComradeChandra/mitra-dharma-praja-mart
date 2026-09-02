<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-page-heading>{{ __('Katalog Produk') }}</x-page-heading>
                <p class="text-sm text-gray-400">{{ $products->total() }} produk</p>
            </div>
            <a href="{{ route('admin.products.create') }}">
                <x-primary-button type="button">+ Tambah Produk</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <x-alert type="success" :message="session('success')" />

            <x-admin.table-card>
                @if ($products->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-admin.th>Produk</x-admin.th>
                                <x-admin.th>Harga Jual</x-admin.th>
                                <x-admin.th>Stok</x-admin.th>
                                <x-admin.th>Status</x-admin.th>
                                <x-admin.th align="right">Aksi</x-admin.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($products as $product)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            @if ($product->image_path)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}"
                                                     alt="{{ $product->name }}" class="h-11 w-11 object-cover rounded-xl border border-gray-100 shadow-sm">
                                            @else
                                                <div class="h-11 w-11 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-300">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 16.5Z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                                                <p class="text-xs text-gray-400">{{ $product->category }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                                        @if ($product->is_fluctuating)
                                            <x-admin.badge color="amber">Fluktuatif</x-admin.badge>
                                        @else
                                            Rp{{ number_format($product->sell_price, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $product->has_stock_tracking ? $product->stock : '—' }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                        <x-admin.active-badge :active="$product->is_active" />
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-4">
                                            <x-admin.edit-link :href="route('admin.products.edit', $product)" />
                                            <x-admin.delete-form
                                                :action="route('admin.products.destroy', $product)"
                                                confirm="Yakin mau hapus produk ini?"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-admin.empty-state
                        title="Belum ada produk"
                        description='Klik "+ Tambah Produk" buat mulai isi katalog.'
                    />
                @endif
            </x-admin.table-card>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
