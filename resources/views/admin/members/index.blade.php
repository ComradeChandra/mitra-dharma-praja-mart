<x-app-layout :title="'Data Anggota — ' . config('app.name')">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-page-heading>{{ __('Data Anggota') }}</x-page-heading>
                <p class="text-sm text-gray-400">{{ $members->total() }} anggota terdaftar</p>
            </div>
            <a href="{{ route('admin.members.create') }}">
                <x-primary-button type="button">+ Tambah Anggota</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <x-alert type="success" :message="session('success')" />

            <x-admin.table-card>
                @if ($members->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-admin.th>Anggota</x-admin.th>
                                <x-admin.th>Kode</x-admin.th>
                                <x-admin.th>WhatsApp</x-admin.th>
                                <x-admin.th>Status</x-admin.th>
                                <x-admin.th align="right">Aksi</x-admin.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($members as $member)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            {{-- Avatar bulat berisi huruf depan nama, biar tabel tidak cuma teks --}}
                                            <span class="shrink-0 h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                                {{ Str::upper(Str::substr($member->full_name, 0, 1)) }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-900">{{ $member->full_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $member->member_code }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $member->whatsapp_number }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                        <x-admin.active-badge :active="$member->is_active" />
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-4">
                                            <x-admin.edit-link :href="route('admin.members.edit', $member)" />
                                            <x-admin.delete-form
                                                :action="route('admin.members.destroy', $member)"
                                                confirm="Yakin mau hapus anggota ini? Riwayat pesanan lama tidak akan ikut terhapus."
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-admin.empty-state
                        title="Belum ada data anggota"
                        description='Klik "+ Tambah Anggota" buat mulai isi daftar anggota koperasi.'
                    />
                @endif
            </x-admin.table-card>

            <div class="mt-4">
                {{ $members->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
