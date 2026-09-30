// Uji logika notifikasi pesanan baru di halaman pengurus
// (resources/js/notif-pesanan.js), dijalankan tanpa browser.
//
// Cara menjalankan, dari folder utama project:
//     npm run test:js
//
// Polanya sama dengan order-form.test.mjs: isi Alpine.data(...) diambil
// sebagai teks dari berkas aslinya lalu dijalankan, jadi yang diuji memang
// kode yang dipakai. fetch dan document diganti tiruan sederhana.
import { readFileSync } from 'fs';

const src = readFileSync('resources/js/notif-pesanan.js', 'utf8');
const mulai = src.indexOf("Alpine.data('notifPesanan', ") + "Alpine.data('notifPesanan', ".length;
const selesai = src.lastIndexOf('}));');
const pabrik = new Function('return ' + src.slice(mulai, selesai + 2))();

// --- tiruan document & fetch
globalThis.document = { title: 'Pesanan Masuk', hidden: false, addEventListener() {} };

let dipanggil = [];
let jawabanBerikut = null;
globalThis.fetch = async (alamat) => {
    dipanggil.push(alamat);
    if (jawabanBerikut instanceof Error) throw jawabanBerikut;
    return jawabanBerikut;
};
const jawab = (data, status = 200, redirected = false) => ({
    ok: status >= 200 && status < 300, status, redirected, json: async () => data,
});

let lulus = 0, gagal = 0;
const cek = (nama, dapat, harap) => {
    const ok = JSON.stringify(dapat) === JSON.stringify(harap);
    ok ? lulus++ : gagal++;
    console.log(`  ${ok ? 'OK  ' : 'GAGAL'} ${nama}${ok ? '' : ` -> dapat ${JSON.stringify(dapat)}, harap ${JSON.stringify(harap)}`}`);
};

const k = pabrik({ url: '/admin/pesanan-baru' });

// 1. Pengecekan pertama cuma menentukan titik awal.
jawabanBerikut = jawab({ terakhir: 5, baru: 0 });
await k.cek();
cek('cek pertama tanpa ?sejak', dipanggil.at(-1), '/admin/pesanan-baru');
cek('titik awal = id terbaru', [k.sejak, k.baru], [5, 0]);
cek('judul tab belum berubah', document.title, 'Pesanan Masuk');

// 2. Ada pesanan baru.
jawabanBerikut = jawab({ terakhir: 7, baru: 2 });
await k.cek();
cek('cek berikutnya mengirim titik awal', dipanggil.at(-1), '/admin/pesanan-baru?sejak=5');
cek('jumlah pesanan baru', k.baru, 2);
cek('angka muncul di judul tab', document.title, '(2) Pesanan Masuk');

// 3. Titik awal TIDAK bergeser sebelum ditutup, jadi jumlahnya bertambah benar.
jawabanBerikut = jawab({ terakhir: 8, baru: 3 });
await k.cek();
cek('titik awal tetap selama belum ditutup', dipanggil.at(-1), '/admin/pesanan-baru?sejak=5');
cek('jumlah ikut bertambah', [k.baru, document.title], [3, '(3) Pesanan Masuk']);

// 4. Tutup: pesanan yang sudah diberitahukan dianggap diketahui.
k.tutup();
cek('tutup memindahkan titik awal', [k.sejak, k.baru], [8, 0]);
cek('judul tab kembali', document.title, 'Pesanan Masuk');

// 5. Gangguan sesaat: tidak berhenti, tidak merusak angka.
jawabanBerikut = jawab({}, 500);
await k.cek();
cek('error 500 diabaikan', [k.baru, k.berhenti], [0, false]);

jawabanBerikut = new Error('koneksi putus');
await k.cek();
cek('koneksi putus tidak membuat macet', [k.sedangCek, k.berhenti], [false, false]);

// 6. Sesi habis: berhenti bertanya.
jawabanBerikut = jawab({}, 401);
await k.cek();
cek('401 menghentikan pengecekan', k.berhenti, true);

const sebelum = dipanggil.length;
await k.cek();
cek('setelah berhenti tidak bertanya lagi', dipanggil.length, sebelum);

// 7. Akun dinonaktifkan (dialihkan ke halaman login): ikut berhenti.
const k2 = pabrik({ url: '/admin/pesanan-baru' });
jawabanBerikut = jawab({}, 200, true);
await k2.cek();
cek('dialihkan ke login menghentikan pengecekan', k2.berhenti, true);

console.log(`\n  ${lulus} lulus, ${gagal} gagal`);
process.exit(gagal ? 1 : 0);
