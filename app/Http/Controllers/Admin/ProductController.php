<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\ImageStorageService;
use App\Services\KategoriProdukService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function __construct(
        private ImageStorageService $imageStorage,
        private KategoriProdukService $kategori,
    ) {}

    public function index(Request $request): View
    {
        // Scope cari() sudah dipakai katalog publik, dipakai ulang di sini
        // supaya aturan pencocokannya cuma ada satu.
        $cari = $request->string('cari')->trim()->toString();

        $products = Product::cari($cari)->latest()->paginate(15)->withQueryString();

        return view('admin.products.index', compact('products', 'cari'));
    }

    public function create(): View
    {
        // Kategori yang sudah dipakai, jadi saran di kotak "Kategori".
        $daftarKategori = $this->kategori->daftar();

        return view('admin.products.create', compact('daftarKategori'));
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
        $daftarKategori = $this->kategori->daftar();

        return view('admin.products.edit', compact('product', 'daftarKategori'));
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
        // Produk yang sudah pernah dipesan tidak boleh dihapus: namanya dipakai
        // riwayat pesanan, invoice, struk, dan rekap, dan database pun
        // menolaknya. Dulu fotonya dihapus DULUAN, lalu penghapusan produknya
        // gagal dengan error 500: produknya tetap ada, fotonya hilang.
        if ($product->orderItems()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', "\"{$product->name}\" sudah pernah dipesan, jadi tidak bisa dihapus supaya riwayat pesanan tetap utuh. Kalau tidak dijual lagi, nonaktifkan saja lewat Edit.");
        }

        $this->imageStorage->delete($product->image_path);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
