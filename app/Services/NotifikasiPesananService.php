<?php

namespace App\Services;

use App\Models\Order;

/**
 * Pengecekan pesanan baru untuk notifikasi di halaman pengurus
 * (komponen x-admin.notif-pesanan + resources/js/notif-pesanan.js).
 *
 * Penanda "baru" memakai id pesanan, bukan jam: id selalu naik, jadi tidak
 * terpengaruh beda jam server dan browser, dan dua pesanan yang masuk di
 * detik yang sama tidak mungkin terlewat.
 */
class NotifikasiPesananService
{
    /**
     * Ringkasan untuk satu kali pengecekan dari browser.
     *
     * - terakhir : id pesanan terbaru saat ini (0 kalau belum ada pesanan).
     *              Browser menyimpannya sebagai titik awal pengecekan berikutnya.
     * - baru     : jumlah pesanan yang belum dibatalkan dan id-nya lebih besar
     *              dari titik awal. Pada pengecekan pertama (belum ada titik
     *              awal) selalu 0, karena pesanan yang sudah ada sebelum halaman
     *              dibuka memang sudah terlihat di halaman itu.
     *
     * @return array{terakhir: int, baru: int}
     */
    public function ringkasan(?int $sejakId): array
    {
        // Diambil SEBELUM menghitung: kalau ada pesanan masuk di antara dua
        // query ini, pesanan itu ikut terhitung sekarang dan sekali lagi di
        // pengecekan berikutnya. Lebih baik muncul dua kali daripada terlewat.
        $terakhir = (int) Order::max('id');

        return [
            'terakhir' => $terakhir,
            'baru' => $sejakId === null ? 0 : $this->jumlahSejak($sejakId),
        ];
    }

    /**
     * Jumlah pesanan yang masuk setelah titik awal dan belum dibatalkan.
     * Pesanan yang langsung dibatalkan pemesannya tidak perlu diberitahukan.
     */
    private function jumlahSejak(int $sejakId): int
    {
        return Order::belumDibatalkan()
            ->where('id', '>', $sejakId)
            ->count();
    }
}
