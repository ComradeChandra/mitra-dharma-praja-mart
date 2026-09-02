<x-app-layout :title="'Buat Periode Pemesanan — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Buat Periode Pemesanan') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.order-periods.index') }}">Kembali ke Periode Pemesanan</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.order-periods.store') }}">
                    @csrf
                    @include('admin.order-periods._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
