{{--
    Halaman "Masuk" terpadu — satu tempat untuk ketiga jenis pengguna.
    Peran dipilih lewat dropdown, lalu formulir yang sesuai muncul.

    Tiap formulir tetap dikirim ke alamat login masing-masing yang sudah ada,
    jadi tidak ada logika keamanan baru di halaman ini — cuma tampilannya
    yang disatukan.

    Peran yang sedang dipilih diingat lewat input tersembunyi "peran", supaya
    kalau login gagal, halaman kembali ke formulir yang tadi dipakai, bukan
    lompat ke formulir anggota lagi.
--}}
@php
    // Peran yang aktif saat halaman pertama dimuat. Dipakai buat menentukan
    // form mana yang inputnya hidup sejak awal, tidak menunggu Alpine jalan.
    $peranAwal = old('peran', 'anggota');
@endphp

<x-guest-layout :title="'Masuk — ' . config('app.name')" subtitle="Koperasi Mitra Dharma Praja">
    <div x-data="{ peran: '{{ $peranAwal }}' }">

        <div class="mb-5">
            <x-input-label for="peran" value="Masuk sebagai" />
            <select
                id="peran"
                x-model="peran"
                class="block mt-1 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm"
            >
                <option value="anggota">Anggota koperasi</option>
                <option value="non-anggota">Non-anggota (lewat OPD)</option>
                <option value="pengurus">Pengurus koperasi</option>
            </select>
        </div>

        {{-- ========== ANGGOTA ========== --}}
        <div x-show="peran === 'anggota'" x-cloak>
            <p class="mb-4 text-sm text-gray-600">
                Pilih nama kamu, lalu masukkan password yang dibuatkan pengurus.
            </p>

            <x-input-error :messages="$errors->get('member_code')" class="mb-4" />

            <form method="POST" action="{{ route('member.login.store') }}">
                @csrf
                <input type="hidden" name="peran" value="anggota">

                <div>
                    <x-input-label for="member_code" value="Nama Kamu" />
                    @if ($members->isNotEmpty())
                        <select
                            id="member_code"
                            name="member_code"
                            required
                            @disabled($peranAwal !== 'anggota')
                            x-bind:disabled="peran !== 'anggota'"
                            class="block mt-1 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm"
                        >
                            <option value="" disabled {{ old('member_code') ? '' : 'selected' }}>— Pilih nama —</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->member_code }}" @selected(old('member_code') === $member->member_code)>
                                    {{ $member->full_name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <p class="mt-1 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                            Belum ada data anggota. Hubungi pengurus koperasi untuk didaftarkan.
                        </p>
                    @endif
                </div>

                <div class="mt-4">
                    <x-input-label for="password_anggota" value="Password" />
                    <x-text-input
                        id="password_anggota"
                        name="password"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="current-password"
                        :disabled="$peranAwal !== 'anggota' || $members->isEmpty()"
                        x-bind:disabled="peran !== 'anggota'"
                    />
                </div>

                <div class="mt-5">
                    <x-primary-button class="w-full justify-center" :disabled="$members->isEmpty()">
                        Masuk
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- ========== NON-ANGGOTA ========== --}}
        <div x-show="peran === 'non-anggota'" x-cloak>
            <p class="mb-4 text-sm text-gray-600">
                Pilih instansi kamu, lalu masukkan kode akses yang dibagikan pengurus
                koperasi ke kantor kamu.
            </p>

            <x-input-error :messages="$errors->get('opd_department_id')" class="mb-4" />
            <x-input-error :messages="$errors->get('access_code')" class="mb-4" />

            <form method="POST" action="{{ route('non-member.login.store') }}">
                @csrf
                <input type="hidden" name="peran" value="non-anggota">

                <div>
                    <x-input-label for="opd_department_id" value="Instansi / OPD" />
                    <select
                        id="opd_department_id"
                        name="opd_department_id"
                        required
                        @disabled($peranAwal !== 'non-anggota')
                        x-bind:disabled="peran !== 'non-anggota'"
                        class="block mt-1 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm"
                    >
                        <option value="" disabled {{ old('opd_department_id') ? '' : 'selected' }}>— Pilih instansi —</option>
                        @foreach ($opdDepartments as $opd)
                            <option value="{{ $opd->id }}" @selected((int) old('opd_department_id') === $opd->id)>
                                {{ $opd->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <x-input-label for="access_code" value="Kode Akses" />
                    <x-text-input
                        id="access_code"
                        name="access_code"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="off"
                        :disabled="$peranAwal !== 'non-anggota'"
                        x-bind:disabled="peran !== 'non-anggota'"
                    />
                </div>

                <div class="mt-5">
                    <x-primary-button class="w-full justify-center">Masuk</x-primary-button>
                </div>
            </form>
        </div>

        {{-- ========== PENGURUS / ADMIN ========== --}}
        <div x-show="peran === 'pengurus'" x-cloak>
            <p class="mb-4 text-sm text-gray-600">
                Khusus pengurus koperasi yang mengelola produk, pesanan, dan rekap.
            </p>

            <x-input-error :messages="$errors->get('email')" class="mb-4" />

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="peran" value="pengurus">

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input
                        id="email"
                        name="email"
                        type="email"
                        class="block mt-1 w-full"
                        :value="old('email')"
                        required
                        autocomplete="username"
                        :disabled="$peranAwal !== 'pengurus'"
                        x-bind:disabled="peran !== 'pengurus'"
                    />
                </div>

                <div class="mt-4">
                    <x-input-label for="password_pengurus" value="Password" />
                    <x-text-input
                        id="password_pengurus"
                        name="password"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="current-password"
                        :disabled="$peranAwal !== 'pengurus'"
                        x-bind:disabled="peran !== 'pengurus'"
                    />
                </div>

                <div class="mt-5">
                    <x-primary-button class="w-full justify-center">Masuk</x-primary-button>
                </div>
            </form>
        </div>

    </div>
</x-guest-layout>
