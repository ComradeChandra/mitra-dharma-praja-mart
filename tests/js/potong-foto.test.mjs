// Uji hitungan kotak potong foto persegi (resources/js/potong-foto.js),
// dijalankan tanpa browser.
//
// Cara menjalankan, dari folder utama project:
//     npm run test:js
//
// Berkasnya tidak bergantung pada Alpine, jadi bisa di-import langsung.
// potongKeBerkas() (yang butuh kanvas browser) tidak diuji di sini.
import {
    areaSumber, geserKotak, KOTAK_TERKECIL, kotakAwal, tarikSudut, tataLetak, ubahUkuranTengah,
} from '../../resources/js/potong-foto.js';

let lulus = 0, gagal = 0;
const cek = (nama, dapat, harap) => {
    const ok = JSON.stringify(dapat) === JSON.stringify(harap);
    ok ? lulus++ : gagal++;
    console.log(`  ${ok ? 'OK  ' : 'GAGAL'} ${nama}${ok ? '' : ` -> dapat ${JSON.stringify(dapat)}, harap ${JSON.stringify(harap)}`}`);
};
// Pembulatan 2 desimal supaya pembagian tidak mengganggu perbandingan.
const bulat = (o) => Object.fromEntries(Object.entries(o).map(([k, v]) => [k, Math.round(v * 100) / 100]));

// Foto mendatar 1600x1200 di bingkai 320 px: skala 0,2 -> tampil 320x240,
// tergambar utuh di tengah (ruang kosong 40 px di atas & bawah).
const t = tataLetak(1600, 1200, 320);
cek('foto tergambar utuh di tengah bingkai', bulat({ ox: t.ox, oy: t.oy, lebar: t.lebar, tinggi: t.tinggi }), { ox: 0, oy: 40, lebar: 320, tinggi: 240 });

const awal = kotakAwal(t);
cek('kotak awal = persegi terbesar di tengah foto', awal, { sisi: 240, x: 40, y: 40 });
cek('kotak awal memotong bagian tengah foto asli', bulat(areaSumber(t, awal)), { x: 200, y: 0, sisi: 1200 });

// Memindahkan kotak: tidak boleh keluar dari foto.
cek('geser terlalu jauh ke kanan berhenti di tepi foto', geserKotak(t, awal, 500, 0), { sisi: 240, x: 80, y: 40 });
cek('geser terlalu jauh ke kiri berhenti di tepi foto', geserKotak(t, awal, -500, 0), { sisi: 240, x: 0, y: 40 });
cek('kotak setinggi foto tidak bisa digeser naik-turun', geserKotak(t, awal, 0, 70), { sisi: 240, x: 40, y: 40 });

// Menarik sudut: sudut seberangnya jadi jangkar dan tidak bergerak.
const kecil = tarikSudut(t, awal, 'kanan-bawah', 140, 140);
cek('tarik sudut kanan-bawah ke dalam: kiri-atas tetap', kecil, { sisi: 100, x: 40, y: 40 });

const dariKiriAtas = tarikSudut(t, awal, 'kiri-atas', 180, 200);
cek('tarik sudut kiri-atas ke dalam: kanan-bawah tetap (ikut tarikan terjauh)', dariKiriAtas, { sisi: 100, x: 180, y: 180 });

cek('tarik sudut kanan-atas', tarikSudut(t, awal, 'kanan-atas', 100, 240), { sisi: 60, x: 40, y: 220 });
cek('tarik sudut kiri-bawah', tarikSudut(t, awal, 'kiri-bawah', 230, 90), { sisi: 50, x: 230, y: 40 });

cek('tarik sudut keluar foto dijepit di tepi foto', tarikSudut(t, kecil, 'kanan-bawah', 9999, 9999), { sisi: 240, x: 40, y: 40 });
cek('tarik sudut melewati jangkar dijepit ke ukuran terkecil', tarikSudut(t, awal, 'kanan-bawah', 0, 0), { sisi: KOTAK_TERKECIL, x: 40, y: 40 });

// Potongan setelah kotak dikecilkan.
cek('potongan mengikuti kotak yang dikecilkan', bulat(areaSumber(t, kecil)), { x: 200, y: 0, sisi: 500 });

// Tombol + / −: ukuran berubah, titik tengah tetap.
cek('tombol − mengecilkan dengan tengah tetap', ubahUkuranTengah(t, awal, -40), { sisi: 200, x: 60, y: 60 });
cek('tombol + tidak bisa melebihi sisi foto', ubahUkuranTengah(t, awal, 40), { sisi: 240, x: 40, y: 40 });

// Foto tegak 900x1800 di bingkai 300: tampil 150x300, kotak awal 150.
const tegak = tataLetak(900, 1800, 300);
const awalTegak = kotakAwal(tegak);
cek('foto tegak: kotak awal di tengah', awalTegak, { sisi: 150, x: 75, y: 75 });
cek('foto tegak: memotong bagian tengah', bulat(areaSumber(tegak, awalTegak)), { x: 0, y: 450, sisi: 900 });

// Foto yang lebih kecil dari ukuran terkecil kotak tetap bisa dipotong.
const mungil = tataLetak(20, 20, 20);
cek('foto mungil: kotak tidak lebih besar dari fotonya', tarikSudut(mungil, kotakAwal(mungil), 'kanan-bawah', 0, 0).sisi, 20);

console.log(`\n  ${lulus} lulus, ${gagal} gagal`);
process.exit(gagal ? 1 : 0);
