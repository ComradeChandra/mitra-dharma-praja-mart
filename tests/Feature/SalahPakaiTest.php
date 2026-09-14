<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Salah pakai: urutan aksi yang "salah" tapi wajar terjadi
|--------------------------------------------------------------------------
| Dibuat 15 Sep 2026 setelah ketahuan pengujian sebelumnya bias: yang diuji
| cuma jalur yang dibayangkan pembuat kodenya. Di sini sengaja dikumpulkan
| hal-hal yang dilakukan orang sungguhan: menghapus data yang sudah dipakai,
| mengirim form yang sudah basi, menekan tombol dua kali, mengisi data
| janggal. Syarat minimalnya: tidak boleh ada yang berakhir di error server.
*/

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->admin = User::factory()->create();
    $this->periode = OrderPeriod::create(['label' => 'Pemesanan Agustus 2026', 'start_date' => now()->subDay(), 'end_date' => now()->addWeek(), 'status' => OrderPeriodStatus::Open]);
    $this->beras = Product::create(['category' => 'Sembako', 'name' => 'Beras 5kg', 'buy_price' => 65000, 'sell_price' => 70000, 'has_stock_tracking' => true, 'stock' => 10, 'is_active' => true]);
    $this->telur = Product::create(['category' => 'Sayur', 'name' => 'Telur 1kg', 'buy_price' => 28000, 'sell_price' => null, 'is_fluctuating' => true, 'is_active' => true]);
    $this->anggota = Member::create(['member_code' => '0001 A', 'full_name' => 'Siti', 'whatsapp_number' => '628123', 'password' => 'rahasia123', 'address' => 'Jl. Kebon Kopi', 'is_active' => true]);
    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $this->pesanan = Order::create(['order_period_id' => $this->periode->id, 'user_type' => UserType::Member, 'member_id' => $this->anggota->id, 'whatsapp_number' => '628123', 'status' => OrderStatus::Verified, 'total_amount' => 140000]);
    $this->pesanan->orderItems()->create(['product_id' => $this->beras->id, 'quantity' => 2, 'price_at_order' => 70000]);

    $this->pesananNon = Order::create(['order_period_id' => $this->periode->id, 'user_type' => UserType::NonMember, 'non_member_name' => 'Teti', 'opd_id' => $this->opd->id, 'whatsapp_number' => '62811', 'status' => OrderStatus::Verified, 'total_amount' => 70000]);
    $this->pesananNon->orderItems()->create(['product_id' => $this->beras->id, 'quantity' => 1, 'price_at_order' => 70000]);
});

/** Semua halaman admin yang menampilkan pesanan, dipakai setelah data induknya dihapus. */
function halamanPesananAdmin(): array
{
    $periode = OrderPeriod::first();

    return [
        route('admin.dashboard'),
        route('admin.orders.index'),
        route('admin.order-periods.rekap', $periode),
        ...Order::all()->flatMap(fn (Order $o) => [route('admin.orders.show', $o), route('admin.orders.struk', $o)])->all(),
    ];
}

// ---------------------------------------------------------------- hapus data yang sudah dipakai

test('menghapus produk yang sudah pernah dipesan tidak berakhir di error, dan fotonya tidak ikut hilang', function () {
    $this->beras->update(['image_path' => UploadedFile::fake()->image('beras.jpg')->store('products', 'public')]);

    $this->actingAs($this->admin)
        ->delete(route('admin.products.destroy', $this->beras))
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('error');

    expect(Product::find($this->beras->id))->not->toBeNull();
    Storage::disk('public')->assertExists($this->beras->fresh()->image_path);
});

test('menghapus anggota yang punya pesanan tidak merusak halaman pesanan admin', function () {
    $this->actingAs($this->admin)->delete(route('admin.members.destroy', $this->anggota));

    foreach (halamanPesananAdmin() as $url) {
        $this->get($url)->assertOk();
    }
});

test('menghapus OPD yang punya pesanan non-anggota tidak merusak halaman pesanan admin', function () {
    $this->actingAs($this->admin)->delete(route('admin.opd-departments.destroy', $this->opd));

    foreach (halamanPesananAdmin() as $url) {
        $this->get($url)->assertOk();
    }
});

test('menghapus periode yang sudah berisi pesanan ditolak dengan pesan, bukan error', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.order-periods.destroy', $this->periode))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(OrderPeriod::find($this->periode->id))->not->toBeNull();
});

// ---------------------------------------------------------------- form basi

test('produk dinonaktifkan saat form masih terbuka: kiriman ditolak dengan pesan', function () {
    $this->beras->update(['is_active' => false]);

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), ['delivery_method' => 'ambil', 'quantity' => [$this->beras->id => 1]])
        ->assertSessionHasErrors();

    expect(Order::count())->toBe(2);
});

test('produk dihapus saat form masih terbuka: kiriman ditolak dengan pesan', function () {
    $hapus = Product::create(['category' => 'Sembako', 'name' => 'Gula', 'buy_price' => 1, 'sell_price' => 2, 'is_active' => true]);
    $id = $hapus->id;
    $hapus->delete();

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), ['delivery_method' => 'ambil', 'quantity' => [$id => 1]])
        ->assertSessionHasErrors();

    expect(Order::count())->toBe(2);
});

test('periode ditutup saat form masih terbuka: kiriman ditolak dengan pesan', function () {
    $this->periode->update(['status' => OrderPeriodStatus::Closed]);

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), ['delivery_method' => 'ambil', 'quantity' => [$this->beras->id => 1]])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Order::count())->toBe(2);
});

test('anggota dinonaktifkan saat masih login tidak bisa lagi memesan', function () {
    $this->actingAs($this->anggota, 'member');
    $this->anggota->update(['is_active' => false]);

    $this->post(route('member.orders.store'), ['delivery_method' => 'ambil', 'quantity' => [$this->beras->id => 1]]);

    expect(Order::count())->toBe(2);
});

// ---------------------------------------------------------------- tombol ditekan dua kali

test('verifikasi pesanan dua kali tidak berakhir di error', function () {
    $pending = Order::create(['order_period_id' => $this->periode->id, 'user_type' => UserType::Member, 'member_id' => $this->anggota->id, 'whatsapp_number' => '628123', 'status' => OrderStatus::Pending]);
    $item = $pending->orderItems()->create(['product_id' => $this->telur->id, 'quantity' => 1, 'price_at_order' => null]);

    $this->actingAs($this->admin);
    $this->patch(route('admin.orders.verify', $pending), ['prices' => [$item->id => 30000]])->assertRedirect();
    $kedua = $this->patch(route('admin.orders.verify', $pending), ['prices' => [$item->id => 30000]]);

    expect($kedua->status())->toBeLessThan(500);
});

test('tandai invoice terkirim dua kali tidak berakhir di halaman error', function () {
    $this->actingAs($this->admin);
    $this->patch(route('admin.orders.mark-invoiced', $this->pesanan))->assertRedirect();

    $this->patch(route('admin.orders.mark-invoiced', $this->pesanan))->assertRedirect();
});

test('konfirmasi lunas dua kali tidak mengubah waktu konfirmasi pertama', function () {
    $this->pesanan->update(['payment_status' => PaymentStatus::AwaitingConfirmation, 'paid_declared_at' => now()]);
    $this->actingAs($this->admin);

    $this->patch(route('admin.orders.confirm-payment', $this->pesanan))->assertRedirect();
    $pertama = $this->pesanan->fresh()->payment_confirmed_at;
    $this->travel(2)->hours();
    $this->patch(route('admin.orders.confirm-payment', $this->pesanan))->assertRedirect();

    expect($this->pesanan->fresh()->payment_confirmed_at->equalTo($pertama))->toBeTrue();
});

test('usulan yang sudah disetujui tidak bisa diam-diam ditolak lewat kiriman kedua', function () {
    $usulan = ProductRequest::create(['user_type' => UserType::Member, 'member_id' => $this->anggota->id, 'requester_label' => 'Siti', 'product_name' => 'Teh Botol', 'status' => ProductRequestStatus::Pending]);
    $this->actingAs($this->admin);

    $this->patch(route('admin.product-requests.approve', $usulan))->assertRedirect();
    $kedua = $this->patch(route('admin.product-requests.reject', $usulan));

    expect($kedua->status())->toBeLessThan(500);
});

// ---------------------------------------------------------------- isian janggal

test('dua periode terbuka yang tanggalnya bertumpuk ditolak', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.order-periods.store'), ['label' => 'Periode Kembar', 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(3)->toDateString(), 'status' => 'open'])
        ->assertSessionHasErrors();

    expect(OrderPeriod::where('label', 'Periode Kembar')->exists())->toBeFalse();
});

test('tanggal selesai sebelum tanggal mulai ditolak', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.order-periods.store'), ['label' => 'Terbalik', 'start_date' => '2026-12-10', 'end_date' => '2026-12-01', 'status' => 'closed'])
        ->assertSessionHasErrors('end_date');
});

test('nomor WhatsApp dengan strip, spasi, atau +62 diterima dan dirapikan', function () {
    $this->actingAs($this->admin);

    foreach (['0812-3456-7890' => '081234567890', '+62 812 3456 7891' => '6281234567891', '0812 3456 7892' => '081234567892'] as $ketikan => $tersimpan) {
        $kode = 'T'.substr(md5($ketikan), 0, 5);
        $this->post(route('admin.members.store'), ['member_code' => $kode, 'full_name' => 'Uji', 'whatsapp_number' => $ketikan, 'password' => 'rahasia123'])
            ->assertSessionHasNoErrors();
        expect(Member::where('member_code', $kode)->value('whatsapp_number'))->toBe($tersimpan);
    }
});

test('kode anggota kembar ditolak', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.members.store'), ['member_code' => '0001 A', 'full_name' => 'Kembar', 'whatsapp_number' => '08123', 'password' => 'rahasia123'])
        ->assertSessionHasErrors('member_code');
});

test('harga verifikasi nol, minus, atau huruf ditolak', function () {
    $pending = Order::create(['order_period_id' => $this->periode->id, 'user_type' => UserType::Member, 'member_id' => $this->anggota->id, 'whatsapp_number' => '628123', 'status' => OrderStatus::Pending]);
    $item = $pending->orderItems()->create(['product_id' => $this->telur->id, 'quantity' => 1, 'price_at_order' => null]);
    $this->actingAs($this->admin);

    foreach (['0', '-5000', 'tigapuluh ribu'] as $harga) {
        $this->patch(route('admin.orders.verify', $pending), ['prices' => [$item->id => $harga]])->assertSessionHasErrors();
    }

    expect($pending->fresh()->status)->toBe(OrderStatus::Pending);
});

test('pesanan dengan jumlah minus, pecahan, atau huruf ditolak', function () {
    $this->actingAs($this->anggota, 'member');

    foreach (['-1', '1.5', 'dua', '99999999999'] as $jumlah) {
        $this->post(route('member.orders.store'), ['delivery_method' => 'ambil', 'quantity' => [$this->beras->id => $jumlah]])->assertSessionHasErrors();
    }

    expect(Order::count())->toBe(2);
    expect($this->beras->fresh()->stock)->toBe(10);
});
