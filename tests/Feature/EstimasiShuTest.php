<?php

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\RecapService;

/**
 * Perkiraan SHU di beranda anggota.
 *
 * Persentasenya BELUM ditetapkan koperasi — di rapat disebut kisaran 0,5%-1%
 * dengan catatan "nanti kita ngobrol". Karena itu angkanya tidak boleh
 * ditulis di dalam kode atau tampilan, melainkan dibaca dari
 * config/koperasi.php.
 *
 * Dulu sempat salah: angkanya ditulis langsung di berkas Blade dan tersebar
 * di empat tempat sekaligus (dua perhitungan, satu komentar, satu label).
 */
beforeEach(function () {
    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111', 'password' => 'anggota123', 'is_active' => true,
    ]);

    $periode = OrderPeriod::create([
        'label' => 'Periode Uji', 'start_date' => now()->subDay(),
        'end_date' => now()->addDay(), 'status' => 'open',
    ]);

    $produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras', 'buy_price' => 65000,
        'sell_price' => 100000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);

    // Belanja Rp1.000.000 supaya angka persentasenya bulat & gampang dicek.
    $pesanan = Order::create([
        'order_period_id' => $periode->id, 'user_type' => UserType::Member,
        'member_id' => $this->anggota->id, 'whatsapp_number' => '628111111111',
        'status' => OrderStatus::Verified, 'total_amount' => 1000000,
    ]);
    $pesanan->orderItems()->create([
        'product_id' => $produk->id, 'quantity' => 10, 'price_at_order' => 100000,
    ]);
});

test('persentase dibaca dari config, bukan ditulis di kode', function () {
    config(['koperasi.shu.persen_min' => 2.0, 'koperasi.shu.persen_maks' => 4.0]);

    $hasil = app(RecapService::class)->estimasiShu(1000000);

    expect($hasil['min'])->toBe(20000.0);
    expect($hasil['maks'])->toBe(40000.0);
    expect($hasil['rentang'])->toBeTrue();
});

test('kalau batas bawah dan atas sama, dianggap sudah pasti', function () {
    // Ini yang terjadi begitu koperasi menetapkan satu angka.
    config(['koperasi.shu.persen_min' => 1.0, 'koperasi.shu.persen_maks' => 1.0]);

    $hasil = app(RecapService::class)->estimasiShu(1000000);

    expect($hasil['rentang'])->toBeFalse();
    expect($hasil['maks'])->toBe(10000.0);
});

test('beranda menampilkan rentang selama persentasenya belum pasti', function () {
    config(['koperasi.shu.persen_min' => 0.5, 'koperasi.shu.persen_maks' => 1.0]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('Rp5.000')   // 0,5%
        ->assertSee('Rp10.000')  // 1%
        ->assertSee('persentase pasti ditentukan koperasi');
});

test('beranda menampilkan satu angka begitu persentasenya ditetapkan', function () {
    config(['koperasi.shu.persen_min' => 1.0, 'koperasi.shu.persen_maks' => 1.0]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('Rp10.000')
        ->assertDontSee('persentase pasti ditentukan koperasi');
});

test('anggota yang belum belanja tidak dikasih angka SHU', function () {
    $baru = Member::create([
        'member_code' => '0002 A', 'full_name' => 'Budi', 'whatsapp_number' => '628122222222',
        'password' => 'anggota123', 'is_active' => true,
    ]);

    $this->actingAs($baru, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('Belum ada belanja terverifikasi')
        ->assertDontSee('Estimasi SHU');
});
