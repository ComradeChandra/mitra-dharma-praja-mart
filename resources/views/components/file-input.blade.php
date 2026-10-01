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

    Foto yang lebih besar dari batas server diperkecil dulu di browser
    sebelum dikirim (resources/js/file-input.js). Foto kamera HP 3–8 MB,
    batas server 2 MB.

    Props:
    - name      : nama field yang dikirim ke server
    - id        : bawaannya sama dengan name
    - accept    : jenis berkas yang boleh dipilih, mis. "image/jpeg,image/png"
    - pratinjau : true = tampilkan pratinjau kecil foto yang dipilih
    - potong    : true = setelah memilih foto, buka jendela untuk mengatur
                  potongan PERSEGI (geser + perbesar). Dipakai foto produk,
                  yang di katalog memang tampil persegi. Otomatis ikut
                  menampilkan pratinjau. JANGAN dipakai untuk bukti transfer:
                  bukti harus utuh untuk dicocokkan pengurus.

    Contoh: <x-file-input name="image" accept="image/jpeg,image/png" :potong="true" />
--}}
@props(['name', 'id' => null, 'accept' => null, 'pratinjau' => false, 'potong' => false])

@php
    $id ??= $name;
    $pratinjau = $pratinjau || $potong;
@endphp

<div
    x-data="inputBerkas({ potong: @js((bool) $potong), pratinjau: @js((bool) $pratinjau) })"
    {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}
>
    {{-- Input aslinya tetap ada dan tetap yang dikirim ke server, cuma tidak
         kelihatan. "peer" dipakai supaya fokus keyboard di input ini tetap
         kelihatan lewat cincin di tombol penggantinya. --}}
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="file"
        @if ($accept) accept="{{ $accept }}" @endif
        class="peer sr-only"
        x-ref="input"
        x-on:change="pilih($event)"
    >

    {{-- Pratinjau kecil foto yang dipilih (sesudah dipotong, kalau dipotong) --}}
    @if ($pratinjau)
        <template x-if="pratinjauUrl">
            <img
                :src="pratinjauUrl"
                alt="Pratinjau foto yang dipilih"
                class="h-14 w-14 shrink-0 rounded-lg border border-gray-200 object-cover"
            >
        </template>
    @endif

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
        x-text="sedangMemproses ? 'Memproses foto…' : (namaBerkas || 'Belum ada berkas dipilih')"
    >Belum ada berkas dipilih</span>

    @if ($potong)
        {{-- Jendela pengatur potongan. Tombolnya type="button" supaya tidak
             mengirim form yang membungkus komponen ini.

             x-teleport="body": jendelanya dipindah ke <body>. Kalau tetap di
             dalam kartu form, pembungkus yang memakai efek CSS (transform /
             backdrop-filter) membuat lapisan "fixed" ini terkurung di kartu
             itu saja, dan tombolnya bisa terpotong di bawah layar. Data &
             $refs Alpine tetap terhubung ke komponen ini. --}}
        <template x-teleport="body">
        <div
            x-cloak
            x-show="potongTerbuka"
            x-transition.opacity
            x-on:keydown.escape.window="potongTerbuka && batal()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $id }}-judul-potong"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        >
            {{-- max-h + overflow: di layar pendek (HP mendatar) isinya bisa
                 digulir, jadi tombol Pakai/Batal selalu terjangkau --}}
            <div class="max-h-[92vh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-5 shadow-xl">
                <h3 id="{{ $id }}-judul-potong" class="text-base font-semibold text-gray-900">Atur foto</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Geser foto untuk memilih bagian yang tampil, lalu perbesar kalau perlu.
                    Isi kotak inilah yang tampil di katalog.
                </p>

                {{-- Bingkai persegi. touch-none: geseran jari menggerakkan
                     foto, bukan menggulir halaman. --}}
                <div
                    x-ref="bingkai"
                    tabindex="0"
                    aria-label="Potongan foto. Geser dengan tetikus, jari, atau tombol panah."
                    class="relative mt-4 aspect-square w-full touch-none cursor-grab overflow-hidden rounded-xl bg-gray-100 active:cursor-grabbing focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    x-on:pointerdown="mulaiGeser($event)"
                    x-on:pointermove="lanjutGeser($event)"
                    x-on:pointerup="akhiriGeser()"
                    x-on:pointercancel="akhiriGeser()"
                    x-on:keydown.arrow-left.prevent="geserTombol(-10, 0)"
                    x-on:keydown.arrow-right.prevent="geserTombol(10, 0)"
                    x-on:keydown.arrow-up.prevent="geserTombol(0, -10)"
                    x-on:keydown.arrow-down.prevent="geserTombol(0, 10)"
                >
                    <img
                        x-show="k"
                        :src="fotoUrl"
                        :style="gayaFoto"
                        alt=""
                        draggable="false"
                        class="pointer-events-none absolute left-0 top-0 max-w-none select-none"
                    >
                </div>

                <label class="mt-4 flex items-center gap-3 text-sm text-gray-600">
                    <span class="shrink-0">Perbesar</span>
                    <input
                        type="range"
                        min="1"
                        max="3"
                        step="0.01"
                        :value="k ? k.perbesar : 1"
                        x-on:input="ubahPerbesar($event.target.value)"
                        class="w-full accent-emerald-700"
                    >
                </label>

                <div class="mt-5 flex justify-end gap-2">
                    <x-secondary-button type="button" x-on:click="batal()">Batal</x-secondary-button>
                    <x-primary-button type="button" x-on:click="pakai()" x-bind:disabled="!k || sedangMemproses">
                        Pakai foto ini
                    </x-primary-button>
                </div>
            </div>
        </div>
        </template>
    @endif
</div>
