// Input unggah berkas — dipakai komponen x-file-input
// (resources/views/components/file-input.blade.php) di form foto produk,
// foto profil anggota, dan bukti transfer.
//
// Tugasnya:
// 1. menampilkan nama berkas yang dipilih;
// 2. memperkecil foto yang terlalu besar SEBELUM dikirim;
// 3. (kalau diminta lewat :pratinjau) menampilkan pratinjau foto;
// 4. (kalau diminta lewat :potong, saat ini foto produk) membuka jendela
//    untuk mengatur potongan foto persegi, hitungannya di potong-foto.js.
//
// KENAPA DIPERKECIL: server menerima paling besar 2 MB, sedangkan foto kamera
// HP sekarang 3–8 MB. Anggota yang memotret struk ATM sebagai bukti transfer,
// atau memakai foto kamera untuk foto profil, akan ditolak. Memperkecil di
// browser juga membuat unggahan cepat di sinyal HP yang lemah.
//
// Berkas yang sudah di bawah batas TIDAK disentuh sama sekali. Kalau
// pengecilan/pemotongan gagal karena apa pun (browser lama, format yang tidak
// bisa dibaca), berkas aslinya yang dikirim dan server menilai seperti biasa.
// Bukti transfer sengaja TIDAK bisa dipotong: harus utuh untuk dicocokkan.

import Alpine from 'alpinejs';
import { geser, keadaanAwal, perbesar, potongKeBerkas, skala } from './potong-foto';

// Sedikit di bawah batas server (2 MB) supaya ada ruang.
const BATAS_BYTE = 1.8 * 1024 * 1024;
// Cukup tajam untuk dibaca pengurus, jauh lebih kecil dari foto kamera.
const SISI_TERPANJANG = 1600;
const MUTU_JPEG = 0.82;

Alpine.data('inputBerkas', ({ potong = false, pratinjau = false } = {}) => {
    // Disimpan di luar state Alpine: elemen gambar yang dibungkus proxy
    // reaktif Alpine ditolak oleh drawImage() di kanvas.
    let gambar = null;
    let berkasAsli = null;

    return {
        namaBerkas: '',
        sedangMemproses: false,

        // alamat sementara (blob:) untuk pratinjau kecil di samping tombol
        pratinjauUrl: '',

        // --- jendela potong
        potongTerbuka: false,
        fotoUrl: '',
        k: null, // keadaan potongan, lihat potong-foto.js
        titikSeret: null,

        async pilih(event) {
            const input = event.target;
            const berkas = input.files.length ? input.files[0] : null;

            this.namaBerkas = berkas ? berkas.name : '';
            this.gantiPratinjau(null);

            if (berkas && potong && berkas.type.startsWith('image/')) {
                await this.bukaPemotong(berkas, input);

                return;
            }

            await this.perkecilBila(berkas, input);

            if (pratinjau && input.files.length && input.files[0].type.startsWith('image/')) {
                this.gantiPratinjau(input.files[0]);
            }
        },

        async perkecilBila(berkas, input) {
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
                    gantiIsiInput(input, kecil);
                    this.namaBerkas = kecil.name;
                }
            } catch {
                // Biarkan berkas aslinya; server yang memberi tahu kalau terlalu besar.
            } finally {
                this.sedangMemproses = false;
                input.setCustomValidity('');
            }
        },

        // ---------------------------------------------------------- pratinjau

        gantiPratinjau(berkas) {
            if (this.pratinjauUrl) {
                URL.revokeObjectURL(this.pratinjauUrl);
            }
            this.pratinjauUrl = berkas ? URL.createObjectURL(berkas) : '';
        },

        // ------------------------------------------------------------- potong

        async bukaPemotong(berkas, input) {
            let dimuat;
            try {
                dimuat = await muatGambar(berkas);
            } catch {
                // Tidak bisa dibaca: kirim apa adanya, server yang menilai.
                return;
            }

            gambar = dimuat;
            berkasAsli = berkas;
            this.fotoUrl = URL.createObjectURL(berkas);
            // Form ditahan selama potongan belum dipakai atau dibatalkan.
            input.setCustomValidity('Atur potongan foto dulu, lalu tekan "Pakai foto ini".');
            this.potongTerbuka = true;

            // Ukuran bingkai baru bisa diukur setelah jendelanya tampil.
            await this.$nextTick();

            // Selama menunggu, jendelanya bisa sudah ditutup (Esc/Batal) atau
            // foto lain sudah dipilih. Terbukti di uji browser: Esc yang
            // ditekan sesaat setelah jendela muncul membuat gambar sudah
            // dikosongkan di sini. Kalau begitu, berhenti dengan tenang.
            if (gambar !== dimuat || !this.potongTerbuka) {
                return;
            }

            this.k = keadaanAwal(dimuat.naturalWidth, dimuat.naturalHeight, this.$refs.bingkai.clientWidth);
        },

        // Posisi & ukuran foto di dalam bingkai, untuk atribut style.
        get gayaFoto() {
            if (!this.k) {
                return '';
            }
            const s = skala(this.k);

            return `width:${this.k.lebarAsli * s}px;height:${this.k.tinggiAsli * s}px;`
                + `transform:translate(${this.k.x}px,${this.k.y}px)`;
        },

        mulaiGeser(event) {
            this.titikSeret = { x: event.clientX, y: event.clientY };
            // Gerakan jari/tetikus tetap diikuti walau keluar dari bingkai.
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        lanjutGeser(event) {
            if (!this.titikSeret || !this.k) {
                return;
            }
            this.k = geser(this.k, event.clientX - this.titikSeret.x, event.clientY - this.titikSeret.y);
            this.titikSeret = { x: event.clientX, y: event.clientY };
        },

        akhiriGeser() {
            this.titikSeret = null;
        },

        // Tombol panah keyboard, untuk yang tidak memakai tetikus/layar sentuh.
        geserTombol(dx, dy) {
            if (this.k) {
                this.k = geser(this.k, dx, dy);
            }
        },

        ubahPerbesar(nilai) {
            if (this.k) {
                this.k = perbesar(this.k, nilai);
            }
        },

        async pakai() {
            const input = this.$refs.input;

            // Tombol dinonaktifkan selama potongan belum siap, ini sekadar
            // jaring pengaman kalau tetap tertekan.
            if (!gambar || !this.k) {
                return;
            }

            this.sedangMemproses = true;

            try {
                const hasil = await potongKeBerkas(gambar, this.k, berkasAsli.name);

                if (hasil) {
                    gantiIsiInput(input, hasil);
                    this.namaBerkas = hasil.name;
                }
            } catch {
                // Biarkan berkas aslinya; server yang menilai.
            } finally {
                this.sedangMemproses = false;
                input.setCustomValidity('');
                this.tutupPemotong();
                this.gantiPratinjau(input.files.length ? input.files[0] : null);
            }
        },

        // "Batal": pilihan foto dibatalkan sama sekali, form kembali seperti
        // sebelum memilih berkas.
        batal() {
            const input = this.$refs.input;
            input.value = '';
            input.setCustomValidity('');
            this.namaBerkas = '';
            this.tutupPemotong();
            this.gantiPratinjau(null);
        },

        tutupPemotong() {
            this.potongTerbuka = false;
            if (this.fotoUrl) {
                URL.revokeObjectURL(this.fotoUrl);
            }
            this.fotoUrl = '';
            this.k = null;
            this.titikSeret = null;
            gambar = null;
            berkasAsli = null;
        },
    };
});

function gantiIsiInput(input, berkas) {
    const wadah = new DataTransfer();
    wadah.items.add(berkas);
    input.files = wadah.files;
}

async function perkecil(berkas) {
    const gambar = await muatGambar(berkas);
    const skalaKecil = Math.min(1, SISI_TERPANJANG / Math.max(gambar.width, gambar.height));

    const kanvas = document.createElement('canvas');
    kanvas.width = Math.round(gambar.width * skalaKecil);
    kanvas.height = Math.round(gambar.height * skalaKecil);

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
