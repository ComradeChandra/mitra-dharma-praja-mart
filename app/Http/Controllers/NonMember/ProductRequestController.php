<?php

namespace App\Http\Controllers\NonMember;

use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\NonMember\StoreProductRequestRequest;
use App\Models\ProductRequest;
use App\Services\NonMemberSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Permintaan produk baru dari non-anggota (Modul 8 di CLAUDE.md).
 *
 * Mirip Member\ProductRequestController, TAPI:
 * - Tidak ada halaman riwayat (index). Sama alasannya dengan pesanan
 *   non-anggota: tidak ada identitas personal yang stabil buat difilter
 *   "punya saya", yang ada cuma sesi OPD yang dipakai bersama sekantor.
 * - `member_id` dikosongkan; identitas pemohon disimpan di `requester_label`
 *   berupa "Nama (OPD)" supaya pengurus tahu usulan itu dari siapa & dari
 *   instansi mana.
 */
class ProductRequestController extends Controller
{
    public function __construct(private NonMemberSessionService $sesiNonAnggota) {}

    public function create(): View
    {
        return view('non-member.product-requests.create', [
            'opd' => $this->sesiNonAnggota->opd(),
        ]);
    }

    public function store(StoreProductRequestRequest $request): RedirectResponse
    {
        $opd = $this->sesiNonAnggota->opd();

        ProductRequest::create([
            'user_type' => UserType::NonMember,
            'member_id' => null,
            // Nama + OPD digabung di sini karena tabel product_requests tidak
            // punya kolom opd_id, dan memang tidak perlu, yang dibutuhkan
            // pengurus cuma tahu usulan ini datang dari siapa.
            'requester_label' => $request->validated('requester_name').' ('.$opd->name.')',
            'product_name' => $request->validated('product_name'),
            'status' => ProductRequestStatus::Pending,
        ]);

        return redirect()
            ->route('non-member.product-requests.create')
            ->with('success', 'Usulan produk berhasil dikirim. Pengurus koperasi akan meninjaunya.');
    }
}
