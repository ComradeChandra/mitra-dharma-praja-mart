<x-app-layout :title="'Profil Saya — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>{{ __('Profil Saya') }}</x-page-heading>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-alert type="success" :message="session('success')" />

            {{-- Kartu 1: Informasi Profil (nama & email) --}}
            <x-card class="p-6">
                <h3 class="font-semibold text-gray-800">Informasi Profil</h3>
                <p class="text-sm text-gray-400 mt-1 mb-5">Nama dan email yang dipakai buat login sebagai admin.</p>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="block mt-1 w-full"
                            :value="old('name', $user->name)"
                            required
                            autofocus
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input
                            id="email"
                            name="email"
                            type="email"
                            class="block mt-1 w-full"
                            :value="old('email', $user->email)"
                            required
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="pt-2">
                        <x-primary-button>Simpan Perubahan</x-primary-button>
                    </div>
                </form>
            </x-card>

            {{-- Kartu 2: Ubah Password (form terpisah, sengaja tidak digabung
                 sama form nama/email di atas biar validasinya independen) --}}
            <x-card class="p-6">
                <h3 class="font-semibold text-gray-800">Ubah Password</h3>
                <p class="text-sm text-gray-400 mt-1 mb-5">Masukkan password lama buat konfirmasi sebelum menggantinya.</p>

                <form method="POST" action="{{ route('admin.profile.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="current_password" value="Password Lama" />
                        <x-text-input
                            id="current_password"
                            name="current_password"
                            type="password"
                            class="block mt-1 w-full"
                            required
                            autocomplete="current-password"
                        />
                        <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Password Baru" />
                        <x-text-input
                            id="password"
                            name="password"
                            type="password"
                            class="block mt-1 w-full"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
                        <x-text-input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="block mt-1 w-full"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="pt-2">
                        <x-primary-button>Ganti Password</x-primary-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
