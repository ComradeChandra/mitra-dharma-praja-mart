<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyOrderRequest;
use App\Models\Order;
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
    ) {}

    /**
     * Daftar semua pesanan yang masuk, bisa difilter per status lewat
     * query string ?status=pending (tab sederhana, bukan filter kompleks).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $statusBayar = $request->query('bayar');

        // Dua penyaring terpisah karena status pesanan dan status pembayaran
        // bergerak sendiri-sendiri. Aturan penyaringannya ada di scope model.
        /** @var LengthAwarePaginator $orders */
        $orders = Order::with('member', 'orderPeriod')
            ->statusPesanan($status)
            ->statusPembayaran($statusBayar)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status', 'statusBayar'));
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
        // pesanan "pending" belum punya harga lengkap, belum ada yang bisa di-invoice-kan.
        $invoiceText = null;
        $whatsAppLink = null;
        if ($order->status !== OrderStatus::Pending) {
            $invoiceText = $this->whatsAppInvoiceService->generateInvoiceText($order);
            $whatsAppLink = $this->whatsAppInvoiceService->generateWhatsAppLink($order);
        }

        return view('admin.orders.show', compact('order', 'invoiceText', 'whatsAppLink'));
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

        $tautanWhatsApp = $order->status === OrderStatus::Pending
            ? null
            : $this->whatsAppInvoiceService->generateWhatsAppLink($order);

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
        $this->orderService->verifyOrder($order, $request->itemPrices());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Pesanan berhasil diverifikasi, harga & total sudah dikunci.');
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
}
