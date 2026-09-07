<?php

namespace App\Http\Controllers\Member;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeclarePaymentRequest;
use App\Http\Requests\Member\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\WhatsAppInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;



/**
 * Pemesanan oleh anggota (Modul 3). Anggota mengisi jumlah langsung di daftar
 * produk, tanpa keranjang, lalu kirim. Statusnya pending, atau langsung
 * terverifikasi kalau tidak ada produk fluktuatif di dalamnya.
 *
 * Versi non-anggotanya terpisah di NonMember\OrderController karena identitas
 * pemesannya beda.
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WhatsAppInvoiceService $whatsAppInvoiceService,
    ) {}

    /**
     * Form pemesanan, daftar produk aktif dengan input jumlah per produk.
     */
    public function create(): View
    {
        $member = Auth::guard('member')->user();
        $period = OrderPeriod::yangSedangDibuka();

        $productsByCategory = $period
            ? Product::aktif()->orderBy('name')->get()->groupBy('category')
            : collect();

        return view('member.orders.create', [
            'period' => $period,
            'productsByCategory' => $productsByCategory,
            // Alamat tersimpan anggota, dipakai sebagai isian awal kolom
            // alamat pengantaran, tapi tetap boleh diubah per pesanan.
            'alamatTersimpan' => $member->address,
        ]);
    }

    /**
     * Kirim pesanan baru.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $member = Auth::guard('member')->user();
        $period = OrderPeriod::yangSedangDibuka();

        // Dicek lagi di sini (bukan cuma di create()) buat jaga-jaga kalau
        // periode keburu ditutup admin saat form-nya masih terbuka.
        if ($blocked = $this->guardCanOrder($period)) {
            return $blocked;
        }

        $order = $this->orderService->createOrder(
            $member,
            $period,
            $request->orderedItems(),
            $request->deliveryMethod(),
            $request->deliveryAddress(),
        );

        return redirect()
            ->route('member.orders.show', $order)
            ->with('success', 'Pesanan berhasil dikirim! Koperasi akan belanjakan barangnya setelah periode ditutup.');
    }

    /**
     * Riwayat pesanan milik anggota yang sedang login.
     */
    public function index(): View
    {
        $member = Auth::guard('member')->user();

        $orders = Order::with('orderPeriod')
            ->where('member_id', $member->id)
            ->latest()
            ->get();

        return view('member.orders.index', compact('orders'));
    }

    /**
     * Detail 1 pesanan milik anggota yang sedang login.
     */
    public function show(Order $order): View
    {
        $member = Auth::guard('member')->user();

        // Anggota cuma boleh lihat pesanan miliknya sendiri.
        abort_unless($order->member_id === $member->id, 403);

        $order->load('orderItems.product', 'orderPeriod');

        // Struk baru bisa dibagikan setelah totalnya final. Pesanan yang masih
        // menunggu verifikasi harga belum punya angka pasti.
        $tautanBagikan = $order->status === OrderStatus::Pending
            ? null
            : $this->whatsAppInvoiceService->generateShareLink($order);

        return view('member.orders.show', compact('order', 'tautanBagikan'));
    }

    /**
     * Struk resmi pesanan, halaman tersendiri yang siap dicetak.
     *
     * Otorisasinya sama dengan show(): anggota cuma boleh membuka struk
     * miliknya sendiri.
     */
    public function struk(Order $order): View
    {
        $member = Auth::guard('member')->user();

        abort_unless($order->member_id === $member->id, 403);

        $order->load('orderItems.product', 'member', 'orderPeriod');

        // Tautan bagikan tanpa nomor tujuan, jadi anggota bebas memilih mau
        // dikirim ke siapa. Pesanan yang totalnya belum final tidak dikasih
        // tautan, karena angkanya masih bisa berubah.
        $tautanWhatsApp = $order->status === OrderStatus::Pending
            ? null
            : $this->whatsAppInvoiceService->generateShareLink($order);

        return view('struk.show', [
            'order' => $order,
            'tautanWhatsApp' => $tautanWhatsApp,
            'kembali' => route('member.orders.show', $order),
        ]);
    }

    /**
     * Anggota menyatakan sudah membayar lewat QRIS.
     *
     * Ini pernyataan, bukan bukti uang sudah masuk. Yang menentukan lunas
     * tetap pengurus setelah mencocokkan ke rekening.
     */
    public function declarePaid(DeclarePaymentRequest $request, Order $order): RedirectResponse
    {
        $member = Auth::guard('member')->user();

        abort_unless($order->member_id === $member->id, 403);

        $this->orderService->declarePaid($order, $request->file('payment_proof'));

        return redirect()
            ->route('member.orders.show', $order)
            ->with('success', 'Terima kasih. Pembayaranmu akan dicocokkan pengurus dengan rekening koperasi.');
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
        $member = Auth::guard('member')->user();

        abort_unless($order->member_id === $member->id, 403);

        abort_unless($order->payment_proof_path, 404);
        abort_unless(Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->response($order->payment_proof_path);
    }

    /**
     * Syaratnya cuma satu: periodenya masih dibuka. Kembalikan redirect kalau
     * gagal, null kalau boleh lanjut.
     *
     * Tidak ada batasan jumlah pesanan per anggota. Yang dibahas di rapat itu
     * kasus suami-istri dengan dua akun berbeda, dan kesimpulannya boleh saja
     * selama barangnya beda (CLAUDE.md, Lampiran A).
     */
    private function guardCanOrder(?OrderPeriod $period): ?RedirectResponse
    {
        if (! $period) {
            return redirect()
                ->route('member.orders.create')
                ->with('error', 'Periode pemesanan sudah ditutup, pesanan tidak bisa dikirim.');
        }

        return null;
    }
}
