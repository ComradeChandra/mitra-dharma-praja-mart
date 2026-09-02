{{--
    Halaman login anggota — terpisah dari login admin (resources/views/auth/login.blade.php).
    Pakai x-guest-layout yang sama (cuma kartu putih di tengah, tidak butuh
    Auth::user() jadi aman dipakai siapa saja yang belum login).

    Nama anggota dipilih dari dropdown, BUKAN diketik manual. Alasannya dari
    rapat: kode anggota seperti "0010 A" gampang salah ketik (spasi, huruf
    besar-kecil), dan pengurus minta namanya langsung kelihatan buat dipilih.
--}}
<x-guest-layout subtitle="Masuk sebagai Anggota">
    <div class="mb-4 text-sm text-gray-600">
        Pilih nama kamu, lalu masukkan password yang dibuatkan pengurus.
    </div>

    {{-- Pesan error umum (mis. anggota/password salah) --}}
    <x-input-error :messages="$errors->get('member_code')" class="mb-4" />

    <form method="POST" action="{{ route('member.login.store') }}">
        @csrf

        <div>
            <x-input-label for="member_code" value="Nama Kamu" />

            @if ($members->isNotEmpty())
                {{--
                    <select> tidak dicakup komponen <x-text-input>, jadi class-nya
                    ditulis di sini supaya bentuknya tetap sama dengan kotak isian
                    lain di halaman ini.
                --}}
                <select
                    id="member_code"
                    name="member_code"
                    required
                    autofocus
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="" disabled {{ old('member_code') ? '' : 'selected' }}>
                        — Pilih nama —
                    </option>
                    @foreach ($members as $member)
                        <option
                            value="{{ $member->member_code }}"
                            @selected(old('member_code') === $member->member_code)
                        >
                            {{ $member->full_name }} ({{ $member->member_code }})
                        </option>
                    @endforeach
                </select>
            @else
                {{--
                    Belum ada anggota terdaftar sama sekali. Dropdown kosong cuma
                    bikin bingung, jadi ditampilkan keterangan yang jelas.
                --}}
                <p class="mt-1 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    Belum ada data anggota di sistem. Hubungi pengurus koperasi
                    untuk didaftarkan lebih dulu.
                </p>
            @endif
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="block mt-1 w-full"
                required
                autocomplete="current-password"
                :disabled="$members->isEmpty()"
            />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button :disabled="$members->isEmpty()">Masuk</x-primary-button>
        </div>
    </form>
</x-guest-layout>
