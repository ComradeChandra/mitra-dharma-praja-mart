<x-app-layout :title="'Edit Periode Pemesanan — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Edit Periode Pemesanan') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.order-periods.index') }}">Kembali ke Periode Pemesanan</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.order-periods.update', $orderPeriod) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.order-periods._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
