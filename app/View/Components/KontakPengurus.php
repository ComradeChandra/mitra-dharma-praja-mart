<?php

namespace App\View\Components;

use App\Services\KontakPengurusService;
use App\Services\NonMemberSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Tombol "Hubungi Pengurus" lewat WhatsApp (16 Sep 2026).
 *
 * Membuka chat ke nomor koperasi yang diisi pengurus di Admin -> Pengaturan,
 * dengan pesan pembuka yang sudah memperkenalkan pengirimnya (nama & kode
 * anggota, atau OPD untuk non-anggota). Pengurus jadi tidak perlu bertanya
 * balik "ini siapa ya?". Kalau nomornya belum diisi, komponen ini tidak
 * menampilkan apa pun.
 *
 * Berbasis CLASS karena perlu tahu siapa yang sedang masuk dan membaca
 * pengaturan dari database; file Blade tidak boleh query sendiri.
 *
 * Dua bentuk:
 * - varian="melayang" : tombol bulat di pojok kanan bawah (layout anggota,
 *                       non-anggota, dan publik)
 * - varian="tautan"   : tautan biasa di dalam halaman, mis. di halaman masuk
 *                       ("Lupa password?") atau di panel pembatalan
 *
 * Contoh: <x-kontak-pengurus varian="tautan" label="..." pesan="Saya ingin ..." />
 */
class KontakPengurus extends Component
{
    /** Tautan wa.me lengkap, null kalau nomor koperasi belum diisi. */
    public ?string $tautan;

    /**
     * @param  string  $pesan  kalimat tambahan setelah salam pembuka
     * @param  string  $label  teks tautan (khusus varian "tautan")
     */
    public function __construct(
        KontakPengurusService $kontak,
        NonMemberSessionService $sesiNonAnggota,
        public string $varian = 'melayang',
        string $pesan = '',
        public string $label = 'Hubungi pengurus lewat WhatsApp',
    ) {
        $this->tautan = $kontak->tautanWhatsApp(trim($this->salam($sesiNonAnggota).' '.$pesan));
    }

    /**
     * Salam pembuka sesuai siapa yang sedang masuk. Tamu yang belum masuk
     * cukup menyapa; identitasnya bisa ia tulis sendiri di chat.
     */
    private function salam(NonMemberSessionService $sesiNonAnggota): string
    {
        $sapaan = 'Halo pengurus Koperasi Mitra Dharma Praja';

        if ($anggota = Auth::guard('member')->user()) {
            return "{$sapaan}, saya {$anggota->full_name} (anggota {$anggota->member_code}).";
        }

        if ($opd = $sesiNonAnggota->opd()) {
            return "{$sapaan}, saya non-anggota dari {$opd->name}.";
        }

        return "{$sapaan}.";
    }

    /** Tidak dirender sama sekali selama nomor koperasi belum diisi. */
    public function shouldRender(): bool
    {
        return $this->tautan !== null;
    }

    public function render(): View
    {
        return view('components.kontak-pengurus');
    }
}
