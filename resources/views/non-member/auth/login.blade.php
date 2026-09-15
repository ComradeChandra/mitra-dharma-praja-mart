{{--
    Halaman "login" non-anggota — SUDAH FIX 24 Agustus 2026 (lihat CLAUDE.md
    Lampiran B). Terpisah total dari login admin & anggota (isolasi sesuai
    CLAUDE.md). Bukan akun personal — pilih nama OPD + kode akses BERSAMA
    yang dibagikan admin ke stafnya, bukan bikin akun sendiri.
--}}
<x-guest-layout :title="'Masuk Non-Anggota — ' . config('app.name')" subtitle="Masuk sebagai Non-Anggota (per OPD)">
    <div class="mb-4 text-sm text-gray-600">
        Masuk sebagai non-anggota (staf OPD). Pilih nama OPD kamu, lalu masukkan
        kode akses yang dibagikan admin koperasi ke OPD kamu. Belum tahu kodenya?
        Tanyakan ke rekan sekantor atau hubungi pengurus.
    </div>

    {{-- Pesan error umum (mis. kode akses salah) --}}
    <x-input-error :messages="$errors->get('access_code')" class="mb-4" />
    <x-input-error :messages="$errors->get('opd_department_id')" class="mb-4" />

    <form method="POST" action="{{ route('non-member.login.store') }}">
        @csrf

        <div>
            <x-input-label for="opd_department_id" value="Nama OPD" />
            <select
                id="opd_department_id"
                name="opd_department_id"
                class="border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm block mt-1 w-full"
                required
                autofocus
            >
                <option value="">— Pilih OPD —</option>
                @foreach ($opdDepartments as $opd)
                    <option value="{{ $opd->id }}" @selected(old('opd_department_id') == $opd->id)>{{ $opd->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4">
            <x-input-label for="access_code" value="Kode Akses OPD" />
            <x-text-input
                id="access_code"
                name="access_code"
                type="password"
                class="block mt-1 w-full"
                required
            />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>Masuk</x-primary-button>
        </div>
    </form>
</x-guest-layout>
