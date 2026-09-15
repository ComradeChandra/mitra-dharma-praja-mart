<x-app-layout :title="'Ubah Akun Pengurus — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>Ubah Akun: {{ $akun->name }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.accounts.index') }}">Kembali ke Akun Pengurus</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.accounts.update', $akun) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.accounts._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
