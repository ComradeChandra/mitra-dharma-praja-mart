/*
 * Cegah form terkirim dua kali. Berlaku di SEMUA halaman (dimuat dari app.js).
 *
 * KENAPA ADA: klik ganda di tombol kirim (umum, apalagi di HP atau kalau
 * koneksinya lambat) membuat browser mengirim dua permintaan. Browser cuma
 * menampilkan hasil yang kedua, jadi pemakai tidak sadar apa-apa, padahal di
 * server dua-duanya diproses. Sudah dibuktikan: satu klik ganda di "Kirim
 * Pesanan" menghasilkan dua pesanan dan stoknya terpotong dua kali. Di tombol
 * "Saya sudah bayar", permintaan kedua malah berakhir di halaman error karena
 * pembayarannya sudah tercatat oleh permintaan pertama.
 *
 * CARA KERJANYA: begitu form mulai terkirim, kiriman berikutnya dari form
 * yang sama ditolak, dan tombolnya diredupkan supaya kelihatan sedang diproses.
 * Tombolnya sengaja TIDAK di-disable: tombol yang di-disable tidak ikut
 * mengirim name/value-nya, dan itu bisa mengubah isi kiriman.
 *
 * Yang dikecualikan:
 * - form GET (pencarian, filter): mengirim ulang tidak berbahaya.
 * - form yang dibatalkan sebelum terkirim, mis. dialog konfirmasi hapus
 *   dijawab "Batal" (onsubmit="return confirm(...)"), atau form yang ditangani
 *   JavaScript sendiri (@submit.prevent). Keduanya menandai event-nya
 *   defaultPrevented, dan penanda itu yang dicek di bawah.
 */

const TANDA = 'sedangDikirim';

// Jaring pengaman: kalau karena sesuatu halaman tidak berpindah (koneksi
// putus, server tidak menjawab), form bisa dipakai lagi setelah jeda ini.
const BATAS_TUNGGU_MS = 10000;

// form.elements, bukan querySelectorAll: ikut mencakup tombol yang letaknya
// di luar tag <form> tapi terhubung lewat atribut form="..." (dipakai bilah
// ringkasan di form pesan).
function tombolPengirim(form) {
    return [...form.elements].filter((el) => el.type === 'submit');
}

function bebaskan(form) {
    delete form.dataset[TANDA];
    tombolPengirim(form).forEach((tombol) => {
        tombol.removeAttribute('aria-busy');
        tombol.classList.remove('opacity-60', 'cursor-wait');
    });
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;

    if (form.dataset[TANDA]) {
        event.preventDefault();
        return;
    }

    form.dataset[TANDA] = '1';
    tombolPengirim(form).forEach((tombol) => {
        tombol.setAttribute('aria-busy', 'true');
        tombol.classList.add('opacity-60', 'cursor-wait');
    });

    setTimeout(() => bebaskan(form), BATAS_TUNGGU_MS);
});

// Tombol "kembali" di browser bisa memulihkan halaman lama dari cache lengkap
// dengan penandanya. Tanpa ini form-nya terkunci setelah pemakai kembali.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll('form').forEach(bebaskan);
    }
});
