{{--
    Antrean "lupa password" anggota. Terbuka untuk semua pengurus. Aturannya
    di PasswordResetService; halaman ini cuma menampilkan dan menyediakan
    tombolnya.
--}}
<x-app-layout :title="'Lupa Password Anggota — ' . config('app.name')">
    <x-slot name="header">
        <div>
            <x-page-heading>Permintaan Lupa Password</x-page-heading>
            <p class="text-sm text-gray-400">
                Dari anggota yang tidak bisa masuk. Siapa pun pengurus yang sedang membuka halaman ini boleh menanganinya.
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert type="success" :message="session('success')" />

            {{-- Password baru, ditampilkan SEKALI setelah dibuatkan --}}
            @if (session('passwordBaru'))
                @php $baru = session('passwordBaru'); @endphp
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm text-emerald-900">
                        Password baru untuk <span class="font-semibold">{{ $baru['nama'] }}</span> sudah berlaku:
                    </p>
                    <p class="mt-2 font-mono text-2xl font-bold tracking-wider text-gray-900 select-all">{{ $baru['password'] }}</p>
                    <p class="mt-2 text-xs text-emerald-800">
                        Kirim ke nomor WhatsApp yang terdaftar. Password ini tidak akan ditampilkan lagi
                        setelah halaman ini ditinggalkan.
                    </p>
                    <a href="{{ $baru['tautan'] }}" target="_blank" rel="noopener"
                       class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800 transition">
                        <x-ikon-whatsapp class="h-4 w-4" />
                        Kirim lewat WhatsApp
                    </a>
                </div>
            @endif

            {{-- Yang menunggu --}}
            <x-card class="overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 text-sm">Menunggu ditangani</h3>
                    <x-admin.badge :color="$menunggu->isEmpty() ? 'gray' : 'amber'">{{ $menunggu->count() }}</x-admin.badge>
                </div>

                @forelse ($menunggu as $permintaan)
                    <div class="px-5 py-4 border-b border-gray-50 last:border-0 flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $permintaan->member->full_name }}
                                <span class="font-normal text-gray-400">· {{ $permintaan->member->member_code }}</span>
                            </p>
                            <p class="text-xs text-gray-500">
                                WhatsApp {{ $permintaan->member->whatsapp_number }} ·
                                diminta {{ $permintaan->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                            @if ($permintaan->note)
                                <p class="mt-1.5 text-sm text-gray-600">"{{ $permintaan->note }}"</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <form method="POST" action="{{ route('admin.password-requests.dismiss', $permintaan) }}"
                                  data-confirm="Abaikan permintaan dari {{ $permintaan->member->full_name }}? Password-nya tidak diubah."
                                  onsubmit="return confirm(this.dataset.confirm);">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-800">Abaikan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.password-requests.reset', $permintaan) }}"
                                  data-confirm="Buatkan password baru untuk {{ $permintaan->member->full_name }}? Password lamanya langsung tidak berlaku."
                                  onsubmit="return confirm(this.dataset.confirm);">
                                @csrf
                                @method('PATCH')
                                <x-primary-button>Buatkan password baru</x-primary-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state
                        title="Tidak ada permintaan"
                        description='Anggota yang lupa password bisa mengajukan lewat tautan "Lupa password?" di halaman masuk.'
                    />
                @endforelse
            </x-card>

            {{-- Riwayat singkat: siapa menangani apa --}}
            @if ($riwayat->isNotEmpty())
                <x-card class="overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Sudah ditangani</h3>
                    </div>
                    @foreach ($riwayat as $permintaan)
                        <div class="px-5 py-3 border-b border-gray-50 last:border-0 flex flex-wrap items-center justify-between gap-3 text-sm">
                            <span class="text-gray-700">{{ $permintaan->member->full_name }}</span>
                            <span class="flex items-center gap-3 text-xs text-gray-400">
                                {{ $permintaan->penangan?->name ?? 'Akun yang sudah dihapus' }} ·
                                {{ $permintaan->handled_at?->translatedFormat('d M Y, H:i') }}
                                <x-admin.badge :color="$permintaan->status->color()">{{ $permintaan->status->label() }}</x-admin.badge>
                            </span>
                        </div>
                    @endforeach
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
