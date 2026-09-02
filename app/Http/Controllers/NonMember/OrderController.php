<?php

namespace App\Http\Controllers\NonMember;

use App\Http\Controllers\Controller;
use App\Http\Requests\NonMember\StoreOrderRequest;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\NonMemberSessionService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pemesanan oleh non-anggota. Alurnya mirip versi anggota, dengan dua beda:
 *
 * Nama dan nomor WA diketik manual di form, karena non-anggota tidak punya
 * akun yang menyimpan data itu. Dan tidak ada halaman riwayat pesanan, karena
 * kode akses OPD dipakai bersama sekantor sehingga tidak ada identitas
 * personal yang bisa dipakai memfilter "pesanan saya". Setelah kirim, pemesan
 * langsung diarahkan ke detail pesanannya sebagai struk.
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private NonMemberSessionService $sesiNonAnggota,
    ) {}

    /**
     * Form pemesanan, daftar produk aktif dengan input jumlah per produk,
     * plus field nama & nomor WA (lihat catatan class di atas).
     */
    public function create(): View
    {
        $opd = $this->opdSedangLogin();
        $period = OrderPeriod::yangSedangDibuka();

        $productsByCategory = $period
            ? Product::aktif()->orderBy('name')->get()->groupBy('category')
            : collect();

        return view('non-member.orders.create', compact('opd', 'period', 'productsByCategory'));
    }

    /**
     * Kirim pesanan baru.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $opd = $this->opdSedangLogin();
        $period = OrderPeriod::yangSedangDibuka();

        if (! $period) {
            return redirect()
                ->route('non-member.orders.create')
                ->with('error', 'Periode pemesanan sudah ditutup, pesanan tidak bisa dikirim.');
        }

        $order = $this->orderService->createNonMemberOrder(
            $opd,
            $request->string('non_member_name')->toString(),
            $request->string('whatsapp_number')->toString(),
            $period,
            $request->orderedItems(),
            $request->deliveryMethod(),
            $request->deliveryAddress(),
        );

        return redirect()
            ->route('non-member.orders.show', $order)
            ->with('success', 'Pesanan berhasil dikirim! Koperasi akan belanjakan barangnya setelah periode ditutup.');
    }

    /**
     * Detail pesanan, dipakai sebagai struk setelah kirim.
     *
     * Otorisasinya per OPD, bukan per orang. Siapa pun yang masuk dengan kode
     * akses OPD yang sama boleh melihat pesanan OPD itu, tapi tidak bisa
     * melihat punya OPD lain.
     */
    public function show(Order $order): View
    {
        $opd = $this->opdSedangLogin();

        abort_unless($order->opd_id === $opd->id, 403);

        $order->load('orderItems.product', 'orderPeriod');

        return view('non-member.orders.show', compact('order'));
    }

    /**
     * OPD yang sedang aktif di sesi ini.
     *
     * Middleware non-member.session sudah memastikan datanya ada dan valid.
     * abort() di bawah cuma jaring pengaman kalau nanti ada route baru yang
     * lupa dipasangi middleware itu, biar gagalnya jelas bukan diam-diam.
     */
    private function opdSedangLogin(): OpdDepartment
    {
        return $this->sesiNonAnggota->opd()
            ?? abort(403, 'Sesi non-anggota tidak ditemukan.');
    }
}
