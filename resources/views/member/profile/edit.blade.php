{{--
    Halaman profil anggota — ubah foto, nomor WhatsApp, alamat, dan password.

    Nama lengkap & kode anggota SENGAJA ditampilkan tapi tidak bisa diubah:
    keduanya tetap dikelola pengurus (lihat catatan di Member\ProfileController).
--}}
<x-layouts.member :title="'Profil Saya — ' . config('app.name')">

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Ubah foto, nomor WhatsApp, dan alamat kamu di sini.
                </p>
            </div>

            <x-alert type="success" :message="session('success')" />

            {{-- ========== Data diri ========== --}}
            <form
                method="POST"
                action="{{ route('member.profile.update') }}"
                enctype="multipart/form-data"
                class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                @csrf
                @method('PATCH')

                {{-- Foto: pratinjau saat ini + tombol ganti --}}
                <div class="flex items-center gap-4">
                    @if ($member->photo_path)
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($member->photo_path) }}"
                            alt="Foto {{ $member->full_name }}"
                            class="h-20 w-20 rounded-full object-cover border border-gray-200"
                        >
                    @else
                        <span class="h-20 w-20 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-semibold">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->full_name, 0, 1)) }}
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <x-input-label for="photo" value="Foto Profil" />
                        <input
                            id="photo"
                            name="photo"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                        >
                        <p class="mt-1 text-xs text-gray-400">JPG, PNG, atau WEBP. Maksimal 2 MB. Boleh dikosongkan.</p>
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                    </div>
                </div>

                {{-- Dikelola pengurus, ditampilkan saja biar anggota tahu datanya benar --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400">Nama Lengkap</p>
                        <p class="font-medium text-gray-800">{{ $member->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Kode Anggota</p>
                        <p class="font-medium text-gray-800">{{ $member->member_code }}</p>
                    </div>
                    <p class="sm:col-span-2 text-xs text-gray-400">
                        Nama dan kode anggota dikelola pengurus koperasi. Hubungi pengurus
                        kalau ada yang perlu diperbaiki.
                    </p>
                </div>

                <div>
                    <x-input-label for="whatsapp_number" value="Nomor WhatsApp" />
                    <x-text-input
                        id="whatsapp_number"
                        name="whatsapp_number"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Contoh: 628123456789 (tanpa spasi/tanda +)"
                        :value="old('whatsapp_number', $member->whatsapp_number)"
                        required
                    />
                    <p class="mt-1 text-xs text-gray-400">Dipakai pengurus buat mengirim invoice pesanan kamu.</p>
                    <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address" value="Alamat" />
                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        placeholder="Contoh: Jl. Kebon Kopi No. 12, RT 03/RW 05, Cimahi Tengah"
                        class="block mt-1 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm text-sm"
                    >{{ old('address', $member->address) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">
                        Dipakai sebagai isian awal alamat pengantaran waktu kamu memesan —
                        tetap bisa diubah per pesanan.
                    </p>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <x-primary-button>Simpan Perubahan</x-primary-button>
                </div>
            </form>

            {{-- ========== Ganti password ========== --}}
            <form
                method="POST"
                action="{{ route('member.profile.password.update') }}"
                class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                @csrf
                @method('PUT')

                <div>
                    <h2 class="font-semibold text-gray-800">Ganti Password</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Password pertama kamu dibuatkan pengurus. Kamu bisa menggantinya sendiri di sini.
                    </p>
                </div>

                <div>
                    <x-input-label for="current_password" value="Password Sekarang" />
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
                    <x-input-label for="password_confirmation" value="Ulangi Password Baru" />
                    <x-text-input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="new-password"
                    />
                </div>

                <div class="pt-2">
                    <x-primary-button>Ganti Password</x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-layouts.member>
