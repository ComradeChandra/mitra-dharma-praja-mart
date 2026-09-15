<?php

namespace App\Services;

use App\Enums\CancelledBy;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Pembatalan pesanan (15 Sep 2026). Aturannya diputuskan Chandra:
 *
 * - Pemesan boleh membatalkan pesanannya sendiri selama periodenya masih
 *   dibuka dan pesanannya belum dibayar. Setelah periode ditutup, koperasi
 *   mungkin sudah belanja, jadi pembatalan harus lewat pengurus.
 * - Pengurus boleh membatalkan kapan saja. Kalau pembayarannya sudah
 *   berjalan, pengurus wajib menyatakan uangnya dikembalikan, karena uang
 *   yang sudah berpindah tidak bisa diurus aplikasi.
 * - Pesanan yang dibatalkan TIDAK dihapus. Statusnya jadi Dibatalkan, stok
 *   barangnya dikembalikan, dan pesanan itu tidak lagi dihitung di rekap,
 *   grafik, maupun SHU.
 *
 * Semua aturan itu sengaja dikumpulkan di sini, bukan tersebar di
 * controller, supaya gampang diubah kalau koperasi memutuskan lain.
 */
class OrderCancellationService
{
    /**
     * Kenapa pemesan TIDAK bisa membatalkan pesanan ini sendiri, atau null
     * kalau bisa. Kalimatnya langsung ditampilkan ke pemesan.
     */
    public function alasanPemesanTidakBisaBatal(Order $order): ?string
    {
        if ($order->dibatalkan()) {
            return 'Pesanan ini sudah dibatalkan.';
        }

        if ($order->payment_status !== PaymentStatus::Unpaid) {
            return 'Pembayaran pesanan ini sudah berjalan. Untuk membatalkan, hubungi pengurus koperasi.';
        }

        // Periode pesanannya harus periode yang SEDANG dibuka. Aturan "sedang
        // dibuka" (status open dan hari ini di dalam rentang tanggal) ada di
        // OrderPeriod::yangSedangDibuka(), jangan ditulis ulang di sini.
        if (OrderPeriod::yangSedangDibuka()?->id !== $order->order_period_id) {
            return 'Periode pemesanannya sudah ditutup, dan koperasi mungkin sudah membelanjakan barangnya. Untuk membatalkan, hubungi pengurus koperasi.';
        }

        return null;
    }

    /**
     * Pemesan membatalkan pesanannya sendiri.
     *
     * Siapa pemesannya sudah diperiksa controller (anggota pemilik, atau sesi
     * non-anggota yang mengirimnya). Di sini tinggal aturan waktunya.
     */
    public function batalkanOlehPemesan(Order $order): void
    {
        // Tombol yang ditekan dua kali (dua tab, tombol "kembali") tidak
        // berakhir di halaman error.
        if ($order->dibatalkan()) {
            return;
        }

        $alasan = $this->alasanPemesanTidakBisaBatal($order);
        abort_if($alasan !== null, 422, (string) $alasan);

        $this->batalkan($order, CancelledBy::Pemesan, null, hanyaYangBelumDibayar: true);
    }

    /**
     * Pengurus membatalkan pesanan, misalnya karena barangnya habis di grosir
     * atau pemesan keberatan dengan harga akhirnya.
     *
     * @param  bool  $uangDikembalikan  pengurus menyatakan uang pemesan dikembalikan; wajib kalau pembayarannya sudah berjalan
     */
    public function batalkanOlehPengurus(Order $order, ?string $alasan, bool $uangDikembalikan): void
    {
        if ($order->dibatalkan()) {
            return;
        }

        abort_if(
            $order->payment_status !== PaymentStatus::Unpaid && ! $uangDikembalikan,
            422,
            'Pembayaran pesanan ini sudah berjalan. Pastikan uangnya dikembalikan ke pemesan, lalu centang pernyataannya.',
        );

        $this->batalkan($order, CancelledBy::Pengurus, $alasan, hanyaYangBelumDibayar: false);
    }

    /**
     * Ubah statusnya lalu kembalikan stok, dalam satu transaksi.
     *
     * Status diubah dengan syarat di klausa WHERE ("belum dibatalkan"), bukan
     * dicek di PHP lalu disimpan. Kalau dua pembatalan datang bersamaan,
     * database yang memutuskan siapa yang kebagian, jadi stok dikembalikan
     * tepat sekali, bukan dua kali.
     */
    private function batalkan(Order $order, CancelledBy $oleh, ?string $alasan, bool $hanyaYangBelumDibayar): void
    {
        DB::transaction(function () use ($order, $oleh, $alasan, $hanyaYangBelumDibayar) {
            $diubah = Order::whereKey($order->id)
                ->where('status', '!=', OrderStatus::Cancelled->value)
                // Pemesan: pembayaran bisa saja keburu dinyatakan di tab lain
                // sesaat sebelum tombol batal ditekan.
                ->when($hanyaYangBelumDibayar, fn ($query) => $query->where('payment_status', PaymentStatus::Unpaid->value))
                ->update([
                    'status' => OrderStatus::Cancelled->value,
                    'cancelled_at' => now(),
                    'cancelled_by' => $oleh->value,
                    'cancellation_reason' => $alasan,
                ]);

            if ($diubah === 0) {
                return;
            }

            $this->kembalikanStok($order);
        });

        $order->refresh();
    }

    /**
     * Kembalikan stok barang yang stoknya dilacak. Pasangan dari
     * OrderService::kurangiStok() yang mengurangi stok saat pesanan dikirim.
     * Produk pre-order murni tidak punya angka stok, jadi dilewati.
     */
    private function kembalikanStok(Order $order): void
    {
        $order->load('orderItems.product');

        foreach ($order->orderItems as $item) {
            if ($item->product?->has_stock_tracking) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        }
    }
}
