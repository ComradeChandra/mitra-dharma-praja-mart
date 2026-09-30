// Notifikasi pesanan baru di halaman pengurus. Dipakai komponen
// x-admin.notif-pesanan, yang dipasang di layouts/app.blade.php, jadi aktif di
// SEMUA halaman admin.
//
// KENAPA "bertanya berkala" (polling), bukan koneksi live (WebSocket/SSE):
// koneksi live menahan satu proses PHP per tab admin yang terbuka, dan shared
// hosting membatasi jumlah proses yang boleh jalan bersamaan. Polling cuma
// permintaan singkat yang langsung selesai, dan jawabannya cuma dua angka.
//
// Cara kerjanya:
// - Saat halaman dibuka, id pesanan terbaru dicatat sebagai titik awal.
// - Tiap jeda, tanyakan berapa pesanan (yang belum dibatalkan) masuk setelah
//   titik awal itu. Kalau ada, tampilkan kotak notifikasi + angka di judul tab.
// - "Tutup" memindahkan titik awal ke pesanan terbaru. "Lihat pesanan" membuka
//   halaman baru, dan halaman baru mulai dengan titik awal baru.
// - Tab sedang tidak dilihat: jedanya diperpanjang (hemat), tapi tetap jalan
//   supaya angka di judul tab tetap memberi tahu. Begitu tab dilihat lagi,
//   langsung dicek.
// - Sesi habis / akun dinonaktifkan: berhenti diam-diam. Gangguan koneksi
//   sesaat: diamkan, coba lagi di putaran berikutnya.

import Alpine from 'alpinejs';

Alpine.data('notifPesanan', ({ url, jedaMs = 10000, jedaLatarMs = 30000 }) => ({
    // id pesanan terbaru yang sudah "diketahui"; null = belum pernah dicek
    sejak: null,
    // id pesanan terbaru dari jawaban server terakhir
    terakhir: null,
    // jumlah pesanan baru sejak titik awal
    baru: 0,

    judulAsli: document.title,
    pewaktu: null,
    sedangCek: false,
    berhenti: false,

    init() {
        this.cek().then(() => this.jadwalkan());

        // Tab kembali dilihat: cek sekarang juga, lalu kembali ke jeda normal.
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.cek();
            }
            this.jadwalkan();
        });
    },

    // setTimeout berantai, bukan setInterval: pengecekan berikutnya baru
    // dijadwalkan setelah yang sekarang selesai, jadi kalau server sedang
    // lambat permintaannya tidak menumpuk.
    jadwalkan() {
        clearTimeout(this.pewaktu);
        if (this.berhenti) return;

        const jeda = document.hidden ? jedaLatarMs : jedaMs;
        this.pewaktu = setTimeout(async () => {
            await this.cek();
            this.jadwalkan();
        }, jeda);
    },

    async cek() {
        if (this.berhenti || this.sedangCek) return;
        this.sedangCek = true;

        try {
            const alamat = this.sejak === null ? url : `${url}?sejak=${this.sejak}`;
            const jawaban = await fetch(alamat, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            // 401 = sesi habis; dialihkan = akun dinonaktifkan dan dikeluarkan
            // (middleware admin.aktif). Tidak ada gunanya terus bertanya.
            if (jawaban.status === 401 || jawaban.status === 419 || jawaban.redirected) {
                this.berhenti = true;
                return;
            }
            if (!jawaban.ok) return;

            const data = await jawaban.json();
            this.terakhir = data.terakhir;

            // Pengecekan pertama cuma menentukan titik awal.
            if (this.sejak === null) {
                this.sejak = data.terakhir;
                return;
            }

            this.baru = data.baru;
            this.perbaruiJudul();
        } catch {
            // Koneksi putus sesaat: diamkan, coba lagi di putaran berikutnya.
        } finally {
            this.sedangCek = false;
        }
    },

    // Angka di judul tab, misalnya "(2) Pesanan Masuk", supaya kelihatan
    // walaupun pengurus sedang membuka tab lain.
    perbaruiJudul() {
        document.title = this.baru > 0 ? `(${this.baru}) ${this.judulAsli}` : this.judulAsli;
    },

    // Tombol "Tutup": pesanan yang sudah diberitahukan dianggap sudah diketahui.
    tutup() {
        this.sejak = this.terakhir;
        this.baru = 0;
        this.perbaruiJudul();
    },
}));
