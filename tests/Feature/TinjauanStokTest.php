<?php

use App\Enums\DeliveryMethod;
use App\Enums\KeputusanStok;
use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;

/*
|--------------------------------------------------------------------------
| Tinjauan stok untuk pesanan yang melebihi stok tercatat (17 Sep 2026)
|--------------------------------------------------------------------------
| Sistem pre-order TIDAK menolak pesanan yang melebihi stok. Tapi supaya
| kelebihannya tidak lewat begitu saja, pesanan yang melebihi stok tercatat
| ditandai "menunggu" dan muncul di dasbor pengurus. Pengurus lalu memutuskan:
| Setujui (koperasi belanja lebih, isi pesanan tetap) atau Tolak (jumlahnya
| disesuaikan ke stok tercatat, atau dihapus kalau stoknya 0). Pemesan dapat
| catatan halus TANPA angka stok.
*/

beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan September 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628121000001', 'password' => 'anggota123', 'is_active' => true,
    ]);

    // Produk berstok (dilacak, stok 3) dan produk pre-order murni (tanpa stok).
    $this->berstok = Product::create([
        'category' => 'Sembako', 'name' => 'Beras 5kg', 'buy_price' => 60000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => true, 'stock' => 3, 'is_active' => true,
    ]);
    $this->preorder = Product::create([
        'category' => 'Sembako', 'name' => 'Minyak 2L', 'buy_price' => 30000, 'sell_price' => 35000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    $this->pesan = fn (array $isi) => app(OrderService::class)->createOrder(
        $this->anggota, $this->periode,
        array_map(fn ($p) => ['product_id' => $p[0]->id, 'quantity' => $p[1]], $isi),
        DeliveryMethod::Ambil, null,
    );
    $this->setujui = fn ($order) => $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.setujui-stok', $order));
    $this->tolak = fn ($order, array $data = []) => $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.tolak-stok', $order), $data);
});

test('pesan melebihi stok: ditandai menunggu dan snapshot stok tersimpan', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]); // stok 3

    expect($order->keputusan_stok)->toBe(KeputusanStok::Menunggu);
    expect($order->menungguTinjauanStok())->toBeTrue();

    $item = $order->orderItems->first();
    expect($item->stok_saat_pesan)->toBe(3);
    expect($item->melebihiStok())->toBeTrue();
    expect($item->kelebihan())->toBe(2);

    // Tetap tidak ditolak: stok jadi minus seperti keputusan PO murni.
    expect($this->berstok->fresh()->stock)->toBe(-2);
});

test('pesan dalam batas stok: tidak perlu ditinjau', function () {
    $order = ($this->pesan)([[$this->berstok, 2]]); // stok 3

    expect($order->keputusan_stok)->toBeNull();
    expect($order->orderItems->first()->melebihiStok())->toBeFalse();
});

test('produk tanpa pelacakan stok tidak pernah dianggap melebihi stok', function () {
    $order = ($this->pesan)([[$this->preorder, 999]]);

    expect($order->keputusan_stok)->toBeNull();
    expect($order->orderItems->first()->stok_saat_pesan)->toBeNull();
    expect($order->orderItems->first()->melebihiStok())->toBeFalse();
});

test('pesanan non-anggota juga ditandai kalau melebihi stok', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $order = app(OrderService::class)->createNonMemberOrder(
        $opd, 'Budi Santoso', '628120000000', $this->periode,
        [['product_id' => $this->berstok->id, 'quantity' => 5]], DeliveryMethod::Ambil, null,
    );

    expect($order->keputusan_stok)->toBe(KeputusanStok::Menunggu);
});

test('setujui: koperasi belanja lebih, isi pesanan tidak berubah', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);

    ($this->setujui)($order)
        ->assertRedirect(route('admin.orders.show', $order))
        ->assertSessionHas('success');

    $order->refresh();
    expect($order->keputusan_stok)->toBe(KeputusanStok::Disetujui);
    expect($order->keputusan_stok_oleh)->toBe($this->admin->id);
    expect($order->keputusan_stok_pada)->not->toBeNull();
    // Isi pesanan & stok tidak berubah.
    expect($order->orderItems->first()->quantity)->toBe(5);
    expect($this->berstok->fresh()->stock)->toBe(-2);
});

test('tolak: jumlah disesuaikan ke stok tercatat, stok kembali, total dihitung ulang, catatan terlihat', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]); // 5 x 70.000 = 350.000, langsung terverifikasi
    expect((float) $order->total_amount)->toBe(350000.0);

    ($this->tolak)($order, ['alasan' => '  grosir terbatas  '])
        ->assertRedirect(route('admin.orders.show', $order))
        ->assertSessionHas('success');

    $order->refresh();
    $item = $order->orderItems->first();

    expect($order->keputusan_stok)->toBe(KeputusanStok::Ditolak);
    expect($order->keputusan_stok_oleh)->toBe($this->admin->id);
    expect($item->quantity)->toBe(3);                      // disesuaikan ke stok tercatat
    expect($item->melebihiStok())->toBeFalse();            // tidak lagi melebihi
    expect($this->berstok->fresh()->stock)->toBe(0);       // -2 + 2 dikembalikan
    expect((float) $order->total_amount)->toBe(210000.0);  // 3 x 70.000
    expect($order->status)->toBe(OrderStatus::Verified);
    expect($order->catatan_pengurus)->toContain('Beras 5kg disesuaikan dari 5 ke 3 karena stok terbatas.');
    expect($order->catatan_pengurus)->toContain('Alasan: grosir terbatas.');
});

test('tolak barang yang stoknya 0: barang dihapus, barang lain tetap', function () {
    $this->berstok->update(['stock' => 0]);
    $order = ($this->pesan)([[$this->berstok, 4], [$this->preorder, 1]]);
    expect($order->keputusan_stok)->toBe(KeputusanStok::Menunggu);

    ($this->tolak)($order)->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->orderItems)->toHaveCount(1);
    expect($order->orderItems->first()->product_id)->toBe($this->preorder->id);
    expect($this->berstok->fresh()->stock)->toBe(0);       // -4 + 4 dikembalikan
    expect($order->keputusan_stok)->toBe(KeputusanStok::Ditolak);
    expect($order->catatan_pengurus)->toContain('Beras 5kg (4) dihapus karena stok tidak tersedia.');
});

test('tolak ditolak kalau akan mengosongkan pesanan (semua barang tak tersedia)', function () {
    $this->berstok->update(['stock' => 0]);
    $order = ($this->pesan)([[$this->berstok, 4]]); // satu-satunya barang, stok 0

    ($this->tolak)($order)->assertStatus(422);

    $order->refresh();
    expect($order->orderItems)->toHaveCount(1);
    expect($order->keputusan_stok)->toBe(KeputusanStok::Menunggu); // belum diputuskan
});

test('tolak diblokir kalau pembayaran sudah berjalan', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);
    $order->update(['payment_status' => PaymentStatus::AwaitingConfirmation]);

    ($this->tolak)($order)->assertStatus(422);
    expect($order->fresh()->orderItems->first()->quantity)->toBe(5);
});

test('setujui & tolak aman ditekan dua kali (idempoten)', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);

    ($this->setujui)($order);
    ($this->setujui)($order)->assertRedirect(); // tidak error walau sudah diputuskan
    expect($order->fresh()->keputusan_stok)->toBe(KeputusanStok::Disetujui);

    // Tolak setelah disetujui tidak berlaku lagi (bukan "menunggu").
    ($this->tolak)($order);
    expect($order->fresh()->orderItems->first()->quantity)->toBe(5);
});

test('scope perluTinjauanStok hanya menghitung pesanan yang menunggu', function () {
    $flag = ($this->pesan)([[$this->berstok, 5]]);
    ($this->pesan)([[$this->preorder, 2]]); // pre-order murni, tidak pernah ditandai

    expect(Order::perluTinjauanStok()->count())->toBe(1);

    // Pesanan batal tidak ikut dihitung di pengingat dasbor.
    $flag->update(['status' => OrderStatus::Cancelled]);
    expect(Order::perluTinjauanStok()->belumDibatalkan()->count())->toBe(0);
});

test('pengurus melihat panel tinjauan stok di halaman detail', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Perlu tinjauan stok')
        ->assertSee('Setujui — koperasi belanja lebih')
        ->assertSee('stok tercatat 3');
});

test('pemesan melihat catatan halus tanpa angka stok, dan hilang setelah disesuaikan', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertSee('Sebagian barang mungkin menyusul')
        ->assertDontSee('stok tercatat'); // angka stok tidak bocor ke pemesan

    ($this->tolak)($order);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertDontSee('Sebagian barang mungkin menyusul');
});

test('anggota tidak bisa memakai rute setujui/tolak stok', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);

    $r1 = $this->actingAs($this->anggota, 'member')->patch(route('admin.orders.setujui-stok', $order));
    $r2 = $this->actingAs($this->anggota, 'member')->patch(route('admin.orders.tolak-stok', $order));

    expect($r1->getStatusCode())->not->toBe(200);
    expect($r2->getStatusCode())->not->toBe(200);
    expect($order->fresh()->keputusan_stok)->toBe(KeputusanStok::Menunggu);
});

test('alasan tolak yang ngawur ditolak dengan rapi', function () {
    $order = ($this->pesan)([[$this->berstok, 5]]);

    ($this->tolak)($order, ['alasan' => str_repeat('a', 256)])->assertSessionHasErrors('alasan');
    ($this->tolak)($order, ['alasan' => ['bukan teks']])->assertSessionHasErrors('alasan');

    expect($order->fresh()->keputusan_stok)->toBe(KeputusanStok::Menunggu);
});
