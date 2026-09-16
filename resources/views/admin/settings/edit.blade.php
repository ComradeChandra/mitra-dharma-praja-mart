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

            {{--
                Persentase SHU. Angka ini yang dipakai beranda anggota untuk
                menghitung perkiraan SHU dari total belanjanya setahun.

                x-data "shu": pratinjau langsung memakai contoh belanja
                Rp1.000.000, jadi pengurus tahu efek angkanya sebelum menyimpan.
                Nilai awal min/maks dicetak dari server (Js::from) supaya isian
                yang dimanipulasi tidak masuk mentah ke JavaScript.
            --}}
            <x-card class="p-6" x-data="{
                min: {{ \Illuminate\Support\Js::from(old('shu_persen_min', $shuPersenMin)) }},
                maks: {{ \Illuminate\Support\Js::from(old('shu_persen_maks', $shuPersenMaks)) }},
                rupiah(persen) {
                    return 'Rp' + Math.round(1000000 * persen / 100).toLocaleString('id-ID');
                },
                get contoh() {
                    if (this.min === '' || this.maks === '' || this.min === null || this.maks === null) return '—';
                    return Number(this.min) === Number(this.maks)
                        ? this.rupiah(this.maks)
                        : this.rupiah(this.min) + ' – ' + this.rupiah(this.maks);
                },
            }">
                <div class="flex items-start gap-3">
                    <span class="shrink-0 p-2 rounded-lg bg-emerald-50 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M8 7h6a2.5 2.5 0 0 1 0 5H9m7 5h-6a2.5 2.5 0 0 1 0-5" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="font-semibold text-gray-800">Persentase SHU</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            SHU (Sisa Hasil Usaha) adalah bagian keuntungan koperasi yang dikembalikan ke
                            anggota di akhir tahun, sebanding dengan belanjanya. Angka di bawah dipakai
                            beranda anggota untuk menampilkan <span class="font-medium text-gray-700">perkiraan</span> SHU.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.shu.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="shu_persen_min" value="Perkiraan terendah (%)" />
                            <x-text-input
                                id="shu_persen_min"
                                name="shu_persen_min"
                                type="number"
                                min="0" max="100" step="0.05"
                                inputmode="decimal"
                                class="block mt-1 w-full"
                                x-model="min"
                                :value="old('shu_persen_min', $shuPersenMin)"
                                required
                            />
                            <x-input-error :messages="$errors->get('shu_persen_min')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="shu_persen_maks" value="Perkiraan tertinggi (%)" />
                            <x-text-input
                                id="shu_persen_maks"
                                name="shu_persen_maks"
                                type="number"
                                min="0" max="100" step="0.05"
                                inputmode="decimal"
                                class="block mt-1 w-full"
                                x-model="maks"
                                :value="old('shu_persen_maks', $shuPersenMaks)"
                                required
                            />
                            <x-input-error :messages="$errors->get('shu_persen_maks')" class="mt-2" />
                        </div>
                    </div>

                    <p class="text-xs text-gray-400">
                        Isi rentang perkiraan selama persentasenya belum pasti. Kalau koperasi sudah
                        memutuskan satu angka, isi kedua kotak sama — tampilan anggota otomatis berubah
                        dari rentang jadi satu angka.
                    </p>

                    {{-- Pratinjau langsung, ikut berubah saat diketik --}}
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3">
                        <p class="text-xs text-emerald-800">Contoh untuk anggota yang belanja Rp1.000.000 setahun:</p>
                        <p class="text-lg font-bold text-emerald-900" x-text="contoh">—</p>
                    </div>

                    <div class="pt-1">
                        <x-primary-button>Simpan Persentase SHU</x-primary-button>
                    </div>
                </form>

                {{-- Status: masih memakai bawaan atau sudah diatur dari aplikasi --}}
                <p class="mt-6 pt-4 border-t border-gray-100 text-sm text-gray-500">
                    @if ($shuSudahDiatur)
                        Persentase ini sudah diatur dari aplikasi.
                    @else
                        Belum diatur dari aplikasi, jadi masih memakai angka bawaan. Simpan sekali untuk menguncinya.
                    @endif
                </p>
            </x-card>
        </div>
    </div>
</x-app-layout>
