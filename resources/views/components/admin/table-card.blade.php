{{--
    Pembungkus tabel di halaman index admin (Anggota, Produk, Pesanan, dst).

    KENAPA KOMPONEN INI ADA — ini memperbaiki bug tampilan HP yang nyata:
    Kartu pembungkusnya pakai overflow-hidden supaya sudutnya tetap membulat,
    tapi itu memotong tabel yang lebih lebar dari layar, bukan bikin bisa
    digeser. Di HP kolom paling kanan yang berisi tombol Edit dan Hapus jadi
    tidak bisa dijangkau sama sekali.

    Aturan scroll-nya ditaruh di sini supaya tidak kelewat di halaman index baru.

    Slot: isi kartunya — biasanya @if(tabel) ... @else <x-admin.empty-state />.
--}}
<div {{ $attributes->merge(['class' => 'bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm overflow-hidden']) }}>
    {{--
        overflow-x-auto: tabel yang kelebaran bisa DIGESER ke samping, bukan
        dipotong. Kartu pembungkus di luar tetap `overflow-hidden` supaya
        sudut membulatnya tidak rusak.
    --}}
    <div class="overflow-x-auto">
        {{ $slot }}
    </div>
</div>
