{{--
    Partial form OPD, dipakai bareng oleh create.blade.php dan edit.blade.php.
    Variabel $opdDepartment dikirim dari edit.blade.php (buat isi value lama);
    di halaman create, $opdDepartment sengaja tidak ada makanya pakai null-safe (??).
--}}
<div>
    <x-input-label for="name" value="Nama OPD" />
    <x-text-input
        id="name"
        name="name"
        type="text"
        class="block mt-1 w-full"
        :value="old('name', $opdDepartment->name ?? '')"
        required
        autofocus
    />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

{{--
    PENTING: jangan menaruh direktif Blade (@if, @isset, dst) di dalam atribut
    tag komponen <x-...>. Blade tidak memproses tag seperti itu, tagnya
    dibiarkan mentah di HTML, dan browser tidak menganggapnya kotak isian.
    Kolom ini sempat begitu ("@if (...) required @endif"): sejak awal admin
    tidak bisa menambah OPD atau mengganti kode aksesnya dari aplikasi.
    Pakai atribut boolean seperti :required="..." di bawah.
--}}
<div class="mt-4">
    <x-input-label for="access_code" value="Kode Akses Non-Anggota" />
    <x-text-input
        id="access_code"
        name="access_code"
        type="text"
        class="block mt-1 w-full"
        placeholder="{{ isset($opdDepartment) ? 'Kosongkan kalau tidak mau ganti' : 'Minimal 6 karakter' }}"
        :value="old('access_code')"
        minlength="6"
        :required="! isset($opdDepartment)"
    />
    <p class="mt-1 text-xs text-gray-400">
        Kode ini yang dipakai non-anggota dari OPD ini buat login (dibagikan admin ke
        stafnya, bukan bikin akun sendiri).
    </p>
    <x-input-error :messages="$errors->get('access_code')" class="mt-2" />
</div>

<div class="flex items-center gap-3 mt-6">
    <x-primary-button>Simpan</x-primary-button>
    <a href="{{ route('admin.opd-departments.index') }}">
        <x-secondary-button type="button">Batal</x-secondary-button>
    </a>
</div>
