<x-app-layout :title="'Tambah Akun Pengurus — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>Tambah Akun Pengurus</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.accounts.index') }}">Kembali ke Akun Pengurus</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.accounts.store') }}">
                    @csrf
                    @include('admin.accounts._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
