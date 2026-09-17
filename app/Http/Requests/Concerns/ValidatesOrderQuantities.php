<?php

namespace App\Http\Requests\Concerns;

use App\Enums\DeliveryMethod;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;

/**
 * Validasi field "quantity[{product_id}]" (isi jumlah per produk) DAN cara
 * penerimaan barang (diantar/ambil sendiri), dipakai BARENG oleh
 * Member\StoreOrderRequest & NonMember\StoreOrderRequest, soalnya bentuk form
 * pesan produknya identik untuk anggota maupun non-anggota (cuma beda di
 * field tambahan: non-anggota juga isi nama & nomor WA sendiri).
 * Dipusatkan di sini biar tidak ada 2 salinan logic validasi yang sama persis.
 */
trait ValidatesOrderQuantities
{
    /**
     * Aturan cara penerimaan barang.
     *
     * Alamat wajib diisi cuma kalau metodenya "antar", kalau "ambil",
     * pemesan datang sendiri ke koperasi jadi tidak ada alamat yang perlu
     * dicatat. `exclude_if` dipakai supaya waktu metodenya "ambil", field
     * alamat benar-benar dibuang dari data tervalidasi (bukan sekadar
     * dikosongkan), jadi tidak ada alamat nyasar yang ikut tersimpan.
     */
    protected function deliveryRules(): array
    {
        return [
            'delivery_method' => ['required', new Enum(DeliveryMethod::class)],
            'delivery_address' => [
                'exclude_if:delivery_method,'.DeliveryMethod::Ambil->value,
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    protected function deliveryMessages(): array
    {
        return [
            'delivery_method.required' => 'Pilih dulu mau diantar atau ambil sendiri di koperasi.',
            'delivery_method.Illuminate\Validation\Rules\Enum' => 'Pilihan cara terima barang tidak dikenali.',
            'delivery_address.required' => 'Alamat pengantaran wajib diisi kalau barang mau diantar.',
        ];
    }

    /**
     * Cara penerimaan yang dipilih, sudah jadi enum.
     */
    public function deliveryMethod(): DeliveryMethod
    {
        return DeliveryMethod::from($this->input('delivery_method'));
    }

    /**
     * Alamat pengantaran, null kalau pemesan memilih ambil sendiri.
     */
    public function deliveryAddress(): ?string
    {
        return $this->deliveryMethod()->butuhAlamat()
            ? $this->string('delivery_address')->trim()->toString()
            : null;
    }

    protected function quantityRules(): array
    {
        return [
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function quantityMessages(): array
    {
        return [
            'quantity.required' => 'Data pesanan tidak ditemukan, coba muat ulang halaman.',
            'quantity.*.integer' => 'Jumlah pesanan harus berupa angka bulat.',
            'quantity.*.min' => 'Jumlah pesanan tidak boleh negatif.',
            'quantity.*.max' => 'Jumlah maksimal 999 per produk. Cek lagi, mungkin salah ketik.',
        ];
    }

    /**
     * Validasi tambahan yang butuh cek ke database & logic sederhana (bukan
     * cuma bentuk data), tetap wajar ditaruh di Form Request karena ini
     * masih soal "apakah input ini valid", bukan proses bisnis pemesanan.
     */
    protected function validateQuantities(Validator $validator): void
    {
        $quantities = collect($this->input('quantity', []))
            ->filter(fn ($qty) => (int) $qty > 0);

        // Minimal harus ada 1 produk yang diisi jumlahnya (lebih dari 0).
        if ($quantities->isEmpty()) {
            $validator->errors()->add('quantity', 'Isi jumlah minimal 1 produk sebelum kirim pesanan.');

            return;
        }

        // Semua product_id yang dikirim harus produk yang beneran ada & aktif —
        // jaga-jaga kalau ada yang mengutak-atik form (mis. lewat DevTools).
        $products = Product::where('is_active', true)
            ->whereIn('id', $quantities->keys())
            ->get()
            ->keyBy('id');

        if ($products->count() !== $quantities->keys()->count()) {
            $validator->errors()->add('quantity', 'Ada produk yang dipesan sudah tidak tersedia, coba muat ulang halaman.');

            return;
        }

        // Sengaja TIDAK ada pembatasan terhadap stok di sini. Ini sistem
        // pre-order: koperasi baru belanja setelah pesanan terkumpul, dan
        // pemesan pun tidak melihat angka stok, jadi pesanan tidak boleh
        // ditolak karena melebihi stok (keputusan Chandra, 16 Sep 2026, dari
        // masukan tim Cimahi Technopark — lihat CLAUDE.md). Stok tetap
        // berkurang di OrderService (boleh jadi minus) sebagai informasi
        // bagi pengurus, bukan penghalang pemesanan.
    }

    /**
     * Ambil daftar item yang siap disimpan (product_id + quantity), sudah
     * difilter cuma yang jumlahnya > 0, dipakai OrderService::createOrder()
     * / createNonMemberOrder().
     *
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function orderedItems(): array
    {
        return collect($this->input('quantity', []))
            ->filter(fn ($qty) => (int) $qty > 0)
            ->map(fn ($qty, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) $qty,
            ])
            ->values()
            ->all();
    }
}
