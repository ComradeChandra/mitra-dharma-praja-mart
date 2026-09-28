{{--
    Bilah ringkasan: berapa jenis produk yang sudah diisi, perkiraan totalnya,
    dan tombol kirim. Bisa dibuka buat melihat rinciannya.

    Ini BUKAN keranjang belanja: jumlah tetap diisi langsung di baris
    produknya. Yang ada di sini cuma cara melihat kembali apa yang sudah
    diisi tanpa perlu menyaring daftarnya dulu.

    Di HP menempel di dasar layar supaya tombol kirim selalu terjangkau. Di
    layar lebar dia ikut masuk kolom kanan, jadi tidak perlu menempel lagi
    (lg:static) — kolomnya sendiri yang menempel.

    SENGAJA TIDAK diberi x-cloak. Tombol kirimnya ada di sini, jadi kalau
    disembunyikan sampai Alpine selesai dimuat, halaman tanpa JavaScript jadi
    tidak punya tombol kirim sama sekali. Angkanya diisi nilai awal dari
    server ("0", "Rp0") yang nanti ditimpa Alpine.

    Props:
    - batal : URL tombol "Batal"
--}}
@props(['batal'])

<div class="sticky bottom-0 z-30 -mx-4 sm:mx-0 mt-6 border-t border-gray-200 bg-white/95 backdrop-blur-sm shadow-[0_-4px_12px_-6px_rgba(0,0,0,0.12)] lg:static lg:mt-0 lg:mx-0 lg:rounded-xl lg:border lg:border-gray-100 lg:shadow-sm">

    {{-- Rincian pilihan. Tingginya dibatasi supaya di HP tidak menutupi
         seluruh layar waktu produk yang dipilih banyak. --}}
    <div x-show="rincianTerbuka && banyakDipilih > 0" x-cloak class="max-h-52 overflow-y-auto border-b border-gray-100">
        <template x-for="item in rincianDipilih" :key="item.id">
            <div class="flex items-center gap-2 px-4 py-2 text-sm border-b border-gray-50 last:border-0">
                <span class="flex-1 min-w-0 truncate text-gray-700" x-text="item.label"></span>
                <span class="shrink-0 text-gray-400 tabular-nums" x-text="item.jumlah + '×'"></span>
                <span class="shrink-0 w-24 text-right tabular-nums text-gray-700"
                      x-text="item.subtotal === null ? 'menyusul' : rupiah(item.subtotal)"></span>

                {{-- Hapus satu baris tanpa perlu mencarinya lagi di daftar --}}
                <button
                    type="button"
                    @click="hapusPilihan(item.id)"
                    class="shrink-0 p-1 text-gray-300 hover:text-red-600 transition"
                    :aria-label="'Hapus ' + item.label + ' dari pesanan'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <div class="px-4 sm:px-5 py-3 flex items-center justify-between gap-4">
        {{--
            Ringkasannya sekaligus tombol buka-tutup rincian. type="button"
            wajib: tanpa itu, tombol di dalam form akan mengirim pesanan.
        --}}
        <button
            type="button"
            @click="rincianTerbuka = ! rincianTerbuka"
            :disabled="banyakDipilih === 0"
            class="min-w-0 text-left disabled:cursor-default"
            :aria-expanded="rincianTerbuka"
        >
            <span class="text-xs text-gray-500 inline-flex items-center gap-1">
                <span x-text="banyakDipilih">0</span> jenis produk dipilih

                <svg x-show="banyakDipilih > 0" x-cloak
                     class="h-3.5 w-3.5 transition-transform"
                     :class="rincianTerbuka ? 'rotate-180' : ''"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </span>

            <span class="block text-base font-semibold text-gray-900 truncate" x-text="rupiah(totalSementara)">Rp0</span>

            {{-- Produk fluktuatif tidak ikut dihitung karena harganya memang
                 belum ada sampai pengurus memverifikasi --}}
            <span x-show="adaHargaMenyusul" x-cloak class="block text-[11px] text-amber-600">
                Belum termasuk produk yang harganya menyusul
            </span>
        </button>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ $batal }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
            <x-primary-button>Kirim Pesanan</x-primary-button>
        </div>
    </div>
</div>
