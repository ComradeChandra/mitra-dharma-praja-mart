<?php

namespace App\Services;

use App\Enums\CancelledBy;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
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
 * - Satu barang saja (16 Sep 2026): cuma pengurus, selama pembayaran belum
 *   berjalan, dan bukan barang terakhir di pesanan (untuk itu, batalkan
 *   pesanannya). Barangnya dihapus dari pesanan, stoknya kembali, total
 *   dihitung ulang, dan kejadiannya dicatat di catatan_pengurus supaya
 *   pemesan tahu kenapa isi pesanannya berubah.
 *
 * Semua aturan itu sengaja dikumpulkan di sini, bukan tersebar di
 * controller, supaya gampang diubah kalau koperasi memutuskan lain.
 */
class OrderCancellationService
{
    /**
     * Penghitung total dipinjam dari OrderService, supaya aturan "kapan
     * pesanan terverifikasi" tidak punya dua salinan.
     */
    public function __construct(
        private OrderService $orderService,
    ) {}

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
     * Kenapa pengurus TIDAK bisa menghapus satu barang dari pesanan ini, atau
     * null kalau bisa. Dipakai halaman detail pesanan untuk menampilkan atau
     * menyembunyikan kartu "Hapus barang", dan dipakai lagi saat menghapus.
     */
    public function alasanTidakBisaHapusBarang(Order $order): ?string
    {
        if ($order->dibatalkan()) {
            return 'Pesanan ini sudah dibatalkan.';
        }

        // Sama dengan aturan kunci harga: begitu uang berpindah, isi pesanan
        // tidak diubah diam-diam. Selisihnya urusan pengurus dan pemesan.
        if ($order->payment_status !== PaymentStatus::Unpaid) {
            return 'Pembayaran pesanan ini sudah berjalan, jadi isinya tidak bisa diubah lagi. Kalau perlu, batalkan pesanannya lalu kembalikan uangnya.';
        }

        if ($order->orderItems()->count() < 2) {
            return 'Ini satu-satunya barang di pesanan ini. Kalau barangnya tidak bisa dipenuhi, batalkan pesanannya.';
        }

        return null;
    }

    /**
     * Pengurus menghapus satu barang dari pesanan, mis. karena habis di grosir.
     *
     * @param  string|null  $alasan  ikut dicatat dan terlihat oleh pemesan
     */
    public function hapusBarang(Order $order, OrderItem $item, ?string $alasan): void
    {
        abort_unless((int) $item->order_id === (int) $order->id, 404);

        DB::transaction(function () use ($order, $item, $alasan) {
            // Dikunci dan dibaca ulang: pemesan bisa saja menyatakan bayar
            // atau membatalkan tepat bersamaan.
            $terkini = Order::whereKey($order->id)->lockForUpdate()->first();
            $tidakBisa = $this->alasanTidakBisaHapusBarang($terkini);
            abort_if($tidakBisa !== null, 422, (string) $tidakBisa);

            $item->loadMissing('product');

            // Hapus bersyarat: kalau barangnya keburu dihapus di tab lain,
            // stoknya jangan dikembalikan dua kali.
            $dihapus = OrderItem::whereKey($item->id)->where('order_id', $order->id)->delete();
            if ($dihapus === 0) {
                return;
            }

            $this->kembalikanStokBarang($item);

            $baris = sprintf(
                '%s: %s (%d) dihapus dari pesanan oleh pengurus.%s',
                now()->translatedFormat('d M Y'),
                $item->product?->name ?? 'Barang',
                $item->quantity,
                // Diakhiri tepat satu titik, walau pengurus sudah mengetik titik sendiri
                $alasan ? ' Alasan: '.rtrim($alasan, '. ').'.' : '',
            );
            $terkini->update([
                'catatan_pengurus' => trim($terkini->catatan_pengurus."\n".$baris),
            ]);

            $this->orderService->hitungUlang($terkini);
        });

        $order->refresh();
    }

    /**
     * Kembalikan stok semua barang di pesanan yang dibatalkan.
     */
    private function kembalikanStok(Order $order): void
    {
        $order->load('orderItems.product');

        foreach ($order->orderItems as $item) {
            $this->kembalikanStokBarang($item);
        }
    }

    /**
     * Kembalikan stok satu barang, kalau stoknya dilacak. Pasangan dari
     * OrderService::kurangiStok() yang mengurangi stok saat pesanan dikirim.
     * Produk pre-order murni tidak punya angka stok, jadi dilewati.
     */
    private function kembalikanStokBarang(OrderItem $item): void
    {
        if ($item->product?->has_stock_tracking) {
            Product::whereKey($item->product_id)->increment('stock', $item->quantity);
        }
    }
}
