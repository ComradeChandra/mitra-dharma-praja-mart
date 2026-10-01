<x-app-layout :title="'Akun Pengurus — ' . config('app.name')">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <x-page-heading>Akun Pengurus</x-page-heading>
                <p class="text-sm text-gray-400">Setiap staf koperasi masuk dengan akunnya sendiri.</p>
            </div>
            <a href="{{ route('admin.accounts.create') }}">
                <x-primary-button type="button">+ Tambah Pengurus</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- Tab Anggota | Pengurus: halaman ini sekarang dibuka dari tab
                 di halaman Data Anggota, bukan dari menu akun --}}
            <x-admin.tab-orang />

            <x-alert type="success" :message="session('success')" />

            <x-admin.table-card>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-admin.th>Nama</x-admin.th>
                            <x-admin.th>Peran</x-admin.th>
                            <x-admin.th>Status</x-admin.th>
                            <x-admin.th align="right">Aksi</x-admin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($akun as $orang)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $orang->name }}
                                        @if ($orang->is(auth()->user()))
                                            <span class="ml-1 text-xs font-normal text-gray-400">(kamu)</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $orang->email }}</p>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">
                                    <x-admin.badge :color="$orang->role->color()">{{ $orang->role->label() }}</x-admin.badge>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">
                                    <x-admin.active-badge :active="$orang->is_active" />
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                    <x-admin.edit-link :href="route('admin.accounts.edit', $orang)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.table-card>

            {{-- Penjelasan tingkatan pemakai, supaya jelas kenapa sebuah
                 akun diberi peran tertentu --}}
            <x-card class="p-6">
                <h3 class="font-semibold text-gray-800">Siapa bisa apa</h3>
                <p class="text-sm text-gray-500 mt-1 mb-4">
                    Aplikasi ini punya empat tingkatan pemakai. Admin Utama dan Pengurus masuk lewat
                    halaman pengurus; anggota dan non-anggota lewat halaman masuk biasa.
                </p>
                <x-admin.hak-akses />
            </x-card>
        </div>
    </div>
</x-app-layout>
