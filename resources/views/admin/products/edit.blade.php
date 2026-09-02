<x-app-layout :title="'Edit Produk — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Edit Produk') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.products.index') }}">Kembali ke Katalog Produk</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.products._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
