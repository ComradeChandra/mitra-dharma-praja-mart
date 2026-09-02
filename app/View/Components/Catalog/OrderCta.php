<?php

namespace App\View\Components\Catalog;

use App\Services\NonMemberSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Tombol "Pesan" di katalog — dipakai di kartu produk dan di halaman detail
 * produk.
 *
 * Tujuannya menyesuaikan siapa yang sedang masuk (sudah fix 24 Agt 2026,
 * lihat CLAUDE.md Lampiran B soal non-anggota):
 *   - anggota            -> form pemesanan anggota
 *   - non-anggota (OPD)  -> form pemesanan non-anggota
 *   - belum masuk        -> halaman masuk
 *
 * Sengaja komponen berbasis CLASS. Sebelumnya pengecekan ini ditulis di blok
 * @php di dalam kartu produk, padahal aturan di CLAUDE.md melarang file Blade
 * mengurus hal seperti itu sendiri.
 *
 * Semua produk dipesan sekaligus di satu form, bukan per-produk (lihat catatan
 * "tanpa istilah keranjang" di CLAUDE.md), jadi tombol ini cuma MENGANTAR ke
 * form-nya, bukan menambahkan barang ke mana pun.
 */
class OrderCta extends Component
{
    public string $href;

    public string $label;

    /** Judul tambahan saat kursor diarahkan, cuma dipakai buat tamu. */
    public ?string $title = null;

    /** 'anggota', 'non-anggota', atau 'tamu'. Dipakai Blade buat memilih gaya
     *  tombolnya — kelas Tailwind sengaja tidak ditaruh di sini. */
    public string $peran;

    public function __construct(NonMemberSessionService $sesiNonAnggota)
    {
        if (Auth::guard('member')->check()) {
            $this->href = route('member.orders.create');
            $this->label = 'Pesan Sekarang';
            $this->peran = 'anggota';

            return;
        }

        if ($sesiNonAnggota->sedangLogin()) {
            $this->href = route('non-member.orders.create');
            $this->label = 'Pesan Sekarang';
            $this->peran = 'non-anggota';

            return;
        }

        $this->href = route('masuk');
        $this->label = 'Masuk buat Pesan';
        $this->title = 'Masuk dulu buat memesan';
        $this->peran = 'tamu';
    }

    public function render(): View
    {
        return view('components.catalog.order-cta');
    }
}
