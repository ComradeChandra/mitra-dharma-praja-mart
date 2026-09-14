// Uji logika penyaring & ringkasan di form pemesanan
// (resources/js/order-form.js), dijalankan tanpa browser.
//
// Cara menjalankan, dari folder utama project:
//     npm run test:js
//
// Sengaja tidak memakai pustaka pengujian apa pun supaya tidak menambah
// dependensi. Isi Alpine.data(...) diambil sebagai teks dari berkasnya lalu
// dijalankan sebagai objek biasa, jadi yang diuji memang kode yang dipakai.
import { readFileSync } from 'fs';

const src = readFileSync('resources/js/order-form.js', 'utf8');
const mulai = src.indexOf('() => ({');
const selesai = src.lastIndexOf('}));');
const badan = src.slice(mulai + 7, selesai + 1);

const buat = new Function('return ' + badan)();
// getter perlu diikat ke objeknya sendiri
const k = Object.create(Object.getPrototypeOf(buat));
Object.defineProperties(k, Object.getOwnPropertyDescriptors(buat));

let lulus = 0, gagal = 0;
const cek = (nama, dapat, harap) => {
    const ok = JSON.stringify(dapat) === JSON.stringify(harap);
    ok ? lulus++ : gagal++;
    console.log(`  ${ok ? 'OK  ' : 'GAGAL'} ${nama}${ok ? '' : ` -> dapat ${JSON.stringify(dapat)}, harap ${JSON.stringify(harap)}`}`);
};

k.daftar({ id: 1, nama: 'beras pandan wangi', label: 'Beras Pandan Wangi', kategori: 'Sembako', harga: 72000, awal: 0 });
k.daftar({ id: 2, nama: 'gula pasir 1kg',     label: 'Gula Pasir 1kg',     kategori: 'Sembako', harga: 17000, awal: 0 });
k.daftar({ id: 3, nama: 'telur ayam 1kg',     label: 'Telur Ayam 1kg',     kategori: 'Sayur',   harga: null,  awal: 0 });

cek('tanpa penyaring, semua tampil', [1,2,3].map(i => k.cocok(k.katalog[i-1].nama, k.katalog[i-1].kategori, i)), [true,true,true]);

k.cari = 'beras';
cek('cari "beras" cuma kena beras', [k.cocok('beras pandan wangi','Sembako',1), k.cocok('gula pasir 1kg','Sembako',2)], [true,false]);

k.cari = 'sembako';
cek('cari bisa lewat nama kategori', k.cocok('gula pasir 1kg','Sembako',2), true);

k.cari = '';
k.kategori = 'Sayur';
cek('tab kategori menyaring', [k.cocok('beras pandan wangi','Sembako',1), k.cocok('telur ayam 1kg','Sayur',3)], [false,true]);

k.kategori = 'semua';
k.jumlah[1] = 2;
k.jumlah[3] = 1;
cek('banyakDipilih', k.banyakDipilih, 2);
cek('total abaikan produk fluktuatif', k.totalSementara, 144000);
cek('tandai ada harga menyusul', k.adaHargaMenyusul, true);

k.hanyaDipilih = true;
cek('"yang saya isi saja" menyaring', [k.cocok('beras pandan wangi','Sembako',1), k.cocok('gula pasir 1kg','Sembako',2)], [true,false]);

k.hanyaDipilih = false;
k.cari = 'zzz';
cek('tidak ada hasil terdeteksi', k.tidakAdaHasil, true);
cek('judul kategori ikut hilang', k.adaIsinya([{id:1,nama:'beras pandan wangi'}], 'Sembako'), false);

k.aturUlang();
cek('atur ulang mengosongkan penyaring', [k.cari, k.kategori, k.hanyaDipilih], ['','semua',false]);
cek('atur ulang TIDAK menghapus isian', k.banyakDipilih, 2);
cek('format rupiah', k.rupiah(144000).replace(/ /g,' '), 'Rp144.000');

// --- rincian pilihan, dipakai bilah ringkasan yang bisa dibuka.
//     Ini BUKAN keranjang: jumlah tetap diisi langsung di baris produknya,
//     yang diuji di sini cuma cara melihat kembali apa yang sudah diisi.
k.cari = ''; k.kategori = 'semua'; k.hanyaDipilih = false;
k.jumlah[1] = 2; k.jumlah[2] = 0; k.jumlah[3] = 1;

cek('rincian cuma memuat yang sudah diisi', k.rincianDipilih.map((r) => r.label), ['Beras Pandan Wangi', 'Telur Ayam 1kg']);
cek('subtotal tiap baris dihitung', k.rincianDipilih.find((r) => r.id === 1).subtotal, 144000);
cek('produk fluktuatif tanpa subtotal', k.rincianDipilih.find((r) => r.id === 3).subtotal, null);

k.hapusPilihan(1);
cek('bisa hapus satu baris dari ringkasan', k.rincianDipilih.map((r) => r.id), [3]);
cek('banyakDipilih ikut turun setelah dihapus', k.banyakDipilih, 1);
// --- jendela konfirmasi sebelum kirim. Dulu "Kirim Pesanan" langsung
//     mengirim tanpa pernah memperlihatkan lagi barang apa saja yang terkirim.
const formPalsu = (isian) => ({
    querySelector(sel) {
        if (sel === 'input[name="delivery_method"]:checked') {
            return isian.cara ? { dataset: { label: isian.cara, butuhAlamat: isian.butuhAlamat ? '1' : '0' } } : null;
        }
        const nama = (sel.match(/\[name="([^"]+)"\]/) || [])[1];
        return nama in isian ? { value: isian[nama] } : null;
    },
});
const acara = (target) => ({ target, dicegah: false, preventDefault() { this.dicegah = true; } });
let fokus = 0;
k.$nextTick = (fn) => fn();
k.$refs = { tombolKirimFinal: { focus: () => fokus++ } };

k.jumlah[1] = 2;
const e1 = acara(formPalsu({ cara: 'Diantar', butuhAlamat: true, delivery_address: ' Jl. Kenanga 5 ', non_member_name: 'Teti', whatsapp_number: '0812' }));
k.periksaDulu(e1);
cek('kirim pertama ditahan, jendela konfirmasi terbuka', [e1.dicegah, k.konfirmasiTerbuka], [true, true]);
cek('cara terima & identitas terbaca dari form', k.ringkasKirim, { cara: 'Diantar', butuhAlamat: true, alamat: 'Jl. Kenanga 5', nama: 'Teti', wa: '0812' });
cek('fokus pindah ke tombol kirim di jendela', fokus, 1);

// Ketukan kedua dari ketukan ganda jatuh di jendela yang baru muncul
let terkirim = 0, dicegahSaatKirim = null;
k.$refs.formPesan = { requestSubmit() { const e = acara(formPalsu({})); k.periksaDulu(e); dicegahSaatKirim = e.dicegah; terkirim++; } };
k.kirimSekarang();
k.tutupKonfirmasi();
cek('ketukan sesaat setelah jendela muncul diabaikan', [terkirim, k.konfirmasiTerbuka], [0, true]);

// "Ya, kirim" setelah sempat membaca: requestSubmit memicu periksaDulu lagi,
// dan kali ini harus lolos
k.dibukaPada = 0;
k.kirimSekarang();
cek('"Ya, kirim" benar-benar mengirim, sekali', [terkirim, dicegahSaatKirim, k.konfirmasiTerbuka], [1, false, false]);
cek('percobaan berikutnya lewat konfirmasi lagi', k.sudahDikonfirmasi, false);

// Halaman yang dipulihkan tombol "kembali" dikosongkan: barang dari pesanan
// sebelumnya tidak boleh ikut terkirim lagi tanpa disadari.
k.kosongkan();
cek('kosongkan() menolkan semua jumlah', k.banyakDipilih, 0);

const e2 = acara(formPalsu({}));
k.periksaDulu(e2);
cek('form tanpa isian tidak ditahan (server yang menolak)', [e2.dicegah, k.konfirmasiTerbuka], [false, false]);

console.log(`\n  ${lulus} lulus, ${gagal} gagal`);
process.exit(gagal ? 1 : 0);
