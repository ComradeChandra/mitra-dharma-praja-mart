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
                  potongan PERSEGI (kotak potong yang sudutnya bisa ditarik
                  dan bisa dipindah). Dipakai foto produk,
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
                    Tarik sudut kotak untuk mengubah ukurannya, tarik bagian tengahnya untuk memindahkan.
                    Isi kotak inilah yang tampil di katalog.
                </p>

                {{-- Bingkai gelap persegi. Padding (p-3) memberi ruang untuk
                     pegangan sudut yang menonjol keluar dari kotak. --}}
                <div class="mt-4 aspect-square w-full overflow-hidden rounded-xl bg-gray-900 p-3">
                    {{-- Area tempat foto utuh digambar; semua hitungan
                         potong-foto.js relatif terhadap area ini.
                         touch-none: tarikan jari menggerakkan kotak, bukan
                         menggulir halaman. --}}
                    <div
                        x-ref="bingkai"
                        tabindex="0"
                        aria-label="Potongan foto. Tarik dengan tetikus atau jari; atau pakai tombol panah untuk memindah dan + / − untuk mengubah ukuran."
                        class="relative h-full w-full touch-none select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                        x-on:pointermove="lanjutSeret($event)"
                        x-on:pointerup="akhiriSeret()"
                        x-on:pointercancel="akhiriSeret()"
                        x-on:keydown="tombolKeyboard($event)"
                    >
                        <img
                            x-show="kotak"
                            :src="fotoUrl"
                            :style="gayaFoto"
                            alt=""
                            draggable="false"
                            class="pointer-events-none absolute max-w-none select-none"
                        >

                        {{-- Kotak potong. Bayangan raksasa menggelapkan semua
                             bagian di luar kotak (dipotong overflow-hidden
                             bingkai), jadi tidak perlu empat panel gelap. --}}
                        <div
                            x-show="kotak"
                            :style="gayaKotak"
                            class="absolute cursor-move border-2 border-white shadow-[0_0_0_9999px_rgba(0,0,0,0.55)]"
                            x-on:pointerdown="mulaiSeret($event, 'geser')"
                        >
                            {{-- Pegangan sudut. .stop: menarik sudut tidak ikut
                                 dianggap menarik kotak (memindahkan). --}}
                            @foreach ([
                                'kiri-atas' => '-left-3 -top-3 cursor-nwse-resize',
                                'kanan-atas' => '-right-3 -top-3 cursor-nesw-resize',
                                'kiri-bawah' => '-left-3 -bottom-3 cursor-nesw-resize',
                                'kanan-bawah' => '-right-3 -bottom-3 cursor-nwse-resize',
                            ] as $sudut => $letak)
                                <span
                                    data-sudut="{{ $sudut }}"
                                    class="absolute {{ $letak }} h-6 w-6 rounded-md border-2 border-white bg-emerald-600 shadow"
                                    x-on:pointerdown.stop="mulaiSeret($event, '{{ $sudut }}')"
                                ></span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <x-secondary-button type="button" x-on:click="batal()">Batal</x-secondary-button>
                    <x-primary-button type="button" x-on:click="pakai()" x-bind:disabled="!kotak || sedangMemproses">
                        Pakai foto ini
                    </x-primary-button>
                </div>
            </div>
        </div>
        </template>
    @endif
</div>
