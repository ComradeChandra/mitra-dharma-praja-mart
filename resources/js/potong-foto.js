// Hitungan & pemotongan foto PERSEGI — dipakai resources/js/file-input.js
// untuk komponen x-file-input yang diberi :potong="true" (saat ini foto
// produk, yang di katalog memang ditampilkan persegi).
//
// Cara kerjanya seperti alat crop di HP: foto tampil UTUH di dalam bingkai,
// lalu ada KOTAK POTONG persegi di atasnya. Sudut kotak ditarik untuk mengubah
// ukurannya, bagian tengahnya ditarik untuk memindahkannya.
//
// Dipisah dari file-input.js supaya hitungannya bisa diuji tanpa browser
// (tests/js/potong-foto.test.mjs). Semua ukuran dalam piksel layar relatif
// terhadap bingkai, kecuali yang disebut "asli" (piksel foto sebenarnya).
//
// - t (tata letak): di mana foto digambar di dalam bingkai
//     { lebarAsli, tinggiAsli, skala, ox, oy, lebar, tinggi }
// - kotak: kotak potong { x, y, sisi }

// Sisi foto hasil potongan. Cukup tajam untuk kartu katalog dan halaman
// detail, dan jauh di bawah batas unggah server (2 MB).
export const SISI_HASIL = 800;

// Kotak terkecil (piksel layar): lebih kecil dari ini pegangan sudutnya
// saling tumpuk dan susah ditarik di layar HP.
export const KOTAK_TERKECIL = 48;

const MUTU_JPEG = 0.85;

/** Foto digambar utuh di tengah bingkai persegi (seperti object-contain). */
export function tataLetak(lebarAsli, tinggiAsli, sisiBingkai) {
    const skala = Math.min(sisiBingkai / lebarAsli, sisiBingkai / tinggiAsli);
    const lebar = lebarAsli * skala;
    const tinggi = tinggiAsli * skala;

    return {
        lebarAsli,
        tinggiAsli,
        skala,
        lebar,
        tinggi,
        ox: (sisiBingkai - lebar) / 2,
        oy: (sisiBingkai - tinggi) / 2,
    };
}

/** Batas ukuran kotak: tidak boleh melebihi sisi foto yang lebih pendek. */
function batasUkuran(t) {
    const maks = Math.min(t.lebar, t.tinggi);

    return { min: Math.min(KOTAK_TERKECIL, maks), maks };
}

const jepit = (nilai, bawah, atas) => Math.min(atas, Math.max(bawah, nilai));

/** Kotak tidak boleh keluar dari foto. */
function dalamFoto(t, kotak) {
    return {
        sisi: kotak.sisi,
        x: jepit(kotak.x, t.ox, t.ox + t.lebar - kotak.sisi),
        y: jepit(kotak.y, t.oy, t.oy + t.tinggi - kotak.sisi),
    };
}

/** Kotak awal: persegi terbesar yang muat, di tengah foto. */
export function kotakAwal(t) {
    const sisi = batasUkuran(t).maks;

    return {
        sisi,
        x: t.ox + (t.lebar - sisi) / 2,
        y: t.oy + (t.tinggi - sisi) / 2,
    };
}

/** Pindahkan kotak sejauh dx, dy (dari posisi kotak saat mulai ditarik). */
export function geserKotak(t, kotak, dx, dy) {
    return dalamFoto(t, { ...kotak, x: kotak.x + dx, y: kotak.y + dy });
}

/**
 * Tarik salah satu sudut ke titik (px, py). Sudut SEBERANGNYA jadi jangkar
 * dan tidak bergerak. Kotak tetap persegi: sisinya mengikuti arah tarikan
 * yang lebih jauh, lalu dijepit supaya tidak keluar dari foto.
 *
 * sudut: 'kiri-atas' | 'kanan-atas' | 'kiri-bawah' | 'kanan-bawah'
 * awal : kotak saat sudut mulai ditarik
 */
export function tarikSudut(t, awal, sudut, px, py) {
    const kiri = sudut.startsWith('kiri');
    const atas = sudut.endsWith('atas');

    // Jangkar = sudut seberang dari yang ditarik.
    const ax = kiri ? awal.x + awal.sisi : awal.x;
    const ay = atas ? awal.y + awal.sisi : awal.y;

    // Seberapa jauh dari jangkar ke titik tarik (positif = kotak membesar).
    const mauX = kiri ? ax - px : px - ax;
    const mauY = atas ? ay - py : py - ay;

    // Ruang yang tersedia dari jangkar sampai tepi foto.
    const ruangX = kiri ? ax - t.ox : t.ox + t.lebar - ax;
    const ruangY = atas ? ay - t.oy : t.oy + t.tinggi - ay;

    const { min } = batasUkuran(t);
    const maks = Math.min(ruangX, ruangY);
    const sisi = jepit(Math.max(mauX, mauY), Math.min(min, maks), maks);

    return {
        sisi,
        x: kiri ? ax - sisi : ax,
        y: atas ? ay - sisi : ay,
    };
}

/** Ubah ukuran kotak dengan titik tengahnya tetap (untuk tombol + dan −). */
export function ubahUkuranTengah(t, kotak, selisih) {
    const { min, maks } = batasUkuran(t);
    const sisi = jepit(kotak.sisi + selisih, min, maks);
    const tengahX = kotak.x + kotak.sisi / 2;
    const tengahY = kotak.y + kotak.sisi / 2;

    return dalamFoto(t, { sisi, x: tengahX - sisi / 2, y: tengahY - sisi / 2 });
}

/** Bagian foto ASLI (piksel asli) yang ada di dalam kotak potong. */
export function areaSumber(t, kotak) {
    return {
        x: (kotak.x - t.ox) / t.skala,
        y: (kotak.y - t.oy) / t.skala,
        sisi: kotak.sisi / t.skala,
    };
}

/** Potong gambar sesuai kotak, hasilnya berkas JPEG persegi. */
export async function potongKeBerkas(gambar, t, kotak, namaAsli) {
    const area = areaSumber(t, kotak);
    // Foto yang sudah kecil tidak diperbesar (cuma jadi buram).
    const sisi = Math.max(1, Math.round(Math.min(SISI_HASIL, area.sisi)));

    const kanvas = document.createElement('canvas');
    kanvas.width = sisi;
    kanvas.height = sisi;

    const konteks = kanvas.getContext('2d');
    // Latar putih: PNG transparan yang dijadikan JPEG kalau tidak begini jadi hitam.
    konteks.fillStyle = '#ffffff';
    konteks.fillRect(0, 0, sisi, sisi);
    konteks.drawImage(gambar, area.x, area.y, area.sisi, area.sisi, 0, 0, sisi, sisi);

    const blob = await new Promise((selesai) => kanvas.toBlob(selesai, 'image/jpeg', MUTU_JPEG));

    if (!blob) {
        return null;
    }

    const nama = namaAsli.replace(/\.[^.]+$/, '') + '.jpg';

    return new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() });
}
