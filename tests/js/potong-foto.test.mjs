// Uji hitungan potong foto persegi (resources/js/potong-foto.js),
// dijalankan tanpa browser.
//
// Cara menjalankan, dari folder utama project:
//     npm run test:js
//
// Berkasnya tidak bergantung pada Alpine, jadi bisa di-import langsung.
// potongKeBerkas() (yang butuh kanvas browser) tidak diuji di sini.
import { areaSumber, geser, keadaanAwal, perbesar, PERBESAR_MAKS } from '../../resources/js/potong-foto.js';

let lulus = 0, gagal = 0;
const cek = (nama, dapat, harap) => {
    const ok = JSON.stringify(dapat) === JSON.stringify(harap);
    ok ? lulus++ : gagal++;
    console.log(`  ${ok ? 'OK  ' : 'GAGAL'} ${nama}${ok ? '' : ` -> dapat ${JSON.stringify(dapat)}, harap ${JSON.stringify(harap)}`}`);
};
const posisi = (k) => [k.x, k.y];

// Foto mendatar 1600x1200 di bingkai 300 px: skala dasar 0,25 -> tampil 400x300.
const awal = keadaanAwal(1600, 1200, 300);
cek('foto mendatar: awalnya di tengah, menutupi bingkai', posisi(awal), [-50, 0]);
cek('foto mendatar: yang terpotong bagian tengah', areaSumber(awal), { x: 200, y: 0, sisi: 1200 });

// Foto tegak 1000x2000 di bingkai 250 px.
const tegak = keadaanAwal(1000, 2000, 250);
cek('foto tegak: awalnya di tengah', posisi(tegak), [0, -125]);
cek('foto tegak: yang terpotong bagian tengah', areaSumber(tegak), { x: 0, y: 500, sisi: 1000 });

// Geser tidak boleh memperlihatkan ruang kosong.
cek('geser terlalu jauh ke kanan berhenti di tepi', posisi(geser(awal, 500, 0)), [0, 0]);
cek('geser terlalu jauh ke kiri berhenti di tepi', posisi(geser(awal, -500, 0)), [-100, 0]);
cek('foto setinggi bingkai tidak bisa digeser naik-turun', posisi(geser(awal, 0, 80)), [-50, 0]);

// Perbesar 2x: titik tengah bingkai tetap menunjuk tengah foto.
const dua = perbesar(awal, 2);
cek('perbesar 2x menjaga titik tengah', posisi(dua), [-250, -150]);
cek('perbesar 2x memotong bagian tengah yang lebih sempit', areaSumber(dua), { x: 500, y: 300, sisi: 600 });

// Batas perbesaran.
cek('perbesar dibatasi paling besar', perbesar(awal, 10).perbesar, PERBESAR_MAKS);
cek('perbesar dibatasi paling kecil 1', perbesar(awal, 0).perbesar, 1);
cek('nilai perbesar yang bukan angka dianggap 1', perbesar(awal, 'abc').perbesar, 1);

// Setelah digeser ke pojok saat diperbesar, mengecilkan lagi tetap menutupi bingkai.
const pojok = geser(perbesar(awal, 3), -10000, -10000);
const kecilLagi = perbesar(pojok, 1);
cek('kembali ke 1x setelah digeser ke pojok tetap menutupi bingkai', posisi(kecilLagi), [-100, 0]);

console.log(`\n  ${lulus} lulus, ${gagal} gagal`);
process.exit(gagal ? 1 : 0);
