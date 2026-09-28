<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\RecapService;

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

    $this->anggotaSering = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Sering Belanja',
        'whatsapp_number' => '628111111111',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);
    $this->anggotaJarang = Member::create([
        'member_code' => '0002 A',
        'full_name' => 'Jarang Belanja',
        'whatsapp_number' => '628222222222',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    // Anggota "Sering Belanja" pesan 5 karung beras, statusnya verified.
    $orderSering = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->anggotaSering->id,
        'whatsapp_number' => $this->anggotaSering->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 350000, // 5 x 70.000
    ]);
    $orderSering->orderItems()->create([
        'product_id' => $this->beras->id,
        'quantity' => 5,
        'price_at_order' => 70000,
    ]);

    // Anggota "Jarang Belanja" pesan 1 karung beras, statusnya verified juga.
    $orderJarang = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->anggotaJarang->id,
        'whatsapp_number' => $this->anggotaJarang->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 70000, // 1 x 70.000
    ]);
    $orderJarang->orderItems()->create([
        'product_id' => $this->beras->id,
        'quantity' => 1,
        'price_at_order' => 70000,
    ]);

    // Pesanan "pending" milik anggota sering — SENGAJA tidak boleh ikut terhitung
    // karena belum final (belum diverifikasi admin).
    Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->anggotaSering->id,
        'whatsapp_number' => $this->anggotaSering->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);

    $this->recap = app(RecapService::class);
});

test('revenueByPeriod menghitung pendapatan kotor, modal, & bersih dengan benar', function () {
    $result = $this->recap->revenueByPeriod();

    expect($result['labels'])->toBe(['Pemesanan Agustus 2026']);
    // Total 6 karung terjual x Rp70.000 = Rp420.000
    expect($result['gross'])->toBe([420000.0]);
    // Modal: 6 x Rp65.000 = Rp390.000
    expect($result['modal'])->toBe([390000.0]);
    // Bersih: 420.000 - 390.000 = 30.000
    expect($result['net'])->toBe([30000.0]);
});

test('topMembers mengurutkan anggota dari yang paling sering pesan', function () {
    $result = $this->recap->topMembers();

    // Sengaja cuma dihitung 1x pesanan verified per anggota (yang pending diabaikan)
    expect($result['labels'][0])->toBe('Sering Belanja');
    expect($result['orderCounts'][0])->toBe(1);
    expect($result['totalValues'][0])->toBe(350000.0);
});

test('topProducts menjumlahkan total quantity terjual per produk', function () {
    $result = $this->recap->topProducts();

    expect($result->first()['nama'])->toBe('Beras 5kg');
    // 5 (sering) + 1 (jarang) = 6, pesanan pending tidak ikut dihitung
    expect($result->first()['jumlahTerjual'])->toBe(6);
});

test('productsForPeriod menghitung semua produk (bukan cuma top N) khusus 1 periode', function () {
    // Bikin periode lain + pesanan di periode itu, buat mastiin productsForPeriod
    // beneran cuma hitung periode yang diminta, bukan gabungan semua periode.
    $periodeLain = OrderPeriod::create([
        'label' => 'Periode Lain',
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addWeek(),
        'status' => OrderPeriodStatus::Closed,
    ]);
    $orderPeriodeLain = Order::create([
        'order_period_id' => $periodeLain->id,
        'user_type' => UserType::Member,
        'member_id' => $this->anggotaSering->id,
        'whatsapp_number' => $this->anggotaSering->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 700000,
    ]);
    $orderPeriodeLain->orderItems()->create([
        'product_id' => $this->beras->id,
        'quantity' => 10,
        'price_at_order' => 70000,
    ]);

    $result = $this->recap->productsForPeriod($this->periode);

    // Cuma 6 (dari periode "Pemesanan Agustus 2026"), BUKAN 16 (6 + 10 dari periode lain)
    expect($result->first()['jumlahDibutuhkan'])->toBe(6);
});

test('memberYearlySpending menjumlahkan belanja 1 anggota di tahun berjalan', function () {
    $total = $this->recap->memberYearlySpending($this->anggotaSering);

    // Cuma order verified milik anggotaSering yang dihitung (350.000),
    // punya anggotaJarang (70.000) & order pending tidak ikut.
    expect($total)->toBe(350000.0);
});

test('memberYearlySpending 0 buat anggota yang belum pernah belanja', function () {
    $anggotaBaru = Member::create([
        'member_code' => '0003 A',
        'full_name' => 'Belum Pernah Belanja',
        'whatsapp_number' => '628333333333',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    expect($this->recap->memberYearlySpending($anggotaBaru))->toBe(0.0);
});

test('revenueByPeriod & topProducts ikut menghitung pesanan NON-ANGGOTA (uang & belanja grosir buat semua orang)', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $orderNonAnggota = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Teh Teti',
        'opd_id' => $opd->id,
        'whatsapp_number' => '628199988877',
        'status' => OrderStatus::Verified,
        'total_amount' => 140000, // 2 x 70.000
    ]);
    $orderNonAnggota->orderItems()->create([
        'product_id' => $this->beras->id,
        'quantity' => 2,
        'price_at_order' => 70000,
    ]);

    $revenue = $this->recap->revenueByPeriod();
    // Sebelumnya cuma anggota (6 karung = 420.000), sekarang +2 dari non-anggota = 8 karung = 560.000
    expect($revenue['gross'])->toBe([560000.0]);

    $products = $this->recap->topProducts();
    // 6 (anggota) + 2 (non-anggota) = 8
    expect($products->first()['jumlahTerjual'])->toBe(8);
});

test('topMembers TIDAK ikut menghitung pesanan non-anggota (khusus anggota)', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $orderNonAnggota = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Teh Teti',
        'opd_id' => $opd->id,
        'whatsapp_number' => '628199988877',
        'status' => OrderStatus::Verified,
        'total_amount' => 9999999, // sengaja gede banget, buat mastiin nggak nyempil ke topMembers
    ]);
    $orderNonAnggota->orderItems()->create([
        'product_id' => $this->beras->id,
        'quantity' => 1,
        'price_at_order' => 70000,
    ]);

    $result = $this->recap->topMembers();

    // Cuma 2 nama (anggotaSering & anggotaJarang); pemesan non-anggota tidak ikut.
    expect($result['labels'])->toHaveCount(2);
    expect($result['labels'])->not->toContain('Teh Teti');
});

test('opdDistribution merekap pesanan per OPD, diurutkan dari yang paling sering pesan', function () {
    $opdSering = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $opdJarang = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'opd99999']);

    // OPD Pendidikan: 2 pesanan verified
    foreach (range(1, 2) as $i) {
        Order::create([
            'order_period_id' => $this->periode->id,
            'user_type' => UserType::NonMember,
            'non_member_name' => "Staf $i",
            'opd_id' => $opdSering->id,
            'whatsapp_number' => '62819998887'.$i,
            'status' => OrderStatus::Verified,
            'total_amount' => 70000,
        ]);
    }

    // OPD Kesehatan: 1 pesanan verified
    Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Staf Kesehatan',
        'opd_id' => $opdJarang->id,
        'whatsapp_number' => '628177766655',
        'status' => OrderStatus::Verified,
        'total_amount' => 70000,
    ]);

    $result = $this->recap->opdDistribution();

    expect($result['labels'][0])->toBe('Dinas Pendidikan');
    expect($result['orderCounts'][0])->toBe(2);
    expect($result['labels'][1])->toBe('Dinas Kesehatan');
    expect($result['orderCounts'][1])->toBe(1);
});
