<x-app-layout>
    <x-slot name="header">
        <x-page-heading>{{ __('Tambah Anggota') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.members.index') }}">Kembali ke Data Anggota</x-back-link>

            <x-card class="p-6">
                <form method="POST" action="{{ route('admin.members.store') }}">
                    @csrf
                    @include('admin.members._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
