<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use App\Services\KontakPengurusService;
use App\Services\PasswordResetService;
use App\Services\RecapService;
use Illuminate\View\View;

/**
 * Controller untuk halaman utama (dashboard) admin setelah login.
 *
 * Cuma orkestrasi, panggil 2 Service (ringkasan data master & rekap
 * pesanan) lalu kirim hasilnya ke view. Semua query sesungguhnya ada di
 * DashboardStatsService & RecapService, sesuai aturan Service Layer di
 * CLAUDE.md (method controller idealnya tipis, di bawah ~20 baris).
 */
class DashboardController extends Controller
{
    public function __construct(
        private DashboardStatsService $dashboardStatsService,
        private RecapService $recapService,
        private KontakPengurusService $kontakPengurus,
        private PasswordResetService $lupaPassword,
    ) {}

    public function index(): View
    {
        $summary = $this->dashboardStatsService->summary();

        // Data buat 3 grafik batang+garis (keuntungan per periode, anggota
        // paling sering belanja, distribusi per OPD) + 1 tabel produk
        // terlaris (bukan grafik, sesuai permintaan), semua dihitung dari
        // pesanan yang statusnya sudah final (verified/invoiced), lihat RecapService.
        $recap = [
            'revenueChart' => $this->recapService->revenueByPeriod(),
            'topMembersChart' => $this->recapService->topMembers(),
            'opdChart' => $this->recapService->opdDistribution(),
            'topProducts' => $this->recapService->topProducts(),

            // Progres "sudah belanja vs belum" di periode yang sedang dibuka —
            // ini sengaja menghitung semua status pesanan (termasuk pending),
            // beda dari rekap uang di atas. Lihat RecapService.
            'progresBelanja' => $this->recapService->memberOrderProgressForPeriod($summary['periodeAktif']),
        ];

        $pengingat = [
            // Nomor WhatsApp koperasi belum diisi: selama kosong, pemesan
            // tidak punya tombol untuk menghubungi pengurus.
            'nomorWaBelumDiisi' => $this->kontakPengurus->nomorWhatsApp() === null,
            // Anggota yang menunggu dibuatkan password baru
            'permintaanLupaPassword' => $this->lupaPassword->jumlahMenunggu(),
        ];

        return view('admin.dashboard', [...$summary, ...$recap, ...$pengingat]);
    }
}
