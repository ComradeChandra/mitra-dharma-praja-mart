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

/**
 * Penjaga keamanan akses — memastikan orang TIDAK BISA melihat/mengubah data
 * yang bukan haknya cuma dengan mengganti angka di URL (mis. mengubah
 * /anggota/pesanan-saya/5 jadi /6), atau dengan menyisipkan field tambahan
 * ke form.
 *
 * Ini ditulis setelah pengujian manual di server asli berhasil ditangkis
 * semua — supaya kalau nanti ada yang mengubah controller dan lupa menaruh
 * pengecekan pemiliknya, test-nya langsung merah, bukan ketahuan pas sudah
 * dipakai orang koperasi.
 */
/**
 * Login sebagai anggota TANPA ikut mengubah guard default.
 *
 * PENTING — kenapa helper ini ada: `actingAs($member, 'member')` bawaan
 * Laravel diam-diam memanggil `shouldUse('member')`, yang mengganti guard
 * DEFAULT seluruh test jadi 'member'. Akibatnya route admin (yang pakai
 * `middleware('auth')` tanpa sebut guard, jadi ikut guard default) malah
 * ikut lolos — padahal di browser asli tidak begitu: guard default tetap
 * 'web', dan sesi anggota tidak memenuhinya (sudah diuji manual ke server,
 * hasilnya 302 ke /login).
 *
 * Jadi guard default-nya dikembalikan ke 'web' di sini, biar test-nya
 * mengukur perilaku ASLI, bukan efek samping alat testnya.
 */
function loginAnggota(Member $member): void
{
    test()->actingAs($member, 'member');
    app('auth')->shouldUse('web');
}

beforeEach(function () {
    $this->periode = OrderPeriod::create([
        'label' => 'Periode Uji',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras 5kg',
        'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    $this->anggotaA = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Anggota A',
        'whatsapp_number' => '628111111111', 'password' => 'rahasia123', 'is_active' => true,
    ]);
    $this->anggotaB = Member::create([
        'member_code' => '0002 A', 'full_name' => 'Anggota B',
        'whatsapp_number' => '628222222222', 'password' => 'rahasia123', 'is_active' => true,
    ]);

    $this->opdSatu = OpdDepartment::create(['name' => 'Dinas Satu', 'access_code' => 'opd12345']);
    $this->opdDua = OpdDepartment::create(['name' => 'Dinas Dua', 'access_code' => 'opd99999']);

    // Pesanan milik Anggota A — jadi sasaran percobaan intip.
    $this->pesananA = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->anggotaA->id,
        'whatsapp_number' => $this->anggotaA->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 70000,
    ]);

    // Pesanan milik OPD Satu — sasaran percobaan intip antar-OPD.
    $this->pesananOpdSatu = Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Staf OPD Satu',
        'opd_id' => $this->opdSatu->id,
        'whatsapp_number' => '628199988877',
        'status' => OrderStatus::Verified,
        'total_amount' => 70000,
    ]);
});

test('anggota TIDAK bisa lihat pesanan anggota lain lewat ganti angka di URL', function () {
    $this->actingAs($this->anggotaB, 'member')
        ->get(route('member.orders.show', $this->pesananA))
        ->assertForbidden();
});

test('anggota tetap bisa lihat pesanannya sendiri', function () {
    $this->actingAs($this->anggotaA, 'member')
        ->get(route('member.orders.show', $this->pesananA))
        ->assertOk();
});

test('non-anggota TIDAK bisa lihat pesanan OPD lain lewat ganti angka di URL', function () {
    $this->withSession(['non_member_opd_id' => $this->opdDua->id])
        ->get(route('non-member.orders.show', $this->pesananOpdSatu))
        ->assertForbidden();
});

test('anggota TIDAK bisa masuk area admin', function (string $routeName) {
    loginAnggota($this->anggotaA);

    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'admin.dashboard',
    'admin.members.index',
    'admin.products.index',
    'admin.orders.index',
]);

test('anggota TIDAK bisa menghapus data lewat kirim request admin langsung', function () {
    loginAnggota($this->anggotaA);

    $this->delete(route('admin.members.destroy', $this->anggotaB))
        ->assertRedirect(route('login'));

    // Yang penting: datanya benar-benar masih ada, bukan cuma responsnya ditolak.
    expect(Member::find($this->anggotaB->id))->not->toBeNull();
});

test('non-anggota TIDAK bisa masuk halaman khusus anggota (profil/SHU)', function (string $routeName) {
    // Non-anggota boleh memesan, tapi TIDAK punya akses ke profil belanja
    // tahunan & SHU; halaman-halaman itu khusus anggota.
    $this->withSession(['non_member_opd_id' => $this->opdSatu->id])
        ->get(route($routeName))
        ->assertRedirect(route('member.login'));
})->with([
    'member.dashboard',
    'member.orders.index',
    'member.product-requests.index',
]);

test('tamu yang belum login ditolak dari semua area terlindungi', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect();
})->with([
    'admin.dashboard',
    'member.dashboard',
    'non-member.orders.create',
]);

test('anggota tidak bisa menyisipkan field terlarang saat memesan', function () {
    // Coba paksa: pesanan atas nama orang lain, status langsung "invoiced",
    // total 0, dan harga item dikarang sendiri.
    $this->actingAs($this->anggotaB, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->produk->id => 1],
        'member_id' => $this->anggotaA->id,
        'status' => OrderStatus::Invoiced->value,
        'total_amount' => 0,
        'price_at_order' => 1,
    ]);

    $pesananBaru = Order::where('member_id', $this->anggotaB->id)->latest('id')->first();

    // Pemiliknya tetap si pengirim, bukan orang yang disisipkan.
    expect($pesananBaru)->not->toBeNull();
    expect($pesananBaru->member_id)->toBe($this->anggotaB->id);
    // Status & harga ditentukan sistem dari data produk, bukan dari kiriman form.
    expect($pesananBaru->status)->toBe(OrderStatus::Verified);
    expect((float) $pesananBaru->total_amount)->toBe(70000.0);
    expect((float) $pesananBaru->orderItems->first()->price_at_order)->toBe(70000.0);
});
