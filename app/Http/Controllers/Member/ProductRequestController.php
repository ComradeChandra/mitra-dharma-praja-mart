<?php

namespace App\Http\Controllers\Member;

use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreProductRequestRequest;
use App\Models\ProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Permintaan produk baru dari anggota (Modul 8 di CLAUDE.md). Anggota
 * mengusulkan nama produk yang belum ada di katalog, admin nanti meninjau
 * lewat Admin\ProductRequestController.
 *
 * Khusus sisi ANGGOTA. Versi non-anggotanya ada di
 * NonMember\ProductRequestController, dipisah karena aturannya beda
 * (non-anggota menuliskan namanya sendiri & tidak punya halaman riwayat).
 */
class ProductRequestController extends Controller
{
    /**
     * Tampilkan riwayat permintaan produk milik anggota yang sedang login.
     */
    public function index(): View
    {
        $member = Auth::guard('member')->user();

        $productRequests = ProductRequest::where('member_id', $member->id)
            ->latest()
            ->get();

        return view('member.product-requests.index', compact('productRequests'));
    }

    public function create(): View
    {
        return view('member.product-requests.create');
    }

    public function store(StoreProductRequestRequest $request): RedirectResponse
    {
        $member = Auth::guard('member')->user();

        ProductRequest::create([
            'user_type' => UserType::Member,
            'member_id' => $member->id,
            // requester_label disimpan terpisah dari member_id supaya tetap
            // terbaca di daftar admin meski suatu saat data anggotanya dihapus.
            'requester_label' => $member->full_name,
            'product_name' => $request->validated('product_name'),
            'status' => ProductRequestStatus::Pending,
        ]);

        return redirect()
            ->route('member.product-requests.index')
            ->with('success', 'Permintaan produk berhasil dikirim. Admin akan meninjau usulan kamu.');
    }
}
