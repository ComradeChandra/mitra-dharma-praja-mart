<x-app-layout :title="'Tambah Produk — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Tambah Produk') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.products.index') }}">Kembali ke Katalog Produk</x-back-link>

            <x-card class="p-6">
                {{-- enctype wajib "multipart/form-data" karena form ini upload file foto --}}
                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('admin.products._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
