<x-app-layout :title="'Pengaturan — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>Pengaturan</x-page-heading>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert type="success" :message="session('success')" />

            {{-- Nomor WhatsApp koperasi, dipakai tombol "Hubungi Pengurus" --}}
            <x-card class="p-6">
                <div class="flex items-start gap-3">
                    <span class="shrink-0 p-2 rounded-lg bg-emerald-50 text-emerald-700">
                        <x-ikon-whatsapp class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 class="font-semibold text-gray-800">Nomor WhatsApp koperasi</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Nomor ini dipakai tombol <span class="font-medium text-gray-700">Hubungi Pengurus</span>
                            di halaman anggota, non-anggota, katalog, dan halaman masuk. Pemesan yang
                            menekannya langsung masuk ke chat WhatsApp dengan nomor ini.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="whatsapp_number" value="Nomor WhatsApp" />
                        <x-text-input
                            id="whatsapp_number"
                            name="whatsapp_number"
                            type="text"
                            inputmode="tel"
                            autocomplete="off"
                            placeholder="Contoh: 081234567890"
                            class="block mt-1 w-full"
                            :value="old('whatsapp_number', $nomorWhatsApp)"
                        />
                        <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
                        <p class="mt-2 text-xs text-gray-400">
                            Boleh ditulis dengan spasi atau tanda strip. Kosongkan kalau tombolnya
                            tidak mau ditampilkan dulu.
                        </p>
                    </div>

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <x-primary-button>Simpan</x-primary-button>

                        {{-- Uji nomornya: membuka chat ke nomor yang tersimpan,
                             tanpa mengirim apa pun --}}
                        @if ($tautanUji)
                            <a
                                href="{{ $tautanUji }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800"
                            >
                                Coba buka chat-nya
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5M19 5l-8 8M10 5H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-4" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </form>

                {{-- Status sekarang, supaya jelas tombolnya sedang tampil atau tidak --}}
                <p class="mt-6 pt-4 border-t border-gray-100 text-sm {{ $nomorWhatsApp ? 'text-gray-500' : 'text-amber-700' }}">
                    @if ($nomorWhatsApp)
                        Tombol <span class="font-medium">Hubungi Pengurus</span> sedang tampil untuk anggota dan non-anggota.
                    @else
                        Nomor belum diisi, jadi tombol <span class="font-medium">Hubungi Pengurus</span> belum tampil.
                    @endif
                </p>
            </x-card>
        </div>
    </div>
</x-app-layout>
