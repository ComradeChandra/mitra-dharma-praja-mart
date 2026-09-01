<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Peninjauan permintaan produk baru dari anggota/non-anggota (Modul 8 di
 * CLAUDE.md). Admin cuma bisa approve/reject di sini, kalau disetujui,
 * admin tetap harus menambahkan produknya secara manual lewat
 * Admin\ProductController (approve tidak otomatis bikin produk baru,
 * supaya admin tetap yang menentukan harga/foto/kategori-nya).
 */
class ProductRequestController extends Controller
{
    /**
     * Tampilkan semua permintaan, yang "pending" duluan di atas biar
     * langsung kelihatan mana yang perlu ditindaklanjuti.
     */
    public function index(): View
    {
        $productRequests = ProductRequest::orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(15);

        return view('admin.product-requests.index', compact('productRequests'));
    }

    public function approve(ProductRequest $productRequest): RedirectResponse
    {
        $productRequest->update(['status' => ProductRequestStatus::Approved]);

        return redirect()
            ->route('admin.product-requests.index')
            ->with('success', 'Permintaan produk disetujui. Jangan lupa tambahkan produknya ke katalog.');
    }

    public function reject(ProductRequest $productRequest): RedirectResponse
    {
        $productRequest->update(['status' => ProductRequestStatus::Rejected]);

        return redirect()
            ->route('admin.product-requests.index')
            ->with('success', 'Permintaan produk ditolak.');
    }
}
