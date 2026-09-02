<x-app-layout :title="'Tambah OPD — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Tambah OPD') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.opd-departments.index') }}">Kembali ke Data OPD</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.opd-departments.store') }}">
                    @csrf
                    @include('admin.opd-departments._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
