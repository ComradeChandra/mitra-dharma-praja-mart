<x-app-layout :title="'Data OPD — ' . config('app.name')">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-page-heading>{{ __('Data OPD') }}</x-page-heading>
                <p class="text-sm text-gray-400">{{ $opdDepartments->total() }} OPD terdaftar</p>
            </div>
            <a href="{{ route('admin.opd-departments.create') }}">
                <x-primary-button type="button">+ Tambah OPD</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- Pesan sukses setelah create/update/delete, dikirim lewat session flash --}}
            <x-alert type="success" :message="session('success')" />
            <x-alert type="error" :message="session('error')" />

            <x-card class="overflow-hidden">
                @forelse ($opdDepartments as $opdDepartment)
                    {{-- List sederhana (bukan tabel) karena cuma 1 kolom data (nama) --}}
                    <div class="flex items-center justify-between gap-4 px-5 py-4 {{ ! $loop->last ? 'border-b border-gray-100' : '' }} hover:bg-gray-50">
                        <div class="flex items-center gap-3 min-w-0">
                            {{-- Ikon gedung sebagai penanda visual OPD --}}
                            <span class="shrink-0 h-9 w-9 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            </span>
                            <p class="font-medium text-gray-800 truncate">{{ $opdDepartment->name }}</p>
                        </div>
                        <div class="flex items-center gap-4 shrink-0">
                            <x-admin.edit-link :href="route('admin.opd-departments.edit', $opdDepartment)" />
                            <x-admin.delete-form
                                :action="route('admin.opd-departments.destroy', $opdDepartment)"
                                confirm="Yakin mau hapus OPD ini? Riwayat pesanan lama tidak akan ikut terhapus."
                            />
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state
                        title="Belum ada data OPD"
                        description='Klik "+ Tambah OPD" buat mulai isi daftar instansi.'
                    />
                @endforelse
            </x-card>

            <div class="mt-4">
                {{ $opdDepartments->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
