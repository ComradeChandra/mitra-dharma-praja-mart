<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

/**
 * Test "asap" buat modul admin Tahap 2: mastiin semua halaman index/create/edit
 * render tanpa error (200 OK) buat admin yang sudah login, dan ditolak (redirect
 * ke login) buat yang belum login. Ini bukan test detail tiap validasi form,
 * cuma jaring pengaman dasar biar ketauan cepat kalau ada Blade/route yang rusak.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('halaman index semua modul admin bisa diakses admin yang login', function (string $routeName) {
    $this->actingAs($this->admin)
        ->get(route($routeName))
        ->assertOk();
})->with([
    'admin.dashboard',
    'admin.opd-departments.index',
    'admin.members.index',
    'admin.order-periods.index',
    'admin.products.index',
]);

test('halaman create semua modul admin bisa diakses admin yang login', function (string $routeName) {
    $this->actingAs($this->admin)
        ->get(route($routeName))
        ->assertOk();
})->with([
    'admin.opd-departments.create',
    'admin.members.create',
    'admin.order-periods.create',
    'admin.products.create',
]);

test('halaman edit OPD bisa diakses dan menampilkan data lama', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Contoh']);

    $this->actingAs($this->admin)
        ->get(route('admin.opd-departments.edit', $opd))
        ->assertOk()
        ->assertSee('Dinas Contoh');
});

test('halaman edit anggota bisa diakses dan menampilkan data lama', function () {
    $member = Member::create([
        'member_code' => '0099 A',
        'full_name' => 'Anggota Contoh',
        'whatsapp_number' => '628999999999',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.members.edit', $member))
        ->assertOk()
        ->assertSee('Anggota Contoh');
});

test('halaman edit periode bisa diakses dan menampilkan data lama', function () {
    $period = OrderPeriod::create([
        'label' => 'Periode Uji Coba',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-15',
        'status' => 'open',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.order-periods.edit', $period))
        ->assertOk()
        ->assertSee('Periode Uji Coba');
});

test('halaman edit produk bisa diakses dan menampilkan data lama', function () {
    $product = Product::create([
        'category' => 'Sembako',
        'name' => 'Produk Contoh',
        'buy_price' => 10000,
        'sell_price' => 12000,
        'is_fluctuating' => false,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('Produk Contoh');
});

test('tamu yang belum login ditolak dan diarahkan ke halaman login', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'admin.dashboard',
    'admin.opd-departments.index',
    'admin.members.index',
    'admin.order-periods.index',
    'admin.products.index',
]);

test('admin bisa tambah, edit, lalu hapus data OPD lewat form', function () {
    // Tambah — access_code sekarang wajib diisi (dipakai non-anggota buat login)
    $this->actingAs($this->admin)
        ->post(route('admin.opd-departments.store'), ['name' => 'OPD Baru', 'access_code' => 'opd12345'])
        ->assertRedirect(route('admin.opd-departments.index'));

    $opd = OpdDepartment::where('name', 'OPD Baru')->firstOrFail();

    // Edit
    $this->actingAs($this->admin)
        ->put(route('admin.opd-departments.update', $opd), ['name' => 'OPD Sudah Diedit'])
        ->assertRedirect(route('admin.opd-departments.index'));

    expect($opd->fresh()->name)->toBe('OPD Sudah Diedit');

    // Hapus
    $this->actingAs($this->admin)
        ->delete(route('admin.opd-departments.destroy', $opd))
        ->assertRedirect(route('admin.opd-departments.index'));

    expect(OpdDepartment::find($opd->id))->toBeNull();
});

test('periode yang masih punya pesanan tidak bisa dihapus dan menampilkan pesan error', function () {
    $period = OrderPeriod::create([
        'label' => 'Periode Dipakai',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-15',
        'status' => 'open',
    ]);

    // Bikin 1 pesanan non-anggota yang menempel ke periode ini
    Order::create([
        'order_period_id' => $period->id,
        'user_type' => 'non_member',
        'non_member_name' => 'Penguji',
        'whatsapp_number' => '628111111111',
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.order-periods.destroy', $period))
        ->assertRedirect(route('admin.order-periods.index'))
        ->assertSessionHas('error');

    // Periode-nya masih ada, tidak jadi terhapus
    expect(OrderPeriod::find($period->id))->not->toBeNull();
});
