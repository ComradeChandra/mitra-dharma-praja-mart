<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

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

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                // Produk non-fluktuatif: harga langsung dikunci pakai harga
                // jual saat ini. Produk fluktuatif (mis. telur, sayur):
                // dikosongkan dulu, baru diisi admin saat verifikasi.
                'price_at_order' => $product->is_fluctuating ? null : $product->sell_price,
            ]);

            // Stok cuma dikurangi untuk produk yang memang dilacak. Produk
            // pre-order murni tidak punya angka stok. Hasilnya cuma kelihatan
            // admin, pelanggan lihat status lewat Product::isAvailable().
            if ($product->has_stock_tracking) {
                $product->decrement('stock', $item['quantity']);
            }
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
