{{--
    Pilihan cara terima barang + alamat pengantaran.
    Dipakai BARENG form pesan anggota & non-anggota — bentuknya sama persis,
    yang beda cuma isian awal alamatnya.

    Props:
    - alamatTersimpan : alamat yang sudah tercatat di data anggota. Dipakai
                        sebagai isian awal supaya anggota tidak perlu mengetik
                        ulang tiap memesan — TAPI tetap bisa diubah kalau kali
                        ini mau dikirim ke tempat lain. Non-anggota tidak punya
                        data tersimpan, jadi dikirim null (kolomnya kosong).

    Kalau memilih "Ambil di koperasi", kolom alamat disembunyikan sekaligus
    dimatikan (disabled) — input yang disabled tidak ikut terkirim, jadi tidak
    ada alamat nyasar yang tersimpan di pesanan ambil-sendiri.
--}}
@props(['alamatTersimpan' => null])

@php
    // Pilihan yang sedang aktif: pakai yang tadi dikirim kalau formnya gagal
    // validasi, kalau belum ada default-nya "antar".
    // Nilai lama dari form yang gagal validasi dicetak ke dalam kode Alpine,
    // jadi dibatasi ke nilai yang sah dan dicetak lewat Js::from. Isian yang
    // dimanipulasi (teks aneh, larik) tidak boleh ikut masuk ke JavaScript.
    $metodeLama = old('delivery_method');
    $metodeTerpilih = (is_string($metodeLama) ? \App\Enums\DeliveryMethod::tryFrom($metodeLama) : null)?->value
        ?? \App\Enums\DeliveryMethod::Antar->value;
    $alamatAwal = old('delivery_address', $alamatTersimpan);
@endphp

<div
    class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-card p-5"
    x-data="{ cara: {{ \Illuminate\Support\Js::from($metodeTerpilih) }} }"
>
    <h3 class="font-semibold text-gray-800 text-sm mb-1">Cara Terima Barang</h3>
    <p class="text-xs text-gray-400 mb-4">
        Barangnya mau diantar ke alamat kamu, atau mau kamu ambil sendiri di koperasi?
    </p>

    {{-- Dua pilihan berbentuk kartu besar biar gampang dipencet dari HP --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach (\App\Enums\DeliveryMethod::cases() as $metode)
            <label
                class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                x-bind:class="cara === '{{ $metode->value }}'
                    ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
            >
                <input
                    type="radio"
                    name="delivery_method"
                    value="{{ $metode->value }}"
                    x-model="cara"
                    {{-- dibaca jendela konfirmasi sebelum kirim (x-order.confirm-dialog) --}}
                    data-label="{{ $metode->label() }}"
                    data-butuh-alamat="{{ $metode->butuhAlamat() ? '1' : '0' }}"
                    class="mt-0.5 text-emerald-600 focus:ring-emerald-500 border-gray-300"
                >
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-gray-800">{{ $metode->label() }}</span>
                    <span class="block text-xs text-gray-500 mt-0.5">
                        {{ $metode->butuhAlamat()
                            ? 'Diantar ke alamat yang kamu tulis di bawah.'
                            : 'Kamu datang sendiri ke koperasi saat barang sudah siap.' }}
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    <x-input-error :messages="$errors->get('delivery_method')" class="mt-2" />

    {{-- Kolom alamat: cuma muncul kalau memilih diantar --}}
    <div x-show="cara === '{{ \App\Enums\DeliveryMethod::Antar->value }}'" x-cloak class="mt-4">
        <x-input-label for="delivery_address" value="Alamat Pengantaran" />

        <textarea
            id="delivery_address"
            name="delivery_address"
            rows="3"
            placeholder="Contoh: Jl. Kebon Kopi No. 12, RT 03/RW 05, Cimahi Tengah"
            x-bind:disabled="cara !== '{{ \App\Enums\DeliveryMethod::Antar->value }}'"
            class="block mt-1 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm text-sm"
        >{{ $alamatAwal }}</textarea>

        @if ($alamatTersimpan)
            <p class="mt-1.5 text-xs text-gray-400">
                Terisi otomatis dari data kamu. Ubah saja kalau kali ini mau dikirim ke tempat lain.
            </p>
        @endif

        <x-input-error :messages="$errors->get('delivery_address')" class="mt-2" />
    </div>
</div>
