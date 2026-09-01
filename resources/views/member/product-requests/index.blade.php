<x-layouts.member :title="'Permintaan Produk — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="font-semibold text-gray-800">Permintaan Produk Kamu</h1>
                <p class="text-sm text-gray-400">Riwayat usulan produk yang pernah kamu ajukan.</p>
            </div>
            <a href="{{ route('member.product-requests.create') }}">
                <x-primary-button type="button">+ Ajukan Baru</x-primary-button>
            </a>
        </div>

        <x-alert type="success" :message="session('success')" />

        <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            @forelse ($productRequests as $productRequest)
                <div class="flex items-center justify-between gap-4 px-5 py-4 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $productRequest->product_name }}</p>
                        <p class="text-xs text-gray-400">{{ $productRequest->created_at->format('d M Y') }}</p>
                    </div>
                    <x-admin.badge :color="$productRequest->status->badgeColor()">
                        {{ $productRequest->status->label() }}
                    </x-admin.badge>
                </div>
            @empty
                <x-admin.empty-state
                    title="Belum ada permintaan"
                    description='Klik "+ Ajukan Baru" kalau ada produk yang mau kamu usulkan.'
                />
            @endforelse
        </div>
    </div>
</x-layouts.member>
