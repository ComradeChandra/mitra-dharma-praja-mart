<?php

use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\WhatsAppInvoiceService;

/*
|--------------------------------------------------------------------------
| Hapus satu barang dari pesanan (16 Sep 2026)
|--------------------------------------------------------------------------
| Untuk kasus satu barang tidak bisa dipenuhi (habis di grosir) tapi barang
| lain tetap jalan. Cuma pengurus, selama pembayaran belum berjalan, dan
| bukan barang terakhir. Stok kembali, total dihitung ulang, pemesan melihat
| catatannya, dan invoice memuat catatan itu.
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

    $produk = fn (string $nama, ?int $harga, ?int $stok = null) => Product::create([
        'category' => 'Sembako', 'name' => $nama, 'buy_price' => 20000, 'sell_price' => $harga,
        'is_fluctuating' => $harga === null, 'has_stock_tracking' => $stok !== null, 'stock' => $stok, 'is_active' => true,
    ]);
    $this->beras = $produk('Beras 5kg', 70000, 10);
    $this->minyak = $produk('Minyak Goreng 2L', 35000);
    $this->telur = $produk('Telur Ayam 1kg', null);

    // Pesan lewat service supaya stok ikut berkurang seperti aslinya
    $this->pesan = fn (array $isi) => app(OrderService::class)->createOrder(
        $this->anggota,
        $this->periode,
        array_map(fn ($p) => ['product_id' => $p[0]->id, 'quantity' => $p[1]], $isi),
        DeliveryMethod::Ambil,
        null,
    );
    $this->itemDari = fn ($order, Product $p) => $order->orderItems()->where('product_id', $p->id)->first();
    $this->hapus = fn ($order, $item, array $tambahan = []) => $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.remove-item', $order), ['order_item_id' => $item->id, ...$tambahan]);
});

test('pengurus menghapus satu barang: stok kembali, total baru, catatan terlihat pemesan dan di invoice', function () {
    $order = ($this->pesan)([[$this->beras, 2], [$this->minyak, 1]]); // Rp175.000, langsung terverifikasi
    expect($this->beras->fresh()->stock)->toBe(8);

    ($this->hapus)($order, ($this->itemDari)($order, $this->beras), ['alasan' => '  habis di grosir  '])
        ->assertRedirect(route('admin.orders.show', $order))
        ->assertSessionHas('success');

    $order->refresh();
    expect($order->orderItems)->toHaveCount(1);
    expect($this->beras->fresh()->stock)->toBe(10);
    expect((float) $order->total_amount)->toBe(35000.0);
    expect($order->status)->toBe(OrderStatus::Verified);
    expect($order->catatan_pengurus)->toContain('Beras 5kg (2) dihapus dari pesanan oleh pengurus. Alasan: habis di grosir.');

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertSee('Catatan dari pengurus')
        ->assertSee('Beras 5kg (2) dihapus dari pesanan oleh pengurus.');

    $invoice = app(WhatsAppInvoiceService::class)->generateInvoiceText($order->load('orderItems.product', 'member', 'orderPeriod'));
    expect($invoice)->toContain('Catatan pengurus:')->toContain('Alasan: habis di grosir.');
    expect($invoice)->not->toContain('Beras 5kg —'); // barangnya sudah tidak ada di rincian
});

test('catatan menumpuk kalau barang dihapus lebih dari sekali', function () {
    $order = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1], [$this->telur, 2]]);

    ($this->hapus)($order, ($this->itemDari)($order, $this->beras));
    ($this->hapus)($order, ($this->itemDari)($order, $this->minyak), ['alasan' => 'stok kosong']);

    $baris = explode("\n", $order->fresh()->catatan_pengurus);
    expect($baris)->toHaveCount(2);
    expect($baris[0])->toContain('Beras 5kg (1) dihapus dari pesanan oleh pengurus.')->not->toContain('Alasan');
    expect($baris[1])->toContain('Minyak Goreng 2L (1) dihapus')->toContain('Alasan: stok kosong.');
});

test('pesanan yang invoicenya sudah terkirim kembali ke terverifikasi supaya invoice dikirim ulang', function () {
    $order = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);
    $order->update(['status' => OrderStatus::Invoiced]);

    ($this->hapus)($order, ($this->itemDari)($order, $this->minyak));

    expect($order->fresh()->status)->toBe(OrderStatus::Verified);
    expect((float) $order->fresh()->total_amount)->toBe(70000.0);
});

test('menghapus barang yang harganya masih kosong membuat pesanan langsung terverifikasi', function () {
    $order = ($this->pesan)([[$this->telur, 2], [$this->beras, 2]]);
    expect($order->status)->toBe(OrderStatus::Pending);

    ($this->hapus)($order, ($this->itemDari)($order, $this->telur), ['alasan' => 'telur habis']);

    expect($order->fresh()->status)->toBe(OrderStatus::Verified);
    expect((float) $order->fresh()->total_amount)->toBe(140000.0);
});

test('menghapus barang berharga tetap membiarkan pesanan menunggu harga barang lain', function () {
    $order = ($this->pesan)([[$this->telur, 2], [$this->beras, 2]]);

    ($this->hapus)($order, ($this->itemDari)($order, $this->beras));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->total_amount)->toBeNull();
    expect($this->beras->fresh()->stock)->toBe(10);
});

test('barang terakhir tidak bisa dihapus, dan kartunya tidak tampil', function () {
    $order = ($this->pesan)([[$this->beras, 2]]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertDontSee('Hapus barang dari pesanan')
        ->assertSee('Batalkan pesanan');

    ($this->hapus)($order, ($this->itemDari)($order, $this->beras))->assertStatus(422);

    expect($order->fresh()->orderItems)->toHaveCount(1);
    expect($this->beras->fresh()->stock)->toBe(8);
});

test('kartu hapus barang tampil kalau pesanan berisi lebih dari satu barang', function () {
    $order = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Hapus barang dari pesanan')
        ->assertSee('Beras 5kg — 1 pcs');
});

test('tidak bisa menghapus barang setelah pembayaran berjalan atau pesanan dibatalkan', function () {
    $dibayar = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);
    $dibayar->update(['payment_status' => PaymentStatus::AwaitingConfirmation]);
    ($this->hapus)($dibayar, ($this->itemDari)($dibayar, $this->minyak))->assertStatus(422);
    expect($dibayar->fresh()->orderItems)->toHaveCount(2);

    $batal = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.cancel', $batal));
    ($this->hapus)($batal, ($this->itemDari)($batal, $this->minyak))->assertStatus(422);
    expect($batal->fresh()->orderItems)->toHaveCount(2);
});

test('barang milik pesanan lain ditolak', function () {
    $pesananA = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);
    $pesananB = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);

    // Rute pesanan A, tapi barang pesanan B
    ($this->hapus)($pesananA, ($this->itemDari)($pesananB, $this->beras))->assertSessionHasErrors('order_item_id');

    expect($pesananA->fresh()->orderItems)->toHaveCount(2);
    expect($pesananB->fresh()->orderItems)->toHaveCount(2);
});

test('anggota tidak bisa memakai rute hapus barang', function () {
    $order = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);

    $respons = $this->actingAs($this->anggota, 'member')
        ->patch(route('admin.orders.remove-item', $order), ['order_item_id' => ($this->itemDari)($order, $this->beras)->id]);

    expect($respons->getStatusCode())->not->toBe(200);
    expect($order->fresh()->orderItems)->toHaveCount(2);
});

test('isian yang ngawur ditolak dengan rapi', function () {
    $order = ($this->pesan)([[$this->beras, 1], [$this->minyak, 1]]);
    $kirim = fn (array $data) => $this->actingAs($this->admin, 'web')->patch(route('admin.orders.remove-item', $order), $data);

    $kirim([])->assertSessionHasErrors('order_item_id');
    $kirim(['order_item_id' => 'abc'])->assertSessionHasErrors('order_item_id');
    $kirim(['order_item_id' => [1, 2]])->assertSessionHasErrors('order_item_id');
    $kirim(['order_item_id' => 999999])->assertSessionHasErrors('order_item_id');
    $kirim(['order_item_id' => ($this->itemDari)($order, $this->beras)->id, 'alasan' => str_repeat('a', 256)])->assertSessionHasErrors('alasan');
    $kirim(['order_item_id' => ($this->itemDari)($order, $this->beras)->id, 'alasan' => ['bukan teks']])->assertSessionHasErrors('alasan');

    expect($order->fresh()->orderItems)->toHaveCount(2);
});
