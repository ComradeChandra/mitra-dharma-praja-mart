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

k.daftar({ id: 1, nama: 'beras pandan wangi', kategori: 'Sembako', harga: 72000, awal: 0 });
k.daftar({ id: 2, nama: 'gula pasir 1kg',     kategori: 'Sembako', harga: 17000, awal: 0 });
k.daftar({ id: 3, nama: 'telur ayam 1kg',     kategori: 'Sayur',   harga: null,  awal: 0 });

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

console.log(`\n  ${lulus} lulus, ${gagal} gagal`);
process.exit(gagal ? 1 : 0);
