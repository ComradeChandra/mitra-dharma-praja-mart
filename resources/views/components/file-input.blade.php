{{--
    Input unggah berkas dengan tulisan berbahasa Indonesia.

    KENAPA ADA: input berkas bawaan browser menulis tombol dan keterangannya
    sendiri, dalam bahasa browser pemakainya. Di laptop yang browsernya
    berbahasa Inggris, yang muncul "Choose File / No file chosen" di tengah
    aplikasi yang semuanya berbahasa Indonesia. Tulisan itu tidak bisa diganti
    lewat CSS, jadi input aslinya disembunyikan dan diganti tombol buatan
    sendiri yang tetap memicu input yang sama.

    Sebelumnya tiga form menulis gayanya masing-masing (foto produk, foto
    profil, bukti transfer), jadi sekalian disatukan di sini.

    Props:
    - name   : nama field yang dikirim ke server
    - id     : bawaannya sama dengan name
    - accept : jenis berkas yang boleh dipilih, mis. "image/jpeg,image/png"

    Contoh: <x-file-input name="photo" accept="image/*" class="mt-1" />
--}}
@props(['name', 'id' => null, 'accept' => null])

@php
    $id ??= $name;
@endphp

<div x-data="{ namaBerkas: '' }" {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    {{-- Input aslinya tetap ada dan tetap yang dikirim ke server, cuma tidak
         kelihatan. "peer" dipakai supaya fokus keyboard di input ini tetap
         kelihatan lewat cincin di tombol penggantinya. --}}
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="file"
        @if ($accept) accept="{{ $accept }}" @endif
        class="peer sr-only"
        x-on:change="namaBerkas = $event.target.files.length ? $event.target.files[0].name : ''"
    >

    <label
        for="{{ $id }}"
        class="shrink-0 cursor-pointer inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0-4 4m4-4 4 4M5 20h14" />
        </svg>
        Pilih berkas
    </label>

    <span
        class="min-w-0 truncate text-sm"
        x-bind:class="namaBerkas ? 'text-gray-800' : 'text-gray-400'"
        x-text="namaBerkas || 'Belum ada berkas dipilih'"
    >Belum ada berkas dipilih</span>
</div>
