<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika pemesanan (Modul 3). Dipisah dari controller sesuai aturan service
 * layer di CLAUDE.md.
 *
 * Isinya empat hal: membuat pesanan anggota, membuat pesanan non-anggota,
 * mengunci harga produk fluktuatif saat admin verifikasi, dan menandai
 * invoice sudah dikirim.
 */
class OrderService
{
    /**
     * Penyimpan gambar dipakai buat bukti transfer yang dilampirkan pemesan.
     * Dipinjam dari service yang sama yang dipakai foto produk & foto profil,
     * jadi aturan penyimpanannya seragam.
     */
    public function __construct(
        private ImageStorageService $imageStorage,
    ) {}

    /**
     * Buat pesanan baru dari anggota yang sedang login.
     *
     * @param  Member  $member  anggota yang memesan
     * @param  OrderPeriod  $period  periode pemesanan yang sedang dibuka
     * @param  array<int, array{product_id: int, quantity: int}>  $items  daftar produk & jumlah (sudah difilter qty > 0 di Form Request)
     */
    public function createOrder(
        Member $member,
        OrderPeriod $period,
        array $items,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
    ): Order {
        return DB::transaction(function () use ($member, $period, $items, $deliveryMethod, $deliveryAddress) {
            $order = Order::create([
                'order_period_id' => $period->id,
                'user_type' => UserType::Member,
                'member_id' => $member->id,
                'whatsapp_number' => $member->whatsapp_number,
                // Alamat disalin ke pesanan, bukan mengacu ke members.address,
                // supaya perubahan alamat profil tidak mengubah riwayat lama.
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'status' => OrderStatus::Pending,
            ]);

            $this->createOrderItemsFor($order, $items);
            $this->recalculateIfComplete($order);

            return $order->fresh('orderItems');
        });
    }

    /**
     * Buat pesanan dari non-anggota. Bedanya dengan createOrder() cuma di data
     * pemesan: nama dan nomor WA diketik di form, plus opd_id. Pengisian item
     * dan penguncian harganya sama persis.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function createNonMemberOrder(
        OpdDepartment $opd,
        string $nonMemberName,
        string $whatsappNumber,
        OrderPeriod $period,
        array $items,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
    ): Order {
        return DB::transaction(function () use ($opd, $nonMemberName, $whatsappNumber, $period, $items, $deliveryMethod, $deliveryAddress) {
            $order = Order::create([
                'order_period_id' => $period->id,
                'user_type' => UserType::NonMember,
                'non_member_name' => $nonMemberName,
                'opd_id' => $opd->id,
                'whatsapp_number' => $whatsappNumber,
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'status' => OrderStatus::Pending,
            ]);

            $this->createOrderItemsFor($order, $items);
            $this->recalculateIfComplete($order);

            return $order->fresh('orderItems');
        });
    }

    /**
     * Isi harga produk fluktuatif yang masih kosong, hitung ulang total, lalu
     * tandai pesanan sebagai terverifikasi.
     *
     * @param  array<int, numeric-string|float>  $prices  [order_item_id => harga]
     */
    public function verifyOrder(Order $order, array $prices): Order
    {
        return DB::transaction(function () use ($order, $prices) {
            foreach ($prices as $orderItemId => $price) {
                $order->orderItems()
                    ->whereKey($orderItemId)
                    ->update(['price_at_order' => $price]);
            }

            $order->refresh();
            $total = $order->orderItems->sum(fn ($item) => $item->quantity * $item->price_at_order);

            $order->update([
                'total_amount' => $total,
                'status' => OrderStatus::Verified,
            ]);

            return $order->fresh('orderItems');
        });
    }


    /**
     * Pemesan menyatakan sudah membayar lewat QRIS.
     *
     * Ini PERNYATAAN, bukan bukti uang sudah masuk. QRIS koperasi itu QRIS
     * statis, jadi tidak ada webhook yang memberi tahu aplikasi. Yang
     * menentukan lunas tetap pengurus setelah mencocokkan ke mutasi rekening
     * (lihat confirmPayment di bawah).
     *
     * Cuma boleh kalau totalnya sudah final. Pesanan yang masih memuat produk
     * fluktuatif belum punya angka yang bisa dibayar.
     */
    public function declarePaid(Order $order, ?UploadedFile $bukti = null): Order
    {
        abort_if($order->total_amount === null, 422, 'Nominalnya belum final, pengurus belum mengunci harga.');
        abort_if($order->payment_status !== PaymentStatus::Unpaid, 422, 'Pembayaran pesanan ini sudah pernah dinyatakan.');

        $order->update([
            'payment_status' => PaymentStatus::AwaitingConfirmation,
            'paid_declared_at' => now(),
            // Bukti transfer opsional. Yang melampirkan memudahkan pengurus
            // mencocokkan; yang tidak, tetap dicek lewat mutasi.
            'payment_proof_path' => $this->imageStorage->store($bukti, 'payment-proofs'),
        ]);

        return $order;
    }

    /**
     * Pengurus mencocokkan ke rekening lalu menyatakan lunas.
     *
     * Sengaja tidak menuntut pemesan menyatakan dulu: kadang orang membayar
     * tanpa menekan tombol apa pun, dan pengurus tetap harus bisa menandainya
     * setelah melihat uangnya masuk.
     */
    public function confirmPayment(Order $order): Order
    {
        abort_if($order->total_amount === null, 422, 'Nominalnya belum final, pengurus belum mengunci harga.');

        $order->update([
            'payment_status' => PaymentStatus::Paid,
            'payment_confirmed_at' => now(),
        ]);

        return $order;
    }
    /**
     * Tandai invoice sudah dikirim. Ini konfirmasi manual dari admin setelah
     * dia menekan kirim di WhatsApp-nya sendiri; sistem tidak punya cara tahu
     * pesannya benar-benar terkirim karena cuma memakai tautan wa.me.
     *
     * Cuma pesanan terverifikasi yang boleh ditandai, karena pesanan pending
     * belum punya total yang pasti.
     */
    public function markAsInvoiced(Order $order): Order
    {
        abort_unless($order->status === OrderStatus::Verified, 422, 'Pesanan harus terverifikasi dulu sebelum bisa ditandai invoice terkirim.');

        $order->update(['status' => OrderStatus::Invoiced]);

        return $order;
    }

    /**
     * Isi baris order_items. Dipakai bareng pesanan anggota & non-anggota
     * supaya logika penguncian harganya tidak ada dua salinan.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function createOrderItemsFor(Order $order, array $items): void
    {
        // Ambil semua produk yang dipesan sekaligus (1 query), biar tidak
        // query berulang di dalam loop.
        $products = Product::whereIn('id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('id');

        // Diurutkan per id produk supaya dua pesanan bersamaan yang isinya
        // sama selalu mengunci barisnya dengan urutan yang sama. Kalau
        // urutannya bisa berbeda, keduanya bisa saling menunggu.
        usort($items, fn (array $a, array $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            // Stok dikurangi DULU, baru itemnya disimpan, dan urutan ini
            // penting. Menyimpan order_item membuat database mengambil kunci
            // baca pada baris produk karena ada relasi ke sana. Kalau
            // pengurangan stok (yang butuh kunci tulis) dilakukan sesudahnya,
            // dua pesanan bersamaan sama-sama memegang kunci baca lalu
            // sama-sama menunggu kunci tulis, dan keduanya macet.
            //
            // Cuma berlaku buat produk yang stoknya dilacak. Produk pre-order
            // murni tidak punya angka stok untuk dikurangi.
            if ($product->has_stock_tracking) {
                $this->kurangiStok($product, $item['quantity']);
            }

            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                // Produk non-fluktuatif: harga langsung dikunci pakai harga
                // jual saat ini. Produk fluktuatif (mis. telur, sayur):
                // dikosongkan dulu, baru diisi admin saat verifikasi.
                'price_at_order' => $product->is_fluctuating ? null : $product->sell_price,
            ]);
        }
    }

    /**
     * Kurangi stok dengan syarat stoknya memang masih cukup.
     *
     * Syaratnya ditaruh di klausa WHERE, bukan dicek di PHP lebih dulu.
     * Form Request sudah menolak pesanan yang melebihi stok, tapi
     * pengecekannya membaca stok sebelum transaksi dimulai. Kalau dua orang
     * memesan barang yang sama pada saat bersamaan, keduanya bisa lolos
     * pengecekan itu lalu sama-sama mengurangi, dan stoknya jadi minus.
     *
     * Dengan syarat di WHERE, database sendiri yang memutuskan siapa yang
     * kebagian. Kalau tidak ada baris yang terpengaruh berarti stoknya keburu
     * habis, dan pesanannya dibatalkan seluruhnya karena masih di dalam
     * DB::transaction().
     */
    private function kurangiStok(Product $product, int $jumlah): void
    {
        $berhasil = Product::whereKey($product->id)
            ->where('stock', '>=', $jumlah)
            ->decrement('stock', $jumlah);

        if ($berhasil === 0) {
            throw ValidationException::withMessages([
                'quantity' => "Stok {$product->name} keburu habis dipesan orang lain. Coba kurangi jumlahnya.",
            ]);
        }
    }

    /**
     * Kalau semua item sudah punya harga, total langsung dihitung dan pesanan
     * ditandai terverifikasi tanpa perlu admin membukanya dulu.
     */
    private function recalculateIfComplete(Order $order): void
    {
        $order->load('orderItems');

        $adaYangBelumBerharga = $order->orderItems->contains(
            fn ($item) => $item->price_at_order === null
        );

        if ($adaYangBelumBerharga) {
            return;
        }

        $total = $order->orderItems->sum(fn ($item) => $item->quantity * $item->price_at_order);

        $order->update([
            'total_amount' => $total,
            'status' => OrderStatus::Verified,
        ]);
    }
}
