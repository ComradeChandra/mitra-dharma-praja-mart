<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan Agustus 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->telur = Product::create([
        'category' => 'Sayur & Segar',
        'name' => 'Telur Ayam 1kg',
        'buy_price' => 28000,
        'sell_price' => null,
        'is_fluctuating' => true,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);
});

test('admin bisa lihat daftar pesanan', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSee('Anggota Uji');
});

test('admin bisa verifikasi pesanan & mengunci harga produk fluktuatif', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);
    $item = $order->orderItems()->create([
        'product_id' => $this->telur->id,
        'quantity' => 2,
        'price_at_order' => null,
    ]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.orders.verify', $order), [
            'prices' => [$item->id => 30000],
        ]);

    $response->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Verified);
    expect((float) $order->total_amount)->toBe(60000.0); // 2 x 30.000
    expect((float) $order->orderItems->first()->price_at_order)->toBe(30000.0);
});

test('verifikasi ditolak kalau harga produk fluktuatif tidak diisi', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);
    $item = $order->orderItems()->create([
        'product_id' => $this->telur->id,
        'quantity' => 2,
        'price_at_order' => null,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.verify', $order), ['prices' => []])
        ->assertSessionHasErrors("prices.{$item->id}");

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('tamu tidak bisa akses daftar pesanan admin', function () {
    $this->get(route('admin.orders.index'))
        ->assertRedirect(route('login'));
});

test('halaman detail pesanan yang sudah verified nampilin preview invoice', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 60000,
    ]);
    $order->orderItems()->create([
        'product_id' => $this->telur->id,
        'quantity' => 2,
        'price_at_order' => 30000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Invoice WhatsApp')
        ->assertSee('Kirim via WhatsApp');
});

test('halaman detail pesanan yang masih pending TIDAK nampilin invoice', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);
    $order->orderItems()->create([
        'product_id' => $this->telur->id,
        'quantity' => 2,
        'price_at_order' => null,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertDontSee('Invoice WhatsApp');
});

test('admin bisa menandai pesanan verified sebagai invoice terkirim', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 60000,
    ]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.orders.mark-invoiced', $order));

    $response->assertRedirect(route('admin.orders.show', $order));
    expect($order->fresh()->status)->toBe(OrderStatus::Invoiced);
});

test('pesanan yang masih pending tidak bisa ditandai invoice terkirim', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.mark-invoiced', $order))
        ->assertStatus(422);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('admin bisa lihat rekap belanja grosir 1 periode', function () {
    $order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 60000,
    ]);
    $order->orderItems()->create([
        'product_id' => $this->telur->id,
        'quantity' => 2,
        'price_at_order' => 30000,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.order-periods.rekap', $this->periode))
        ->assertOk()
        ->assertSee('Telur Ayam 1kg')
        ->assertSee('2'); // jumlah dibutuhkan
});

test('tab "Semua" tersorot saat daftar pesanan dibuka tanpa penyaring', function () {
    // Kunci array null di PHP berubah jadi string kosong, jadi dulu tab
    // "Semua" tidak pernah dianggap aktif: pengurus membuka halaman dan
    // tidak ada satu tab pun yang tersorot.
    $html = $this->actingAs($this->admin)->get(route('admin.orders.index'))->getContent();

    expect($html)->toMatch('/bg-emerald-700 text-white[^>]*>\s*Semua\s*</');
    expect($html)->toMatch('/bg-emerald-600 text-white[^>]*>\s*Semua\s*</');
});

test('tab penyaring yang dipilih tersorot, tab "Semua" tidak', function () {
    $html = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'pending']))->getContent();

    expect($html)->toMatch('/bg-emerald-700 text-white[^>]*>\s*Menunggu Verifikasi\s*</');
    expect($html)->not->toMatch('/bg-emerald-700 text-white[^>]*>\s*Semua\s*</');
});
