<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\RecapService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman beranda anggota setelah login, profil dasar + ringkasan belanja
 * tahun berjalan (dasar hitung SHU, Modul 9 di CLAUDE.md).
 */
class DashboardController extends Controller
{
    public function __construct(private RecapService $recapService) {}

    public function index(): View
    {
        // Ambil data anggota yang sedang login dari guard 'member', dikirim
        // ke view lewat variabel biasa (bukan manggil Auth::guard() berkali-kali di Blade)
        $member = Auth::guard('member')->user();

        // Total belanja anggota ini TAHUN INI, dasar hitung SHU (lihat
        // CLAUDE.md Modul 9). Cuma
        // angkanya saja yang ditampilkan (bukan rincian), sesuai arahan Ibu
        // pengurus: cukup angka totalnya, tidak perlu rincian per transaksi.
        $belanjaTahunIni = $this->recapService->memberYearlySpending($member);

        // Perkiraan SHU dihitung di service, bukan di Blade. Persentasenya
        // ada di config/koperasi.php supaya gampang diganti begitu koperasi
        // menetapkan angkanya.
        $estimasiShu = $this->recapService->estimasiShu($belanjaTahunIni);

        return view('member.dashboard', compact('member', 'belanjaTahunIni', 'estimasiShu'));
    }
}
