{{--
    Latar dekoratif yang dipasang di belakang seluruh halaman.

    ARAH WARNA: hangat & ramah, bukan formal seperti situs bank.
    Sebelumnya dasarnya abu-abu (bg-gray-400) ditambah titik cahaya UNGU —
    kombinasi abu dingin + ungu itu yang bikin tampilannya terasa seperti
    lembaga keuangan. Sekarang dasarnya krem hangat, dan titik cahayanya
    dipilih dari keluarga warna hangat (hijau koperasi, kuning, jingga, hijau
    muda) — tidak ada lagi warna dingin.

    Posisinya 'fixed' (ikut kamera, bukan ikut scroll halaman), di belakang
    semua konten (-z-10), dan 'pointer-events-none' supaya tidak menghalangi
    klik ke elemen asli di atasnya.
--}}
<div class="no-print fixed inset-0 -z-10 overflow-hidden pointer-events-none bg-[#FAF6EF]">
    {{-- 4 titik cahaya statis di pojok layar. Yang kiri-atas hijau koperasi
         (warna identitas), sisanya warna hangat. Tanpa animasi & tanpa blur —
         kelembutannya murni dari radial-gradient yang memudar sendiri. --}}
    <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full opacity-50 bg-[radial-gradient(circle,#34d399_0%,transparent_70%)]"></div>
    <div class="absolute -top-20 -right-20 h-96 w-96 rounded-full opacity-45 bg-[radial-gradient(circle,#fcd34d_0%,transparent_70%)]"></div>
    <div class="absolute -bottom-24 -right-16 h-[26rem] w-[26rem] rounded-full opacity-40 bg-[radial-gradient(circle,#fdba74_0%,transparent_70%)]"></div>
    <div class="absolute -bottom-20 -left-20 h-80 w-80 rounded-full opacity-40 bg-[radial-gradient(circle,#bef264_0%,transparent_70%)]"></div>

    {{-- Pola titik-titik halus. Warnanya putih dengan opacity rendah supaya
         terbaca lembut di atas dasar krem — kalau terlalu pekat, latarnya
         malah ramai dan mengganggu isi halaman. --}}
    <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:24px_24px]"></div>
</div>
