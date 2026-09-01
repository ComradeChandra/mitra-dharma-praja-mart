<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD katalog produk untuk admin, termasuk upload foto opsional.
 *
 * Method aksi (index/store/update/destroy) sengaja dibikin pendek, urusan
 * simpan/hapus berkas foto ditangani ImageStorageService, yang dipakai bareng
 * dengan foto profil anggota (Member\ProfileController) supaya logikanya
 * tidak dobel.
 */
class ProductController extends Controller
{
    public function __construct(private ImageStorageService $imageStorage) {}

    public function index(): View
    {
        $products = Product::latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create([
            ...$request->validated(),
            'is_fluctuating' => $request->boolean('is_fluctuating'),
            'has_stock_tracking' => $request->boolean('has_stock_tracking'),
            'is_active' => $request->boolean('is_active'),
            'image_path' => $this->imageStorage->store($request->file('image'), 'products'),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk baru berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update([
            ...$request->validated(),
            'is_fluctuating' => $request->boolean('is_fluctuating'),
            'has_stock_tracking' => $request->boolean('has_stock_tracking'),
            'is_active' => $request->boolean('is_active'),
            // Foto lama dipertahankan kalau admin tidak mengunggah yang baru
            'image_path' => $this->imageStorage->replace(
                $request->file('image'),
                $product->image_path,
                'products',
            ),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Hapus produk sekaligus file fotonya (kalau ada). Catatan: kalau produk
     * ini sudah pernah dipesan (order_items.product_id), database akan
     * menolak hapus lewat foreign key constraint, di Tahap 2 ini kita belum
     * menangkap error itu secara khusus, cukup pakai flash message umum;
     * bisa ditambah penanganan seperti di OrderPeriodController kalau perlu.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->imageStorage->delete($product->image_path);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

}
