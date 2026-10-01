// Hitungan & pemotongan foto PERSEGI — dipakai resources/js/file-input.js
// untuk komponen x-file-input yang diberi :potong="true" (saat ini foto
// produk, yang di katalog memang ditampilkan persegi).
//
// Dipisah dari file-input.js supaya hitungannya bisa diuji tanpa browser
// (tests/js/potong-foto.test.mjs).
//
// "Keadaan" (k) menyimpan posisi foto di dalam bingkai:
// - x, y        : letak pojok kiri-atas foto terhadap bingkai (piksel layar,
//                 selalu <= 0 karena foto selalu menutupi bingkai)
// - skalaDasar  : skala supaya foto pas menutupi bingkai (seperti object-cover)
// - perbesar    : pengali dari penggeser, 1 sampai PERBESAR_MAKS

export const PERBESAR_MAKS = 3;

// Sisi foto hasil potongan. Cukup tajam untuk kartu katalog dan halaman
// detail, dan jauh di bawah batas unggah server (2 MB).
export const SISI_HASIL = 800;
const MUTU_JPEG = 0.85;

/** Keadaan awal: foto menutupi bingkai penuh dan berada di tengah. */
export function keadaanAwal(lebarAsli, tinggiAsli, sisiBingkai) {
    const skalaDasar = sisiBingkai / Math.min(lebarAsli, tinggiAsli);

    return batasi({
        lebarAsli,
        tinggiAsli,
        sisiBingkai,
        skalaDasar,
        perbesar: 1,
        x: (sisiBingkai - lebarAsli * skalaDasar) / 2,
        y: (sisiBingkai - tinggiAsli * skalaDasar) / 2,
    });
}

/** Skala tampilan saat ini (piksel layar per piksel asli). */
export function skala(k) {
    return k.skalaDasar * k.perbesar;
}

/**
 * Foto tidak boleh digeser sampai bingkai memperlihatkan ruang kosong:
 * pojok kiri-atas foto dijaga di antara (sisiBingkai - ukuranFoto) dan 0.
 */
export function batasi(k) {
    const lebar = k.lebarAsli * skala(k);
    const tinggi = k.tinggiAsli * skala(k);

    return {
        ...k,
        x: Math.min(0, Math.max(k.sisiBingkai - lebar, k.x)),
        y: Math.min(0, Math.max(k.sisiBingkai - tinggi, k.y)),
    };
}

/** Geser foto sejauh dx, dy piksel layar. */
export function geser(k, dx, dy) {
    return batasi({ ...k, x: k.x + dx, y: k.y + dy });
}

/** Ubah perbesaran; titik tengah bingkai tetap menunjuk bagian foto yang sama. */
export function perbesar(k, nilai) {
    const baru = Math.min(PERBESAR_MAKS, Math.max(1, Number(nilai) || 1));
    const rasio = baru / k.perbesar;
    const tengah = k.sisiBingkai / 2;

    return batasi({
        ...k,
        perbesar: baru,
        x: tengah - (tengah - k.x) * rasio,
        y: tengah - (tengah - k.y) * rasio,
    });
}

/** Bagian foto ASLI (piksel asli) yang sedang terlihat di dalam bingkai. */
export function areaSumber(k) {
    const s = skala(k);

    return { x: -k.x / s, y: -k.y / s, sisi: k.sisiBingkai / s };
}

/** Potong gambar sesuai bingkai, hasilnya berkas JPEG persegi. */
export async function potongKeBerkas(gambar, k, namaAsli) {
    const area = areaSumber(k);
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
