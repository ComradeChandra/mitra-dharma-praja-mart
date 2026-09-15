<?php

use App\Enums\CancelledBy;
use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Harga fluktuatif: "pesanan ini saja" atau "semua pesanan" (15 Sep 2026)
|--------------------------------------------------------------------------
| Tanpa pilihan "semua", 30 pesanan telur berarti mengetik harga telur 30
| kali. Yang ikut diisi cuma pesanan di PERIODE YANG SAMA yang harga barang
| itu masih kosong; yang sudah diisi satu per satu tidak ditimpa (keputusan
| Chandra), begitu juga pesanan yang dibatalkan dan yang pembayarannya
| sudah berjalan.
*/

beforeEach(function () {
    $this->admin = User::factory()->create();

    $buatPeriode = fn (string $label, int $mulai) => OrderPeriod::create([
        'label' => $label,
        'start_date' => now()->addDays($mulai),
        'end_date' => now()->addDays($mulai + 7),
        'status' => OrderPeriodStatus::Open,
    ]);
    $this->periode = $buatPeriode('Pemesanan September 2026', -1);
    $this->periodeLain = $buatPeriode('Pemesanan Agustus 2026', -40);

    $buatProduk = fn (string $nama, ?int $harga) => Product::create([
        'category' => 'Sayur & Segar', 'name' => $nama, 'buy_price' => 20000, 'sell_price' => $harga,
        'is_fluctuating' => $harga === null, 'has_stock_tracking' => false, 'is_active' => true,
    ]);
    $this->telur = $buatProduk('Telur Ayam 1kg', null);
    $this->bayam = $buatProduk('Bayam Ikat', null);
    $this->beras = $buatProduk('Beras 5kg', 70000);

    // Pesanan dibuat langsung (bukan lewat form) supaya isinya bisa diatur persis.
    $nomor = 0;
    $this->pesanan = function (array $isi, array $atribut = []) use (&$nomor) {
        $nomor++;
        $member = Member::create([
            'member_code' => sprintf('%04d A', $nomor), 'full_name' => "Anggota {$nomor}",
            'whatsapp_number' => '6281200'.$nomor, 'password' => 'anggota123', 'is_active' => true,
        ]);
        $order = Order::create([
            'order_period_id' => $this->periode->id, 'user_type' => UserType::Member, 'member_id' => $member->id,
            'whatsapp_number' => $member->whatsapp_number, 'status' => OrderStatus::Pending, ...$atribut,
        ]);
        foreach ($isi as [$produk, $jumlah, $harga]) {
            $order->orderItems()->create(['product_id' => $produk->id, 'quantity' => $jumlah, 'price_at_order' => $harga]);
        }

        return $order;
    };

    // A: yang sedang dibuka pengurus
    $this->a = ($this->pesanan)([[$this->telur, 2, null]]);
    // B: telur kosong + beras -> jadi lengkap setelah telur diisi
    $this->b = ($this->pesanan)([[$this->telur, 1, null], [$this->beras, 1, 70000]]);
    // C: telur & bayam kosong -> telur terisi, tetap menunggu harga bayam
    $this->c = ($this->pesanan)([[$this->telur, 3, null], [$this->bayam, 1, null]]);
    // D: telur sudah diisi satu per satu -> TIDAK ditimpa
    $this->d = ($this->pesanan)([[$this->telur, 1, 25000], [$this->bayam, 1, null]]);
    // E: periode lain -> tidak ikut
    $this->e = ($this->pesanan)([[$this->telur, 1, null]], ['order_period_id' => $this->periodeLain->id]);
    // F: sudah dibatalkan -> tidak ikut
    $this->f = ($this->pesanan)([[$this->telur, 1, null]], ['status' => OrderStatus::Cancelled, 'cancelled_by' => CancelledBy::Pemesan, 'cancelled_at' => now()]);
    // G: pembayarannya (entah bagaimana) sudah berjalan -> tidak ikut
    $this->g = ($this->pesanan)([[$this->telur, 1, null]], ['payment_status' => PaymentStatus::AwaitingConfirmation]);

    $this->itemA = $this->a->orderItems()->first();
    $this->hargaTelur = fn (Order $order) => $order->orderItems()->where('product_id', $this->telur->id)->value('price_at_order');
});

test('terapkan ke semua mengisi pesanan lain di periode yang sama yang harganya masih kosong', function () {
    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.verify', $this->a), [
            'prices' => [$this->itemA->id => 30000],
            'terapkan' => 'semua',
        ])
        ->assertRedirect(route('admin.orders.show', $this->a))
        ->assertSessionHas('success', fn (string $pesan) => str_contains($pesan, 'diterapkan ke 2 pesanan lain')
            && str_contains($pesan, '1 di antaranya masih menunggu harga barang lain'));

    // A sendiri
    expect($this->a->fresh()->status)->toBe(OrderStatus::Verified);
    expect((float) $this->a->fresh()->total_amount)->toBe(60000.0);

    // B jadi lengkap: 1 x 30.000 + 70.000
    expect($this->b->fresh()->status)->toBe(OrderStatus::Verified);
    expect((float) $this->b->fresh()->total_amount)->toBe(100000.0);

    // C: telur terisi, bayam masih kosong, jadi tetap menunggu
    expect((float) ($this->hargaTelur)($this->c))->toBe(30000.0);
    expect($this->c->fresh()->status)->toBe(OrderStatus::Pending);
    expect($this->c->fresh()->total_amount)->toBeNull();

    // Yang tidak boleh berubah
    expect((float) ($this->hargaTelur)($this->d))->toBe(25000.0);
    expect(($this->hargaTelur)($this->e))->toBeNull();
    expect(($this->hargaTelur)($this->f))->toBeNull();
    expect($this->f->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect(($this->hargaTelur)($this->g))->toBeNull();
});

test('pesanan ini saja tidak mengubah pesanan lain', function () {
    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.verify', $this->a), [
            'prices' => [$this->itemA->id => 30000],
            'terapkan' => 'pesanan-ini',
        ])
        ->assertSessionHas('success', 'Pesanan berhasil diverifikasi, harga & total sudah dikunci.');

    expect($this->a->fresh()->status)->toBe(OrderStatus::Verified);
    expect(($this->hargaTelur)($this->b))->toBeNull();
    expect(($this->hargaTelur)($this->c))->toBeNull();
});

test('tanpa pilihan, harganya cuma berlaku untuk pesanan ini', function () {
    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.verify', $this->a), ['prices' => [$this->itemA->id => 30000]])
        ->assertRedirect();

    expect(($this->hargaTelur)($this->b))->toBeNull();
});

test('halaman detail menyebut berapa pesanan lain yang ikut terisi', function () {
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $this->a))
        ->assertOk()
        ->assertSee('Terapkan harga ini ke')
        ->assertSee('Telur Ayam 1kg: 2 pesanan lain')
        ->assertSee('Pesanan yang harganya sudah diisi tidak ditimpa.');
});

test('pilihannya tidak muncul kalau tidak ada pesanan lain yang menunggu', function () {
    // E sendirian di periodenya
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $this->e))
        ->assertOk()
        // false: teks tombolnya ditulis langsung di template, tidak di-escape jadi &amp;
        ->assertSee('Verifikasi & Kunci Harga', false)
        ->assertDontSee('Terapkan harga ini ke');
});

test('nilai pilihan yang ngawur ditolak dan tidak mengubah apa pun', function () {
    foreach (['hapus-semua', ['semua']] as $ngawur) {
        $this->actingAs($this->admin, 'web')
            ->patch(route('admin.orders.verify', $this->a), [
                'prices' => [$this->itemA->id => 30000],
                'terapkan' => $ngawur,
            ])
            ->assertSessionHasErrors('terapkan');
    }

    expect($this->a->fresh()->status)->toBe(OrderStatus::Pending);
    expect(($this->hargaTelur)($this->b))->toBeNull();
});
