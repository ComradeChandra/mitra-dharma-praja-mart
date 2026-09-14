<?php

namespace App\Http\Controllers\NonMember;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeclarePaymentRequest;
use App\Http\Requests\NonMember\StoreOrderRequest;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\NonMemberSessionService;
use App\Services\OrderLinkService;
use App\Services\OrderService;
use App\Services\WhatsAppInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pemesanan oleh non-anggota. Alurnya mirip versi anggota, dengan dua beda:
 *
 * Nama dan nomor WA diketik manual di form, karena non-anggota tidak punya
 * akun yang menyimpan data itu. Dan tidak ada halaman riwayat pesanan lintas
 * waktu, karena kode akses OPD dipakai bersama sekantor sehingga tidak ada
 * identitas personal yang bisa dipakai memfilter "pesanan saya". Yang dipakai
 * sesi berjalan, lihat pastikanPesanannya() di bawah. Setelah kirim, pemesan
 * langsung diarahkan ke detail pesanannya sebagai struk.
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private NonMemberSessionService $sesiNonAnggota,
        private WhatsAppInvoiceService $whatsAppInvoiceService,
        private OrderLinkService $tautanPesanan,
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

        // Cuma pesanan yang dikirim dari sesi ini. Kode akses OPD dipakai
        // sekantor, jadi pesanan OPD yang sama belum tentu milik orang ini.
        $pesananTerkirim = $period
            ? Order::whereIn('id', $this->sesiNonAnggota->daftarPesanan())
                ->where('opd_id', $opd->id)
                ->where('order_period_id', $period->id)
                ->latest()
                ->get()
            : collect();

        return view('non-member.orders.create', compact('opd', 'period', 'productsByCategory', 'pesananTerkirim'));
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

        // Dicatat ke sesi supaya orang ini bisa membukanya lagi. Kode akses
        // OPD dipakai bersama sekantor, jadi opd_id saja tidak cukup buat
        // menentukan siapa pemiliknya.
        $this->sesiNonAnggota->catatPesanan($order->id);

        return redirect()
            ->to($this->tautanPesanan->untukPemesan($order))
            ->with('success', 'Pesanan berhasil dikirim! Koperasi akan belanjakan barangnya setelah periode ditutup.');
    }

    /**
     * Detail pesanan, dipakai sebagai struk setelah kirim.
     *
     * Yang boleh membuka cuma orang yang mengirim pesanan ini, lihat
     * pastikanPesanannya().
     */
    public function show(Request $request, Order $order): View
    {
        $this->klaimLewatTautan($request, $order);
        $this->pastikanPesanannya($order);

        $order->load('orderItems.product', 'orderPeriod');

        return view('non-member.orders.show', compact('order'));
    }

    /**
     * Struk resmi pesanan, halaman tersendiri yang siap dicetak.
     *
     * Otorisasinya sama dengan show().
     */
    public function struk(Order $order): View
    {
        $this->pastikanPesanannya($order);

        $order->load('orderItems.product', 'opdDepartment', 'orderPeriod');

        $tautanWhatsApp = $order->status === OrderStatus::Pending
            ? null
            : $this->whatsAppInvoiceService->generateShareLink($order);

        return view('struk.show', [
            'order' => $order,
            'tautanWhatsApp' => $tautanWhatsApp,
            'kembali' => $this->tautanPesanan->untukPemesan($order),
        ]);
    }

    /**
     * Non-anggota menyatakan sudah membayar lewat QRIS.
     */
    public function declarePaid(DeclarePaymentRequest $request, Order $order): RedirectResponse
    {
        $this->pastikanPesanannya($order);

        $this->orderService->declarePaid($order, $request->file('payment_proof'));

        return redirect()
            ->to($this->tautanPesanan->untukPemesan($order))
            ->with('success', 'Terima kasih. Pembayaran akan dicocokkan pengurus dengan rekening koperasi.');
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
        $this->pastikanPesanannya($order);

        abort_unless($order->payment_proof_path, 404);
        abort_unless(Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->response($order->payment_proof_path);
    }

    /**
     * Terima kembali pemesan yang datang lewat tautan bertanda tangan.
     *
     * Tanda tangan yang sah membuktikan tautannya memang dari kami, jadi
     * pesanannya dicatat ulang ke sesi. Setelah itu tombol struk dan
     * pembayaran di halaman tersebut ikut jalan tanpa perlu ikut ditandatangani
     * satu per satu.
     *
     * OPD-nya tetap dicek: tautan yang bocor ke kantor lain tidak boleh bisa
     * dipakai.
     */
    private function klaimLewatTautan(Request $request, Order $order): void
    {
        // Relative: pasangan OrderLinkService, yang menandatangani tanpa domain.
        if ($request->hasValidRelativeSignature() && $order->opd_id === $this->opdSedangLogin()->id) {
            $this->sesiNonAnggota->catatPesanan($order->id);
        }
    }

    /**
     * Pastikan pesanan ini memang dibuat orang yang sedang membuka halaman.
     *
     * Dua lapis. Pertama pesanannya harus milik OPD yang sedang masuk, kedua
     * harus tercatat di sesi orang ini. Lapis kedua yang penting: kode akses
     * OPD dipakai bersama sekantor, jadi tanpa itu siapa pun yang punya kode
     * kantor bisa membuka pesanan rekannya cuma dengan menaikkan angka di URL,
     * termasuk bukti transfer yang memuat nomor rekening.
     *
     * Non-anggota memang tidak punya akun personal, dan itu keputusan yang
     * sudah disetujui, jadi yang bisa dipakai membatasi cuma sesinya.
     */
    private function pastikanPesanannya(Order $order): void
    {
        $opd = $this->opdSedangLogin();

        abort_unless($order->opd_id === $opd->id, 403);
        abort_unless($this->sesiNonAnggota->pesanannya($order->id), 403);
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
