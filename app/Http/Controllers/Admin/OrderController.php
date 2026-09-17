<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\RemoveOrderItemRequest;
use App\Http\Requests\Admin\TolakStokRequest;
use App\Http\Requests\Admin\VerifyOrderRequest;
use App\Models\Order;
use App\Services\OrderCancellationService;
use App\Services\OrderService;
use App\Services\WhatsAppInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Sisi admin dari pemesanan (Modul 3, 5 & 7 di CLAUDE.md), admin melihat
 * semua pesanan yang masuk, mengunci harga produk fluktuatif ("verifikasi")
 * biar totalnya final, lalu kirim invoice teks lewat WhatsApp. Pesanan yang
 * semua produknya non-fluktuatif sudah otomatis "verified" sejak dikirim
 * anggota, jadi tidak semua pesanan butuh aksi verifikasi di sini.
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WhatsAppInvoiceService $whatsAppInvoiceService,
        private OrderCancellationService $pembatalan,
    ) {}

    /**
     * Daftar semua pesanan yang masuk, bisa difilter per status lewat
     * query string ?status=pending (tab sederhana, bukan filter kompleks).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $statusBayar = $request->query('bayar');
        // Datang dari pengingat dasbor "Pesanan melebihi stok, perlu ditinjau".
        $perluTinjauanStok = $request->boolean('tinjauan_stok');

        // Dua penyaring terpisah karena status pesanan dan status pembayaran
        // bergerak sendiri-sendiri. Aturan penyaringannya ada di scope model.
        /** @var LengthAwarePaginator $orders */
        $orders = Order::with('member', 'orderPeriod')
            ->statusPesanan($status)
            ->statusPembayaran($statusBayar)
            ->when($perluTinjauanStok, fn ($query) => $query->perluTinjauanStok())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status', 'statusBayar', 'perluTinjauanStok'));
    }

    /**
     * Detail 1 pesanan. Kalau statusnya masih "pending" & ada item produk
     * fluktuatif yang belum berharga, halaman ini juga nampilin form
     * verifikasi (isi harga per item). Kalau statusnya sudah "verified" atau
     * "invoiced", halaman ini nampilin preview teks invoice + link wa.me
     * (Modul 7) buat admin kirim manual ke pemesan.
     */
    public function show(Order $order): View
    {
        $order->load('orderItems.product', 'member', 'orderPeriod', 'opdDepartment');

        // Invoice cuma bisa disiapkan kalau totalnya sudah final (verified/invoiced) —
        // pesanan "pending" belum punya harga lengkap, dan pesanan yang
        // dibatalkan tidak perlu ditagih.
        $invoiceText = null;
        $whatsAppLink = null;
        if ($order->status->hargaSudahFinal()) {
            $invoiceText = $this->whatsAppInvoiceService->generateInvoiceText($order);
            $whatsAppLink = $this->whatsAppInvoiceService->generateWhatsAppLink($order);
        }

        // Berapa pesanan lain di periode ini yang juga menunggu harga barang
        // yang sama, buat pilihan "terapkan ke semua" di form kunci harga.
        $pesananLainMenunggu = $this->orderService->pesananLainMenungguHarga($order);

        // null berarti kartu "Hapus barang dari pesanan" boleh tampil
        $alasanTidakBisaHapusBarang = $this->pembatalan->alasanTidakBisaHapusBarang($order);

        return view('admin.orders.show', compact('order', 'invoiceText', 'whatsAppLink', 'pesananLainMenunggu', 'alasanTidakBisaHapusBarang'));
    }

    /**
     * Struk resmi pesanan, halaman tersendiri yang siap dicetak.
     *
     * Beda dari sisi pemesan: tautan WhatsApp di sini sudah menuju nomor
     * pemesannya, karena pengurus yang mengirimkan.
     */
    public function struk(Order $order): View
    {
        $order->load('orderItems.product', 'member', 'orderPeriod', 'opdDepartment');

        $tautanWhatsApp = $order->status->hargaSudahFinal()
            ? $this->whatsAppInvoiceService->generateWhatsAppLink($order)
            : null;

        return view('struk.show', [
            'order' => $order,
            'tautanWhatsApp' => $tautanWhatsApp,
            'kembali' => route('admin.orders.show', $order),
        ]);
    }

    /**
     * Pengurus mencocokkan ke rekening lalu menandai lunas.
     */
    public function confirmPayment(Order $order): RedirectResponse
    {
        $this->orderService->confirmPayment($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pembayaran ditandai lunas.');
    }

    /**
     * Sajikan bukti transfer yang diunggah pemesan.
     *
     * Berkasnya disimpan di disk privat, jadi tidak bisa dibuka langsung lewat
     * URL. Isinya data rekening orang, dan sebelumnya sempat tersimpan di disk
     * publik sehingga siapa pun yang punya tautannya bisa membukanya.
     */
    public function paymentProof(Order $order)
    {
        // Pengurus boleh membuka bukti pesanan mana pun.

        abort_unless($order->payment_proof_path, 404);
        abort_unless(Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->response($order->payment_proof_path);
    }

    /**
     * Kunci harga produk fluktuatif yang masih kosong di pesanan ini, lalu
     * hitung ulang totalnya (lihat OrderService::verifyOrder()).
     */
    public function verify(VerifyOrderRequest $request, Order $order): RedirectResponse
    {
        $hasil = $this->orderService->verifyOrder($order, $request->itemPrices(), $request->terapkanKeSemua());

        $pesan = 'Pesanan berhasil diverifikasi, harga & total sudah dikunci.';
        if ($hasil['lain'] > 0) {
            $pesan .= " Harganya juga diterapkan ke {$hasil['lain']} pesanan lain di periode ini";
            $pesan .= $hasil['masihMenunggu'] > 0
                ? "; {$hasil['masihMenunggu']} di antaranya masih menunggu harga barang lain."
                : '.';
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', $pesan);
    }

    /**
     * Pengurus menghapus satu barang dari pesanan, misalnya karena habis di
     * grosir. Total dihitung ulang dan pemesan melihat catatannya; aturannya
     * ada di OrderCancellationService::hapusBarang().
     */
    public function removeItem(RemoveOrderItemRequest $request, Order $order): RedirectResponse
    {
        $this->pembatalan->hapusBarang($order, $request->barang(), $request->alasan());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Barang dihapus dari pesanan dan totalnya sudah dihitung ulang. Kirim ulang invoice supaya pemesan menerima rincian terbaru.');
    }

    /**
     * Pengurus membatalkan pesanan, misalnya karena barangnya habis di grosir
     * atau pemesan keberatan dengan harga akhirnya. Aturannya ada di
     * OrderCancellationService.
     */
    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->pembatalan->batalkanOlehPengurus($order, $request->alasan(), $request->uangDikembalikan());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pesanan dibatalkan dan tidak lagi dihitung di rekap. Jangan lupa kabari pemesannya lewat WhatsApp.');
    }

    /**
     * Tandai invoice sudah dikirim manual lewat WhatsApp (lihat catatan di
     * OrderService::markAsInvoiced(), ini konfirmasi manual admin, bukan
     * callback otomatis dari WhatsApp).
     */
    public function markInvoiced(Order $order): RedirectResponse
    {
        $this->orderService->markAsInvoiced($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pesanan ditandai invoice sudah terkirim.');
    }

    /**
     * Tinjauan stok — Setujui: koperasi belanja lebih, pesanan lanjut apa
     * adanya. Aturannya di OrderCancellationService::setujuiStok().
     */
    public function setujuiStok(Order $order): RedirectResponse
    {
        $this->pembatalan->setujuiStok($order, auth()->id());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pesanan disetujui. Koperasi akan belanja lebih untuk menutup kekurangan stok; isi pesanan tidak berubah.');
    }

    /**
     * Tinjauan stok — Tolak: barang yang melebihi stok disesuaikan ke stok
     * tercatat (atau dihapus kalau stoknya 0). Aturannya di
     * OrderCancellationService::tolakKarenaStok().
     */
    public function tolakStok(TolakStokRequest $request, Order $order): RedirectResponse
    {
        $this->pembatalan->tolakKarenaStok($order, $request->alasan(), auth()->id());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Jumlah barang disesuaikan ke stok yang tersedia dan totalnya dihitung ulang. Kirim ulang invoice supaya pemesan menerima rincian terbaru.');
    }
}
