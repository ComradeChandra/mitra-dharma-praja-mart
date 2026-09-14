// Input unggah berkas — dipakai komponen x-file-input
// (resources/views/components/file-input.blade.php) di form foto produk,
// foto profil anggota, dan bukti transfer.
//
// Dua tugas: menampilkan nama berkas yang dipilih, dan memperkecil foto yang
// terlalu besar SEBELUM dikirim.
//
// KENAPA DIPERKECIL: server menerima paling besar 2 MB, sedangkan foto kamera
// HP sekarang 3–8 MB. Anggota yang memotret struk ATM sebagai bukti transfer,
// atau memakai foto kamera untuk foto profil, akan ditolak. Memperkecil di
// browser juga membuat unggahan cepat di sinyal HP yang lemah.
//
// Berkas yang sudah di bawah batas TIDAK disentuh sama sekali. Kalau
// pengecilan gagal karena apa pun (browser lama, format yang tidak bisa
// dibaca), berkas aslinya yang dikirim dan server menilai seperti biasa.

import Alpine from 'alpinejs';

// Sedikit di bawah batas server (2 MB) supaya ada ruang.
const BATAS_BYTE = 1.8 * 1024 * 1024;
// Cukup tajam untuk dibaca pengurus, jauh lebih kecil dari foto kamera.
const SISI_TERPANJANG = 1600;
const MUTU_JPEG = 0.82;

Alpine.data('inputBerkas', () => ({
    namaBerkas: '',
    sedangMemproses: false,

    async pilih(event) {
        const input = event.target;
        const berkas = input.files.length ? input.files[0] : null;

        this.namaBerkas = berkas ? berkas.name : '';

        if (!berkas || berkas.size <= BATAS_BYTE || !berkas.type.startsWith('image/')) {
            return;
        }

        this.sedangMemproses = true;

        // Selama foto diperkecil (di HP bisa sepersekian detik), form ditahan
        // browser: tanpa ini, orang yang langsung menekan tombol kirim akan
        // mengirim foto aslinya yang besar, lalu ditolak server.
        input.setCustomValidity('Sebentar, foto sedang diperkecil.');

        try {
            const kecil = await perkecil(berkas);

            if (kecil && kecil.size < berkas.size) {
                // Isi input diganti berkas yang sudah diperkecil. Ini tidak
                // memicu event change lagi, jadi tidak berulang.
                const wadah = new DataTransfer();
                wadah.items.add(kecil);
                input.files = wadah.files;
                this.namaBerkas = kecil.name;
            }
        } catch {
            // Biarkan berkas aslinya; server yang memberi tahu kalau terlalu besar.
        } finally {
            this.sedangMemproses = false;
            input.setCustomValidity('');
        }
    },
}));

async function perkecil(berkas) {
    const gambar = await muatGambar(berkas);
    const skala = Math.min(1, SISI_TERPANJANG / Math.max(gambar.width, gambar.height));

    const kanvas = document.createElement('canvas');
    kanvas.width = Math.round(gambar.width * skala);
    kanvas.height = Math.round(gambar.height * skala);

    const konteks = kanvas.getContext('2d');
    // Latar putih: PNG transparan yang dijadikan JPEG kalau tidak begini jadi hitam.
    konteks.fillStyle = '#ffffff';
    konteks.fillRect(0, 0, kanvas.width, kanvas.height);
    konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

    const blob = await new Promise((selesai) => kanvas.toBlob(selesai, 'image/jpeg', MUTU_JPEG));

    if (!blob) {
        return null;
    }

    const nama = berkas.name.replace(/\.[^.]+$/, '') + '.jpg';

    return new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() });
}

function muatGambar(berkas) {
    return new Promise((selesai, gagal) => {
        const alamat = URL.createObjectURL(berkas);
        const gambar = new Image();

        gambar.onload = () => {
            URL.revokeObjectURL(alamat);
            selesai(gambar);
        };
        gambar.onerror = () => {
            URL.revokeObjectURL(alamat);
            gagal(new Error('gambar tidak bisa dibaca'));
        };
        gambar.src = alamat;
    });
}
