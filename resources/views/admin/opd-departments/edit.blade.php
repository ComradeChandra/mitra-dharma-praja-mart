<x-app-layout :title="'Edit OPD — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Edit OPD') }}</x-page-heading>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-back-link href="{{ route('admin.opd-departments.index') }}">Kembali ke Data OPD</x-back-link>

            <x-card class="p-6">
                {{-- method PUT buat update, di-spoof lewat @method karena HTML form cuma support GET/POST --}}
                <form method="POST" action="{{ route('admin.opd-departments.update', $opdDepartment) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.opd-departments._form')
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
