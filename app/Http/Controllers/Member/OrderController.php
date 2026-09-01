<?php

namespace App\Http\Controllers\Member;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\WhatsAppInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
