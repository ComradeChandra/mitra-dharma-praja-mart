{{--
    Partial form anggota, dipakai bareng create.blade.php dan edit.blade.php.
    $member dikirim dari edit.blade.php buat isi value lama; di create tidak ada,
    makanya semua pakai null-safe (??) / operator "old('field', $member->field ?? '')".

    Disusun per section (Identitas, Akun & Password, Status) biar enak dibaca.
--}}
<div class="space-y-6">
    {{-- Section: Identitas --}}
    <div class="pb-6 border-b border-gray-100 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">Identitas</h3>

        <div>
            <x-input-label for="member_code" value="Kode Anggota" />
            <x-text-input
                id="member_code"
                name="member_code"
                type="text"
                class="block mt-1 w-full"
                placeholder="Contoh: 0010 A"
                :value="old('member_code', $member->member_code ?? '')"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('member_code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="full_name" value="Nama Lengkap" />
            <x-text-input
                id="full_name"
                name="full_name"
                type="text"
                class="block mt-1 w-full"
                :value="old('full_name', $member->full_name ?? '')"
                required
            />
            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="whatsapp_number" value="Nomor WhatsApp" />
            <x-text-input
                id="whatsapp_number"
                name="whatsapp_number"
                type="text"
                class="block mt-1 w-full"
                placeholder="Contoh: 628123456789 (tanpa spasi/tanda +)"
                :value="old('whatsapp_number', $member->whatsapp_number ?? '')"
                required
            />
            <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
        </div>
    </div>

    {{-- Alamat: opsional. Dipakai sebagai isian awal kolom alamat pengantaran
         waktu anggota memesan — anggota tetap bisa mengubahnya per pesanan,
         dan bisa juga mengisinya sendiri kalau di sini dikosongkan. --}}
    <div class="pb-6 border-b border-gray-100">
        <x-input-label for="address" value="Alamat (opsional)" />
        <textarea
            id="address"
            name="address"
            rows="2"
            placeholder="Dipakai sebagai isian awal alamat pengantaran. Boleh dikosongkan."
            class="block mt-1 w-full border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm text-sm"
        >{{ old('address', $member->address ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    {{-- Section: Akun & Password --}}
    <div class="pb-6 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Akun & Password</h3>

        {{--
            Password anggota. Di form tambah (create) WAJIB diisi (anggota baru harus
            langsung punya password). Di form edit OPSIONAL — kosongkan kalau admin
            tidak mau ganti password anggota ini.

            Pakai Alpine.js (x-data/x-model) buat tombol "lihat password" — admin perlu
            baca ulang passwordnya buat dikasih tau ke anggota (mis. via WhatsApp),
            makanya defaultnya disamarkan tapi bisa ditampilkan sementara.
        --}}
        <div x-data="{ showPassword: false }">
            <x-input-label for="password" :value="isset($member) ? 'Password Baru (opsional)' : 'Password Awal'" />
            <div class="relative mt-1">
                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    class="border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm block w-full pr-20"
                    placeholder="{{ isset($member) ? 'Kosongkan kalau tidak ingin ganti password' : 'Minimal 8 karakter' }}"
                    minlength="8"
                    {{ isset($member) ? '' : 'required' }}
                >
                <button
                    type="button"
                    @click="showPassword = ! showPassword"
                    class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-emerald-700 hover:text-emerald-900"
                >
                    <span x-text="showPassword ? 'Sembunyikan' : 'Lihat'"></span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-1.5 text-xs text-gray-400">
                Password ini yang perlu kamu kasih tau ke anggota (mis. lewat WhatsApp) supaya
                mereka bisa login.
            </p>
        </div>
    </div>

    {{-- Section: Status --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Status</h3>
        {{-- old() dulu (kalau form pernah gagal validasi), kalau tidak ada baru cek
             data lama ($member), kalau anggota baru default aktif. --}}
        <x-toggle
            name="is_active"
            label="Anggota aktif"
            :checked="old('is_active', $member->is_active ?? true)"
        />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>Simpan</x-primary-button>
        <a href="{{ route('admin.members.index') }}">
            <x-secondary-button type="button">Batal</x-secondary-button>
        </a>
    </div>
</div>
