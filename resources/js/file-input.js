// Input unggah berkas — dipakai komponen x-file-input
// (resources/views/components/file-input.blade.php) di form foto produk,
// foto profil anggota, dan bukti transfer.
//
// Tugasnya:
// 1. menampilkan nama berkas yang dipilih;
// 2. memperkecil foto yang terlalu besar SEBELUM dikirim;
// 3. (kalau diminta lewat :pratinjau) menampilkan pratinjau foto;
// 4. (kalau diminta lewat :potong, saat ini foto produk) membuka jendela
//    dengan kotak potong persegi yang sudutnya bisa ditarik; hitungannya di
//    potong-foto.js.
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
import { geserKotak, kotakAwal, potongKeBerkas, tarikSudut, tataLetak, ubahUkuranTengah } from './potong-foto';

// Sedikit di bawah batas server (2 MB) supaya ada ruang.
const BATAS_BYTE = 1.8 * 1024 * 1024;
// Cukup tajam untuk dibaca pengurus, jauh lebih kecil dari foto kamera.
const SISI_TERPANJANG = 1600;
const MUTU_JPEG = 0.82;

// Jarak geser/ubah ukuran per tekanan tombol keyboard (piksel layar).
const LANGKAH_TOMBOL = 10;

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
        t: null, // tata letak foto di dalam bingkai, lihat potong-foto.js
        kotak: null, // kotak potong { x, y, sisi }
        seret: null, // tarikan yang sedang berjalan: { jenis, x0, y0, kotak0 }

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

            this.t = tataLetak(dimuat.naturalWidth, dimuat.naturalHeight, this.$refs.bingkai.clientWidth);
            this.kotak = kotakAwal(this.t);
        },

        // Posisi & ukuran foto utuh di dalam bingkai, untuk atribut style.
        get gayaFoto() {
            if (!this.t) {
                return '';
            }

            return `left:${this.t.ox}px;top:${this.t.oy}px;width:${this.t.lebar}px;height:${this.t.tinggi}px`;
        },

        // Posisi & ukuran kotak potong, untuk atribut style.
        get gayaKotak() {
            if (!this.kotak) {
                return '';
            }

            return `left:${this.kotak.x}px;top:${this.kotak.y}px;width:${this.kotak.sisi}px;height:${this.kotak.sisi}px`;
        },

        // Titik jari/tetikus, relatif terhadap pojok kiri-atas bingkai.
        titikDi(event) {
            const kotakBingkai = this.$refs.bingkai.getBoundingClientRect();

            return { x: event.clientX - kotakBingkai.left, y: event.clientY - kotakBingkai.top };
        },

        // jenis: 'geser' (menarik bagian tengah kotak) atau nama sudut
        // ('kiri-atas', 'kanan-atas', 'kiri-bawah', 'kanan-bawah').
        mulaiSeret(event, jenis) {
            if (!this.kotak) {
                return;
            }
            const titik = this.titikDi(event);
            this.seret = { jenis, x0: titik.x, y0: titik.y, kotak0: { ...this.kotak } };
            // Gerakan tetap diikuti walau jari/tetikus keluar dari bingkai.
            this.$refs.bingkai.setPointerCapture(event.pointerId);
        },

        lanjutSeret(event) {
            if (!this.seret || !this.t) {
                return;
            }
            const titik = this.titikDi(event);
            const { jenis, x0, y0, kotak0 } = this.seret;

            this.kotak = jenis === 'geser'
                ? geserKotak(this.t, kotak0, titik.x - x0, titik.y - y0)
                : tarikSudut(this.t, kotak0, jenis, titik.x, titik.y);
        },

        akhiriSeret() {
            this.seret = null;
        },

        // Keyboard, untuk yang tidak memakai tetikus/layar sentuh:
        // panah = pindahkan kotak, + / − = ubah ukuran kotak.
        tombolKeyboard(event) {
            if (!this.kotak) {
                return;
            }
            const geser = {
                ArrowLeft: [-LANGKAH_TOMBOL, 0],
                ArrowRight: [LANGKAH_TOMBOL, 0],
                ArrowUp: [0, -LANGKAH_TOMBOL],
                ArrowDown: [0, LANGKAH_TOMBOL],
            }[event.key];

            if (geser) {
                event.preventDefault();
                this.kotak = geserKotak(this.t, this.kotak, ...geser);
            } else if (['+', '='].includes(event.key)) {
                event.preventDefault();
                this.kotak = ubahUkuranTengah(this.t, this.kotak, LANGKAH_TOMBOL);
            } else if (['-', '_'].includes(event.key)) {
                event.preventDefault();
                this.kotak = ubahUkuranTengah(this.t, this.kotak, -LANGKAH_TOMBOL);
            }
        },

        async pakai() {
            const input = this.$refs.input;

            // Tombol dinonaktifkan selama potongan belum siap, ini sekadar
            // jaring pengaman kalau tetap tertekan.
            if (!gambar || !this.kotak) {
                return;
            }

            this.sedangMemproses = true;

            try {
                const hasil = await potongKeBerkas(gambar, this.t, this.kotak, berkasAsli.name);

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
            this.t = null;
            this.kotak = null;
            this.seret = null;
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
