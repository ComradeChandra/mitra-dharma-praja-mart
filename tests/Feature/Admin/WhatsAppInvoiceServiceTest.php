<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\WhatsAppInvoiceService;

beforeEach(function () {
    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan Agustus 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->beras = Product::create([
        'category' => 'Sembako',
        'name' => 'Beras 5kg',
        'buy_price' => 65000,
        'sell_price' => 70000,
        'is_fluctuating' => false,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);

    $this->member = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '08123456789', // sengaja format lokal (awalan 0), buat tes normalisasi nomor
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->order = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 140000,
    ]);
    $this->order->orderItems()->create([
        'product_id' => $this->beras->id,
        'quantity' => 2,
        'price_at_order' => 70000,
    ]);

    $this->service = app(WhatsAppInvoiceService::class);
});

test('generateInvoiceText berisi nama pemesan, item, & total', function () {
    $teks = $this->service->generateInvoiceText($this->order);

    expect($teks)
        ->toContain('Siti Nurhaliza')
        ->toContain('Beras 5kg')
        ->toContain('2 x Rp70.000')
        ->toContain('Rp140.000');
});

test('generateWhatsAppLink pakai domain wa.me & normalisasi nomor lokal ke format internasional', function () {
    $link = $this->service->generateWhatsAppLink($this->order);

    // Nomor lokal "08123456789" harus jadi "628123456789" (awalan 0 diganti 62)
    expect($link)
        ->toStartWith('https://wa.me/628123456789?text=')
        ->not->toContain('08123456789');
});

test('generateWhatsAppLink meng-encode teks invoice biar aman jadi query string URL', function () {
    $link = $this->service->generateWhatsAppLink($this->order);

    // Spasi & baris baru di teks invoice harus sudah ke-encode (bukan mentah)
    expect($link)->not->toContain(' Siti Nurhaliza ');
    expect(urldecode(explode('?text=', $link)[1]))->toContain('Siti Nurhaliza');
});
