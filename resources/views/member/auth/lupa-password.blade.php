{{--
    Halaman "Lupa password" anggota. Tidak mereset apa pun sendiri: permintaan
    masuk ke antrean yang dilihat semua pengurus, lalu pengurus membuatkan
    password baru dan mengirimnya ke nomor WhatsApp yang terdaftar (lihat
    PasswordResetService).
--}}
<x-guest-layout :title="'Lupa Password — ' . config('app.name')" subtitle="Lupa password anggota">
    @if (session('terkirim'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-4 text-sm text-emerald-900 leading-relaxed">
            <p class="font-semibold">Permintaan terkirim.</p>
            <p class="mt-1">
                Pengurus koperasi akan membuatkan password baru dan mengirimkannya lewat WhatsApp
                ke nomor yang terdaftar ({{ session('terkirim') }}).
            </p>
            <p class="mt-2">Setelah berhasil masuk, ganti passwordnya di menu <span class="font-medium">Profil Saya</span>.</p>
        </div>

        <a href="{{ route('masuk') }}" class="mt-5 inline-flex text-sm font-medium text-emerald-700 hover:text-emerald-900">
            ← Kembali ke halaman masuk
        </a>
    @else
        <p class="mb-4 text-sm text-gray-600 leading-relaxed">
            Pilih nama kamu. Permintaannya masuk ke pengurus koperasi, lalu password baru
            dikirim lewat WhatsApp ke nomor yang terdaftar.
        </p>

        <form method="POST" action="{{ route('member.password-request.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="member_code" value="Nama Kamu" />
                <select id="member_code" name="member_code" required
                    class="block mt-1 w-full border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm">
                    <option value="" disabled {{ old('member_code') ? '' : 'selected' }}>— Pilih nama —</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->member_code }}" @selected(old('member_code') === $member->member_code)>
                            {{ $member->full_name }} ({{ $member->member_code }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('member_code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="note" value="Keterangan (boleh dikosongkan)" />
                <textarea id="note" name="note" rows="2" maxlength="255"
                    placeholder="Mis. nomor WhatsApp saya sudah ganti"
                    class="block mt-1 w-full border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm text-sm">{{ old('note') }}</textarea>
                <x-input-error :messages="$errors->get('note')" class="mt-2" />
            </div>

            <x-primary-button class="w-full justify-center">Kirim permintaan</x-primary-button>
        </form>

        <a href="{{ route('masuk') }}" class="mt-5 inline-flex text-sm text-gray-500 hover:text-gray-800">
            ← Ingat passwordnya? Kembali ke halaman masuk
        </a>
    @endif
</x-guest-layout>
