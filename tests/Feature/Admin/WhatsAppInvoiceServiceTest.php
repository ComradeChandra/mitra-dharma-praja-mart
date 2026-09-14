<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\WhatsAppInvoiceService;
use Illuminate\Support\Facades\URL;

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
    expect(rawurldecode(explode('?text=', $link)[1]))->toContain('Siti Nurhaliza');

    // Spasi jadi %20, bukan "+": tidak semua aplikasi WhatsApp mengembalikan
    // "+" jadi spasi, jadi invoice bisa terbaca "Total:+Rp140.000".
    expect($link)->toContain('Siti%20Nurhaliza')->not->toContain('Siti+Nurhaliza');
});

test('teks invoice tidak mengubah apostrof dan & jadi kode HTML', function () {
    // Invoice ini teks polos buat WhatsApp. Kalau dicetak pakai {{ }}, nama
    // "Nur'aini" sampai di WhatsApp sebagai "Nur&#039;aini". Nama berapostrof
    // umum sekali, jadi ini pasti kena begitu data anggota asli masuk.
    $this->member->update(['full_name' => "Nur'aini"]);
    $this->order->update(['delivery_address' => 'Jl. Kenanga & Melati No. 5']);

    $teks = $this->service->generateInvoiceText($this->order->fresh());

    expect($teks)
        ->toContain("Nama: Nur'aini")
        ->toContain('Alamat: Jl. Kenanga & Melati No. 5')
        ->not->toContain('&#039;')
        ->not->toContain('&amp;');
});

test('invoice anggota yang belum bayar menyertakan tautan ke halaman bayar', function () {
    // Invoice memberi tahu berapa yang harus dibayar, jadi di situ juga harus
    // ada cara membayarnya. QRIS dan tombol "Saya sudah bayar" ada di halaman
    // pesanan, jadi tautannya yang dikirim.
    $teks = $this->service->generateInvoiceText($this->order);

    expect($teks)
        ->toContain('Cara bayar')
        ->toContain(route('member.orders.show', $this->order));
});

test('invoice non-anggota menyertakan tautan bertanda tangan yang benar-benar bisa dibuka', function () {
    // Non-anggota tidak punya akun maupun riwayat pesanan, jadi tautan di
    // invoice inilah jalan kembalinya ke halaman bayar setelah sesinya habis.
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $pesanan = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Teh Teti',
        'opd_id' => $opd->id,
        'whatsapp_number' => '628199988877',
        'status' => OrderStatus::Verified,
        'total_amount' => 70000,
    ]);
    $pesanan->orderItems()->create(['product_id' => $this->beras->id, 'quantity' => 1, 'price_at_order' => 70000]);

    $teks = $this->service->generateInvoiceText($pesanan);
    $tautan = url(URL::signedRoute('non-member.orders.show', $pesanan, absolute: false));

    expect($teks)->toContain($tautan);

    // Dibuka dari sesi yang belum pernah mencatat pesanan ini: tetap masuk.
    $this->withSession(['non_member_opd_id' => $opd->id])->get($tautan)->assertOk();
});

test('invoice pesanan yang sudah lunas tidak lagi menyuruh membayar', function () {
    $this->order->update(['payment_status' => PaymentStatus::Paid]);

    $teks = $this->service->generateInvoiceText($this->order->fresh());

    expect($teks)
        ->toContain('Lunas')
        ->not->toContain('Cara bayar');
});
