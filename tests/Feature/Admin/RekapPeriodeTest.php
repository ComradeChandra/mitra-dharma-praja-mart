<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\RecapService;

/**
 * Rekap 1 periode — ketiga jenis yang diminta Modul 6 CLAUDE.md:
 * belanja grosir per produk (+ siapa pemesannya), distribusi per OPD
 * (+ nama pemesannya), dan status "sudah/belum belanja" per anggota.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();

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

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    // Anggota yang SUDAH pesan.
    $this->sudahPesan = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Sudah Pesan',
        'whatsapp_number' => '628111111111',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    // Anggota yang BELUM pesan.
    $this->belumPesan = Member::create([
        'member_code' => '0002 A',
        'full_name' => 'Belum Pesan',
        'whatsapp_number' => '628222222222',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $orderAnggota = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->sudahPesan->id,
        'whatsapp_number' => $this->sudahPesan->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 140000,
    ]);
    $orderAnggota->orderItems()->create([
        'product_id' => $this->beras->id, 'quantity' => 2, 'price_at_order' => 70000,
    ]);

    $orderNonAnggota = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Teh Teti',
        'opd_id' => $this->opd->id,
        'whatsapp_number' => '628199988877',
        'status' => OrderStatus::Verified,
        'total_amount' => 210000,
    ]);
    $orderNonAnggota->orderItems()->create([
        'product_id' => $this->beras->id, 'quantity' => 3, 'price_at_order' => 70000,
    ]);

    $this->recap = app(RecapService::class);
});

test('rekap produk membawa daftar pemesannya (jawaban "yang beli beras siapa aja")', function () {
    $produk = $this->recap->productsForPeriod($this->periode)->first();

    expect($produk['nama'])->toBe('Beras 5kg');
    expect($produk['jumlahDibutuhkan'])->toBe(5); // 2 anggota + 3 non-anggota

    $namaPemesan = $produk['pemesan']->pluck('nama')->all();
    expect($namaPemesan)->toContain('Sudah Pesan');
    expect($namaPemesan)->toContain('Teh Teti');

    // Jumlah rincian harus cocok sama total di baris induknya.
    expect($produk['pemesan']->sum('jumlah'))->toBe(5);
});

test('rekap produk menandai asal pemesan (anggota vs OPD-nya)', function () {
    $pemesan = $this->recap->productsForPeriod($this->periode)->first()['pemesan'];

    expect($pemesan->firstWhere('nama', 'Sudah Pesan')['asal'])->toBe('Anggota');
    expect($pemesan->firstWhere('nama', 'Teh Teti')['asal'])->toBe('Dinas Pendidikan');
});

test('rekap per-OPD memuat nama pemesannya, bukan cuma angka', function () {
    $opd = $this->recap->opdRecapForPeriod($this->periode)->first();

    expect($opd['nama'])->toBe('Dinas Pendidikan');
    expect($opd['jumlahPesanan'])->toBe(1);
    expect($opd['totalBelanja'])->toBe(210000.0);
    expect($opd['pemesan']->pluck('nama')->all())->toBe(['Teh Teti']);
});

test('rekap per-OPD TIDAK memasukkan pesanan anggota', function () {
    $semuaNama = $this->recap->opdRecapForPeriod($this->periode)
        ->flatMap(fn ($opd) => $opd['pemesan']->pluck('nama'))
        ->all();

    expect($semuaNama)->not->toContain('Sudah Pesan');
});

test('status belanja memisahkan anggota yang sudah & belum pesan', function () {
    $status = $this->recap->memberOrderStatusForPeriod($this->periode);

    expect($status->firstWhere('nama', 'Sudah Pesan')['sudahPesan'])->toBeTrue();
    expect($status->firstWhere('nama', 'Belum Pesan')['sudahPesan'])->toBeFalse();
});

test('pesanan PENDING tetap dihitung sudah belanja (yang ditanya sudah kirim atau belum)', function () {
    // Anggota "Belum Pesan" kirim pesanan yang harganya belum dikunci admin.
    Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->belumPesan->id,
        'whatsapp_number' => $this->belumPesan->whatsapp_number,
        'status' => OrderStatus::Pending,
    ]);

    $status = $this->recap->memberOrderStatusForPeriod($this->periode);

    expect($status->firstWhere('nama', 'Belum Pesan')['sudahPesan'])->toBeTrue();
});

test('anggota NONAKTIF tidak ikut didaftar sebagai "belum belanja"', function () {
    Member::create([
        'member_code' => '0003 A',
        'full_name' => 'Anggota Nonaktif',
        'whatsapp_number' => '628333333333',
        'password' => 'rahasia123',
        'is_active' => false,
    ]);

    $nama = $this->recap->memberOrderStatusForPeriod($this->periode)->pluck('nama')->all();

    expect($nama)->not->toContain('Anggota Nonaktif');
});

test('progres belanja menghitung berapa anggota aktif yang sudah pesan', function () {
    $progres = $this->recap->memberOrderProgressForPeriod($this->periode);

    expect($progres)->toBe(['sudah' => 1, 'total' => 2]);
});

test('progres belanja aman dipanggil saat tidak ada periode dibuka', function () {
    expect($this->recap->memberOrderProgressForPeriod(null))->toBe(['sudah' => 0, 'total' => 2]);
});

test('halaman rekap admin menampilkan ketiga jenis rekap', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.order-periods.rekap', $this->periode));

    $response->assertOk();
    $response->assertSee('Belanja Grosir');
    $response->assertSee('Distribusi per OPD');
    $response->assertSee('Status Anggota');

    // Isi ketiganya benar-benar terender, bukan cuma judul tabnya.
    $response->assertSee('Beras 5kg');
    $response->assertSee('Dinas Pendidikan');
    $response->assertSee('Belum Pesan');
});
