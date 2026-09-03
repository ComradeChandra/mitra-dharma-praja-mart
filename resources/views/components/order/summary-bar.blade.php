{{--
    Bilah ringkasan yang menempel di bawah layar: berapa jenis produk yang
    sudah diisi, perkiraan totalnya, dan tombol kirim.

    Gunanya supaya tidak perlu menggulir sampai ujung cuma buat melihat sudah
    memilih apa saja dan menekan kirim.

    SENGAJA TIDAK diberi x-cloak, beda dari filter-bar. Tombol kirimnya ada di
    sini, jadi kalau bilah ini disembunyikan sampai Alpine selesai dimuat,
    halaman tanpa JavaScript jadi tidak punya tombol kirim sama sekali. Angka
    di dalamnya diisi nilai awal dari server ("0", "Rp0") yang nanti ditimpa
    Alpine, jadi tampilannya tetap masuk akal tanpa JavaScript.

    Props:
    - batal : URL tombol "Batal"
--}}
@props(['batal'])

{{-- Di HP bilah ini menempel di dasar layar supaya tombol kirim selalu
     terjangkau tanpa menggulir sampai ujung. Di layar lebar dia ikut masuk
     kolom kanan bersama "Cara Terima Barang", jadi tidak perlu menempel
     lagi — kolomnya sendiri yang menempel. --}}
<div class="sticky bottom-0 z-30 -mx-4 sm:mx-0 mt-6 border-t border-gray-200 bg-white/95 backdrop-blur-sm shadow-[0_-4px_12px_-6px_rgba(0,0,0,0.12)] lg:static lg:mt-0 lg:mx-0 lg:rounded-xl lg:border lg:border-gray-100 lg:shadow-sm">
    <div class="px-4 sm:px-5 py-3 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs text-gray-500">
                <span x-text="banyakDipilih">0</span> jenis produk dipilih
            </p>

            <p class="text-base font-semibold text-gray-900 truncate">
                <span x-text="rupiah(totalSementara)">Rp0</span>
            </p>

            {{-- Produk fluktuatif tidak ikut dihitung karena harganya memang
                 belum ada sampai pengurus memverifikasi --}}
            <p x-show="adaHargaMenyusul" x-cloak class="text-[11px] text-amber-600">
                Belum termasuk produk yang harganya menyusul
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ $batal }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
            <x-primary-button>Kirim Pesanan</x-primary-button>
        </div>
    </div>
</div>
