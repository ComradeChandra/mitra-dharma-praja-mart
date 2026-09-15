{{--
    Partial form akun pengurus, dipakai create.blade.php dan edit.blade.php.
    $akun cuma ada di halaman edit.

    Saat Admin Utama mengubah akunnya SENDIRI, pilihan peran & status tetap
    tampil tapi dikunci: menurunkan peran atau menonaktifkan akun sendiri
    ditolak (lihat UpdateAccountRequest), jadi tidak ada gunanya ditawarkan.
--}}
@php
    $akunSendiri = isset($akun) && $akun->is(auth()->user());
    $peranTerpilih = old('role', isset($akun) ? $akun->role->value : App\Enums\AdminRole::Pengurus->value);
@endphp

<div class="space-y-6">
    {{-- Identitas --}}
    <div class="pb-6 border-b border-gray-100 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">Identitas</h3>

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                :value="old('name', $akun->name ?? '')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email (dipakai untuk masuk)" />
            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                :value="old('email', $akun->email ?? '')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    </div>

    {{-- Peran --}}
    <fieldset class="pb-6 border-b border-gray-100">
        <legend class="text-sm font-semibold text-gray-700 mb-3">Peran</legend>

        <div class="space-y-3">
            @foreach (App\Enums\AdminRole::cases() as $peran)
                {{-- Sorotan pilihan mengikuti radio yang dicentang lewat CSS
                     (has-[:checked]), jadi ikut berubah saat diklik tanpa JS --}}
                <label class="flex items-start gap-3 rounded-xl border border-gray-200 px-4 py-3 has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50 {{ $akunSendiri ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }}">
                    <input type="radio" name="role" value="{{ $peran->value }}"
                        class="mt-1 text-emerald-700 focus:ring-emerald-600"
                        @checked($peranTerpilih === $peran->value)
                        @disabled($akunSendiri)>
                    <span>
                        <span class="block text-sm font-medium text-gray-800">{{ $peran->label() }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $peran->keterangan() }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        {{-- Input yang disabled tidak ikut terkirim, jadi nilainya dikirim lewat sini --}}
        @if ($akunSendiri)
            <input type="hidden" name="role" value="{{ $akun->role->value }}">
            <p class="mt-2 text-xs text-gray-400">Peran akunmu sendiri tidak bisa diubah dari sini.</p>
        @endif
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </fieldset>

    {{-- Password --}}
    <div class="pb-6 border-b border-gray-100" x-data="{ lihat: false }">
        <x-input-label for="password" :value="isset($akun) ? 'Password Baru (opsional)' : 'Password Awal'" />
        <div class="relative mt-1">
            <input id="password" name="password" :type="lihat ? 'text' : 'password'"
                class="border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm block w-full pr-24"
                placeholder="{{ isset($akun) ? 'Kosongkan kalau tidak diganti' : 'Minimal 8 karakter' }}"
                minlength="8" autocomplete="new-password" @required(! isset($akun))>
            <button type="button" @click="lihat = ! lihat"
                class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-emerald-700 hover:text-emerald-900">
                <span x-text="lihat ? 'Sembunyikan' : 'Lihat'">Lihat</span>
            </button>
        </div>
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
        <p class="mt-1.5 text-xs text-gray-400">
            Beri tahu pemilik akun password ini. Setelah masuk, ia bisa menggantinya sendiri di Profil Saya.
        </p>
    </div>

    {{-- Status --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Status</h3>
        @if ($akunSendiri)
            <input type="hidden" name="is_active" value="1">
            <p class="text-sm text-gray-500">Akunmu sendiri selalu aktif.</p>
        @else
            <x-toggle name="is_active" label="Akun aktif (bisa masuk)" :checked="old('is_active', $akun->is_active ?? true)" />
            <p class="mt-1.5 text-xs text-gray-400">
                Staf yang sudah tidak bertugas cukup dinonaktifkan, tidak perlu dihapus.
                Kalau sedang masuk, ia langsung dikeluarkan.
            </p>
        @endif
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>Simpan</x-primary-button>
        <a href="{{ route('admin.accounts.index') }}">
            <x-secondary-button type="button">Batal</x-secondary-button>
        </a>
    </div>
</div>
