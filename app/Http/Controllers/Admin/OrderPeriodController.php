<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderPeriodRequest;
use App\Http\Requests\Admin\UpdateOrderPeriodRequest;
use App\Models\OrderPeriod;
use App\Services\RecapService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD periode pemesanan untuk admin, jendela waktu kapan anggota/non-anggota
 * boleh mengirim pesanan (lihat CLAUDE.md, Modul 4: Periode Pemesanan).
 * Juga nampilin rekap belanja grosir per periode (Modul 6) lewat rekap().
 */
class OrderPeriodController extends Controller
{
    public function __construct(private RecapService $recapService) {}

    /**
     * Tampilkan daftar periode, yang terbaru dulu.
     */
    public function index(): View
    {
        $orderPeriods = OrderPeriod::latest('start_date')->paginate(15);

        return view('admin.order-periods.index', compact('orderPeriods'));
    }

    public function create(): View
    {
        return view('admin.order-periods.create');
    }

    public function store(StoreOrderPeriodRequest $request): RedirectResponse
    {
        OrderPeriod::create($request->validated());

        return redirect()
            ->route('admin.order-periods.index')
            ->with('success', 'Periode pemesanan baru berhasil dibuat.');
    }

    public function edit(OrderPeriod $orderPeriod): View
    {
        return view('admin.order-periods.edit', compact('orderPeriod'));
    }

    public function update(UpdateOrderPeriodRequest $request, OrderPeriod $orderPeriod): RedirectResponse
    {
        $orderPeriod->update($request->validated());

        return redirect()
            ->route('admin.order-periods.index')
            ->with('success', 'Periode pemesanan berhasil diperbarui.');
    }

    /**
     * Hapus periode. Kalau periode ini sudah punya pesanan terkait
     * (orders.order_period_id wajib diisi, tidak nullable), database akan
     * menolak penghapusan lewat foreign key constraint, ini sengaja supaya
     * riwayat pesanan tidak pernah kehilangan induk periodenya. Kita tangkap
     * error itu di sini dan tampilkan pesan yang jelas, bukan error teknis.
     */
    public function destroy(OrderPeriod $orderPeriod): RedirectResponse
    {
        try {
            $orderPeriod->delete();
        } catch (QueryException) {
            return redirect()
                ->route('admin.order-periods.index')
                ->with('error', 'Periode ini tidak bisa dihapus karena sudah ada pesanan yang masuk di dalamnya.');
        }

        return redirect()
            ->route('admin.order-periods.index')
            ->with('success', 'Periode pemesanan berhasil dihapus.');
    }

    /**
     * Rekap lengkap 1 periode, memenuhi KETIGA jenis rekap yang diminta
     * Modul 6 di CLAUDE.md dalam satu halaman bertab:
     * 1. kolektif per-produk (belanja grosir) + daftar pemesan tiap produk,
     * 2. distribusi per-OPD + daftar nama pemesannya,
     * 3. status belanja anggota (siapa sudah & belum kirim pesanan).
     *
     * Rekap per-individu (invoice) sendiri ada di Admin\OrderController::show().
     */
    public function rekap(OrderPeriod $orderPeriod): View
    {
        $products = $this->recapService->productsForPeriod($orderPeriod);
        $opdRecap = $this->recapService->opdRecapForPeriod($orderPeriod);
        $memberStatus = $this->recapService->memberOrderStatusForPeriod($orderPeriod);

        return view('admin.order-periods.rekap', compact(
            'orderPeriod',
            'products',
            'opdRecap',
            'memberStatus',
        ));
    }
}
