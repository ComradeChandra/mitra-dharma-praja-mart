// Penyaring + ringkasan di form pemesanan — dipakai di halaman "Pesan Produk"
// milik anggota (member/orders/create.blade.php) dan non-anggota
// (non-member/orders/create.blade.php).
//
// Semua produk tetap dirender server seperti biasa; file ini cuma
// menyembunyikan/menampilkan baris yang tidak cocok, menghitung ringkasan di
// bawah, dan membuka jendela konfirmasi sebelum kirim. Jadi kalau JavaScript
// mati, halamannya masih utuh dan tetap bisa dipakai memesan, cuma tanpa
// fitur cari, ringkasan, dan konfirmasi.

import Alpine from 'alpinejs';

Alpine.data('formPesan', () => ({
    // --- keadaan penyaring
    cari: '',
    kategori: 'semua',
    hanyaDipilih: false,

    // Rincian pilihan di bilah ringkasan sedang dibuka atau tidak.
    rincianTerbuka: false,

    // --- keadaan isian
    // jumlah: { idProduk: angka }, terhubung ke input lewat x-model
    jumlah: {},
    // harga: { idProduk: angka|null }, null berarti produk fluktuatif yang
    // harganya baru dikunci pengurus saat verifikasi
    harga: {},
    // katalog: salinan ringkas semua produk di halaman ini, dipakai buat tahu
    // apakah pencarian sama sekali tidak menemukan apa-apa
    katalog: [],

    // --- jendela konfirmasi sebelum kirim (x-order.confirm-dialog)
    konfirmasiTerbuka: false,
    // true sesaat setelah "Ya, kirim pesanan" ditekan, supaya periksaDulu()
    // tidak membuka jendelanya lagi waktu form benar-benar dikirim.
    sudahDikonfirmasi: false,
    // cara terima & identitas pemesan, dibaca dari form saat jendela dibuka
    ringkasKirim: { cara: '', butuhAlamat: false, alamat: '', nama: '', wa: '' },
    // Kapan jendela konfirmasi terakhir dibuka. Di HP jendelanya muncul dari
    // bawah, tepat di posisi tombol "Kirim Pesanan", jadi ketukan kedua dari
    // ketukan ganda bisa jatuh di "Ya, kirim pesanan" dan langsung mengirim
    // tanpa sempat dibaca. Klik yang datang sesaat setelah jendela muncul
    // diabaikan (lihat baruSajaDibuka()).
    dibukaPada: 0,

    init() {
        // Browser bisa memulihkan halaman ini utuh dari cache waktu orang
        // menekan "kembali" setelah mengirim, lengkap dengan angka yang
        // terakhir diisi. Form pesan selalu dimulai bersih, supaya barang dari
        // pesanan sebelumnya tidak ikut terkirim lagi tanpa disadari.
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                this.kosongkan();
            }
        });
    },

    /**
     * Dipanggil sekali per baris produk lewat x-init. Tiap baris mendaftarkan
     * dirinya sendiri, jadi halaman induknya tidak perlu menyuntik satu blok
     * JSON besar berisi seluruh katalog.
     */
    daftar({ id, nama, label, kategori, harga, awal }) {
        this.harga[id] = harga;
        // nama versi huruf kecil dipakai buat mencocokkan pencarian, label
        // versi aslinya dipakai waktu menampilkan rincian pilihan.
        this.katalog.push({ id, nama, label, kategori });

        if (this.jumlah[id] === undefined) {
            // "awal" berisi nilai old() dari server. Kalau pengiriman ditolak
            // validasi, angka yang sudah diketik orang harus tetap ada di
            // kotaknya, bukan balik ke nol.
            this.jumlah[id] = awal;
        }
    },

    /**
     * Apakah satu baris produk lolos penyaring yang sedang aktif?
     *
     * Nama dan kategori dioper langsung dari Blade, tidak diambil dari daftar
     * hasil daftar() di atas. Alasannya x-show bisa dievaluasi sebelum x-init
     * baris itu jalan, dan kalau menunggu daftarnya terisi, baris bisa
     * berkedip hilang sebentar saat halaman dibuka.
     */
    cocok(nama, kategori, id) {
        if (this.kategori !== 'semua' && this.kategori !== kategori) {
            return false;
        }

        if (this.hanyaDipilih && ! (this.jumlah[id] > 0)) {
            return false;
        }

        const kata = this.cari.trim().toLowerCase();

        if (kata === '') {
            return true;
        }

        return nama.includes(kata) || kategori.toLowerCase().includes(kata);
    },

    /**
     * Judul kategori ikut disembunyikan kalau semua produk di bawahnya
     * tersaring habis — kalau tidak, yang tersisa cuma deretan judul kosong.
     */
    adaIsinya(daftarProduk, kategori) {
        return daftarProduk.some((p) => this.cocok(p.nama, kategori, p.id));
    },

    /**
     * Rincian apa saja yang sudah diisi, buat ditampilkan di bilah ringkasan.
     *
     * Ini BUKAN keranjang belanja. Di rapat, keranjang ditolak: alurnya isi
     * jumlah langsung di barisnya, lalu lihat totalnya, lalu konfirmasi. Yang
     * ditambahkan di sini cuma cara melihat kembali apa yang sudah diisi,
     * tanpa harus menyaring daftarnya dulu.
     */
    get rincianDipilih() {
        return this.katalog
            .filter((p) => this.jumlah[p.id] > 0)
            .map((p) => ({
                id: p.id,
                label: p.label,
                jumlah: this.jumlah[p.id],
                harga: this.harga[p.id],
                subtotal: this.harga[p.id] === null || this.harga[p.id] === undefined
                    ? null
                    : this.jumlah[p.id] * this.harga[p.id],
            }));
    },

    /** Kosongkan satu baris dari ringkasan, tanpa perlu mencarinya lagi. */
    hapusPilihan(id) {
        this.jumlah[id] = 0;
    },

    /**
     * Dipanggil setiap kali form pesan akan terkirim. Kiriman pertama ditahan
     * dan diganti jendela konfirmasi; baru setelah "Ya, kirim pesanan" form
     * benar-benar dikirim lewat kirimSekarang().
     */
    periksaDulu(event) {
        if (this.sudahDikonfirmasi) {
            return;
        }

        // Belum ada yang diisi: biarkan terkirim supaya server yang menjawab
        // "pilih minimal satu produk", sama seperti kalau JavaScript mati.
        if (this.banyakDipilih === 0) {
            return;
        }

        event.preventDefault();
        this.ringkasKirim = this.bacaRingkasKirim(event.target);
        this.konfirmasiTerbuka = true;
        this.dibukaPada = Date.now();
        this.$nextTick(() => this.$refs.tombolKirimFinal?.focus());
    },

    /**
     * Cara terima dan identitas tidak disimpan di komponen ini (dipegang
     * x-order.delivery-picker dan kolom identitas non-anggota), jadi dibaca
     * langsung dari isian form saat jendela konfirmasi dibuka.
     */
    bacaRingkasKirim(form) {
        const cara = form.querySelector('input[name="delivery_method"]:checked');
        const nilai = (nama) => (form.querySelector(`[name="${nama}"]`)?.value ?? '').trim();

        return {
            cara: cara ? cara.dataset.label : '',
            butuhAlamat: cara ? cara.dataset.butuhAlamat === '1' : false,
            alamat: nilai('delivery_address'),
            nama: nilai('non_member_name'),
            wa: nilai('whatsapp_number'),
        };
    },

    /** Jendela konfirmasi baru muncul kurang dari setengah detik lalu. */
    baruSajaDibuka() {
        return Date.now() - this.dibukaPada < 500;
    },

    tutupKonfirmasi() {
        if (this.baruSajaDibuka()) {
            return;
        }

        this.konfirmasiTerbuka = false;
    },

    kirimSekarang() {
        if (this.baruSajaDibuka()) {
            return;
        }

        const form = this.$refs.formPesan;

        this.sudahDikonfirmasi = true;
        this.konfirmasiTerbuka = false;

        // requestSubmit(), bukan submit(): yang pertama ikut memicu event
        // submit, jadi penahan kirim ganda (cegah-kirim-ganda.js) tetap jalan.
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }

        // Pengirimannya bisa tertahan browser, mis. kolom wajib belum diisi.
        // Kalau begitu, percobaan berikutnya harus lewat konfirmasi lagi.
        this.sudahDikonfirmasi = false;
    },

    /** Kembalikan semua jumlah ke nol dan tutup jendela yang terbuka. */
    kosongkan() {
        Object.keys(this.jumlah).forEach((id) => {
            this.jumlah[id] = 0;
        });
        this.konfirmasiTerbuka = false;
        this.sudahDikonfirmasi = false;
        this.rincianTerbuka = false;
    },

    /** Berapa jenis produk yang jumlahnya sudah diisi. */
    get banyakDipilih() {
        return Object.values(this.jumlah).filter((n) => n > 0).length;
    },

    /**
     * Total sementara. Produk fluktuatif tidak ikut dijumlah karena harganya
     * memang belum ada — itu diberi tahu terpisah lewat adaHargaMenyusul().
     */
    get totalSementara() {
        return Object.entries(this.jumlah).reduce((total, [id, banyak]) => {
            const h = this.harga[id];

            return banyak > 0 && h !== null && h !== undefined
                ? total + banyak * h
                : total;
        }, 0);
    },

    /** Ada produk fluktuatif yang dipilih, jadi total di atas belum final. */
    get adaHargaMenyusul() {
        return Object.entries(this.jumlah).some(
            ([id, banyak]) => banyak > 0 && (this.harga[id] === null || this.harga[id] === undefined),
        );
    },

    /**
     * Semua produk tersaring habis. Dipakai buat menampilkan pesan "tidak
     * ditemukan", bukan kartu kosong tanpa keterangan.
     */
    get tidakAdaHasil() {
        return this.katalog.length > 0
            && ! this.katalog.some((p) => this.cocok(p.nama, p.kategori, p.id));
    },

    /** Penyaring sedang aktif, dipakai buat menampilkan tombol "atur ulang". */
    get sedangMenyaring() {
        return this.cari.trim() !== '' || this.kategori !== 'semua' || this.hanyaDipilih;
    },

    aturUlang() {
        this.cari = '';
        this.kategori = 'semua';
        this.hanyaDipilih = false;
    },

    rupiah(angka) {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(angka);
    },
}));
