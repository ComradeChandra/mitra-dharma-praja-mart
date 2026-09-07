<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ProductRequestStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\ProductRequest;

/**
 * Ringkasan angka + aktivitas terbaru buat dashboard admin (kartu statistik,
 * status periode, panel "Anggota/Produk Terbaru", badge permintaan menunggu).
 *
 * Dipisah dari DashboardController (yang tadinya isinya query beruntun cukup
 * panjang) sesuai aturan Service Layer di CLAUDE.md: method controller
 * idealnya di bawah ~20 baris, kalau lebih dari itu logicnya dipindah ke
 * Service. Beda tanggung jawab sama RecapService, ini ringkasan data master
 * (anggota/produk/OPD), RecapService khusus rekap yang DIHITUNG dari pesanan
 * (keuntungan, anggota tersering, produk terlaris).
 */
class DashboardStatsService
{
    /**
     * @return array{
     *     stats: array{totalAnggota: int, anggotaAktif: int, totalProduk: int, totalOpd: int},
     *     periodeAktif: OrderPeriod|null,
     *     anggotaTerbaru: \Illuminate\Support\Collection,
     *     produkTerbaru: \Illuminate\Support\Collection,
     *     permintaanMenunggu: int,
     *     pembayaranMenunggu: int,
     * }
     */
    public function summary(): array
    {
        return [
            'stats' => [
                'totalAnggota' => Member::count(),
                'anggotaAktif' => Member::aktif()->count(),
                'totalProduk' => Product::where('is_active', true)->count(),
                'totalOpd' => OpdDepartment::count(),
            ],

            // Periode yang sedang dibuka, ditampilkan di kartu paling atas
            // karena ini info yang paling sering dicek admin. Aturan "sedang
            // dibuka" (status open DAN masih dalam rentang tanggal) ada di
            // OrderPeriod::yangSedangDibuka(), dipakai bareng sama katalog &
            // form pesan biar tidak beda-beda definisinya.
            'periodeAktif' => OrderPeriod::yangSedangDibuka(),

            // 5 anggota & 5 produk yang paling baru diinput, buat panel
            // "Aktivitas Terbaru", biar dashboard kerasa hidup/berkembang.
            'anggotaTerbaru' => Member::latest()->take(5)->get(),
            'produkTerbaru' => Product::latest()->take(5)->get(),

            // Jumlah permintaan produk yang belum ditinjau, badge di widget
            // "Aksi Cepat" biar admin langsung tau ada yang perlu ditindaklanjuti.
            'permintaanMenunggu' => ProductRequest::where('status', ProductRequestStatus::Pending)->count(),

            // Pemesan yang sudah menyatakan bayar tapi belum dicocokkan ke
            // mutasi rekening. Ini pekerjaan harian pengurus, dan angka
            // "Pendapatan" di grafik dihitung dari pesanan terverifikasi tanpa
            // peduli sudah dibayar atau belum, jadi tanpa penanda ini gampang
            // mengira uangnya sudah masuk semua.
            'pembayaranMenunggu' => Order::where('payment_status', PaymentStatus::AwaitingConfirmation)->count(),
        ];
    }
}
