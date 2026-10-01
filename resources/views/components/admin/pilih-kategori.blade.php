{{--
    Dropdown kategori produk + pilihan "+ Buat kategori baru".
    Logika nilai awalnya ada di App\View\Components\Admin\PilihKategori.

    Dua kolom terpisah (category & category_baru) supaya form tetap bisa
    dipakai walau JavaScript mati: tanpa Alpine, kotak kategori baru selalu
    tampil, dan server (Requests\Concerns\RapikanKategori) yang memilih kolom
    mana yang dipakai.
--}}
<div x-data="{ pilihan: @js($pilihan), baru: @js($baru), BARU: @js($pilihanBaru) }">
    <select
        id="category"
        name="category"
        x-model="pilihan"
        required
        class="block mt-1 w-full border-gray-300 focus:border-emerald-600 focus:ring-emerald-600 rounded-lg shadow-sm"
    >
        <option value="" disabled @selected($pilihan === '')>— Pilih kategori —</option>
        @foreach ($daftar as $kategori)
            <option value="{{ $kategori }}" @selected($pilihan === $kategori)>{{ $kategori }}</option>
        @endforeach
        <option value="{{ $pilihanBaru }}" @selected($pilihan === $pilihanBaru)>+ Buat kategori baru…</option>
    </select>

    {{-- Muncul hanya kalau "+ Buat kategori baru" dipilih. --}}
    <div x-show="pilihan === BARU" class="mt-2">
        <x-text-input
            name="category_baru"
            type="text"
            class="block w-full"
            placeholder="Ketik nama kategori baru, mis. Sembako"
            aria-label="Nama kategori baru"
            :value="$baru"
            x-model="baru"
            x-bind:required="pilihan === BARU"
        />
    </div>
</div>
