<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

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

    $this->telur = Product::create([
        'category' => 'Sayur & Segar',
        'name' => 'Telur Ayam 1kg',
        'buy_price' => 28000,
        'sell_price' => null,
        'is_fluctuating' => true,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);
});

/**
 * Non-anggota bukan guard Laravel beneran — "login" cuma nyimpen
 * non_member_opd_id ke session (lihat EnsureNonMemberSession). Helper ini
 * biar tiap test nggak perlu POST ke /non-anggota/login berulang-ulang.
 */
function sesiNonAnggota(OpdDepartment $opd): array
{
    return ['non_member_opd_id' => $opd->id];
}

test('non-anggota bisa lihat form pesan saat periode dibuka', function () {
    $this->withSession(sesiNonAnggota($this->opd))
        ->get(route('non-member.orders.create'))
        ->assertOk()
        ->assertSee('Beras 5kg');
});

test('pesanan non-anggota produk non-fluktuatif langsung terverifikasi & totalnya benar', function () {
    $response = $this->withSession(sesiNonAnggota($this->opd))
        ->post(route('non-member.orders.store'), [
            'delivery_method' => 'ambil',
            'non_member_name' => 'Teh Teti',
            'whatsapp_number' => '628199988877',
            'quantity' => [$this->beras->id => 3],
        ]);

    $order = Order::first();
    // Tautannya bertanda tangan, bukan URL polos: itu satu-satunya cara
    // non-anggota membuka lagi pesanannya setelah sesi 2 jam habis.
    $response->assertRedirect(URL::signedRoute('non-member.orders.show', $order));

    expect($order->user_type)->toBe(UserType::NonMember);
    expect($order->member_id)->toBeNull();
    expect($order->non_member_name)->toBe('Teh Teti');
    expect($order->opd_id)->toBe($this->opd->id);
    expect($order->whatsapp_number)->toBe('628199988877');
    expect($order->status)->toBe(OrderStatus::Verified);
    expect((float) $order->total_amount)->toBe(210000.0); // 3 x 70.000
});

test('pesanan non-anggota produk fluktuatif tetap pending', function () {
    $this->withSession(sesiNonAnggota($this->opd))->post(route('non-member.orders.store'), [
        'delivery_method' => 'ambil',
        'non_member_name' => 'Teh Teti',
        'whatsapp_number' => '628199988877',
        'quantity' => [$this->telur->id => 2],
    ]);

    $order = Order::first();

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->orderItems->first()->price_at_order)->toBeNull();
});

test('nama & nomor WA wajib diisi buat non-anggota (tidak punya akun tersimpan)', function () {
    $this->withSession(sesiNonAnggota($this->opd))
        ->post(route('non-member.orders.store'), [
            'delivery_method' => 'ambil',
            'quantity' => [$this->beras->id => 1],
        ])
        ->assertSessionHasErrors(['non_member_name', 'whatsapp_number']);

    $this->assertDatabaseCount('orders', 0);
});

test('BEDA dari anggota: non-anggota BOLEH kirim lebih dari 1 pesanan di periode yang sama', function () {
    // Kode akses OPD dipakai bersama banyak staf, jadi "1 pesanan per periode"
    // tidak berlaku (beda dari anggota yang 1 akun = 1 pesanan per periode).
    $this->withSession(sesiNonAnggota($this->opd))->post(route('non-member.orders.store'), [
        'delivery_method' => 'ambil',
        'non_member_name' => 'Teh Teti',
        'whatsapp_number' => '628199988877',
        'quantity' => [$this->beras->id => 1],
    ]);

    $response = $this->withSession(sesiNonAnggota($this->opd))->post(route('non-member.orders.store'), [
        'delivery_method' => 'ambil',
        'non_member_name' => 'Kang Dadan',
        'whatsapp_number' => '628177766655',
        'quantity' => [$this->beras->id => 2],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('orders', 2);
});

test('non-anggota cuma bisa lihat pesanan OPD sendiri, bukan OPD lain', function () {
    $opdLain = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'opd99999']);

    $this->withSession(sesiNonAnggota($opdLain))->post(route('non-member.orders.store'), [
        'delivery_method' => 'ambil',
        'non_member_name' => 'Orang OPD Lain',
        'whatsapp_number' => '628111122223',
        'quantity' => [$this->beras->id => 1],
    ]);
    $pesananOpdLain = Order::first();

    $this->withSession(sesiNonAnggota($this->opd))
        ->get(route('non-member.orders.show', $pesananOpdLain))
        ->assertForbidden();
});

test('tamu yang belum login ditolak dari halaman pesan non-anggota', function () {
    $this->get(route('non-member.orders.create'))
        ->assertRedirect(route('non-member.login'));
});

/*
|--------------------------------------------------------------------------
| Kepemilikan pesanan non-anggota
|--------------------------------------------------------------------------
| Satu kode akses OPD dipakai bersama sekantor, jadi opd_id saja tidak cukup
| menentukan siapa pemilik sebuah pesanan. Dulu memang cuma itu yang dicek,
| dan akibatnya siapa pun yang tahu kode kantor bisa membaca pesanan rekannya
| — termasuk bukti transfer yang memuat nama dan nomor rekening — cuma dengan
| menaikkan angka di URL. Test di bawah menjaga supaya itu tidak balik lagi.
*/

/** Kirim satu pesanan lalu kembalikan pesanannya. */
function kirimPesananNonAnggota(OpdDepartment $opd, int $produkId, string $nama): Order
{
    test()->withSession(sesiNonAnggota($opd))
        ->post(route('non-member.orders.store'), [
            'delivery_method' => 'ambil',
            'non_member_name' => $nama,
            'whatsapp_number' => '628190000001',
            'quantity' => [$produkId => 1],
        ]);

    return Order::latest('id')->first();
}

test('pemesan bisa membuka lagi pesanannya sendiri', function () {
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');

    $this->get(route('non-member.orders.show', $pesanan))->assertOk();
});

test('rekan sekantor tidak bisa membuka pesanan orang lain', function () {
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');

    // Sesi baru, kode akses kantor yang sama persis: inilah rekan sekantor.
    // Boleh memesan atas nama OPD ini, tapi bukan membaca pesanan Teh Teti.
    $this->flushSession();

    $this->withSession(sesiNonAnggota($this->opd))
        ->get(route('non-member.orders.show', $pesanan))
        ->assertForbidden();
});

test('rekan sekantor tidak bisa membuka bukti transfer orang lain', function () {
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');
    $this->flushSession();

    $this->withSession(sesiNonAnggota($this->opd))
        ->get(route('non-member.orders.payment-proof', $pesanan))
        ->assertForbidden();
});

test('rekan sekantor tidak bisa mencentang bayar di pesanan orang lain', function () {
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');
    $this->flushSession();

    $this->withSession(sesiNonAnggota($this->opd))
        ->post(route('non-member.orders.declare-paid', $pesanan))
        ->assertForbidden();
});

test('tautan bertanda tangan mengembalikan akses setelah sesi habis', function () {
    // Ini alasan tautannya ditandatangani: pembayaran baru dilakukan setelah
    // pengurus mengirim invoice, bisa beberapa hari kemudian, sedangkan sesi
    // cuma bertahan 2 jam. Tanpa ini pemesan terkunci dari pesanannya sendiri.
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');
    $tautan = URL::signedRoute('non-member.orders.show', $pesanan);

    $this->flushSession();

    $this->withSession(sesiNonAnggota($this->opd))->get($tautan)->assertOk();
});

test('tautan tanpa tanda tangan yang sah tetap ditolak', function () {
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');
    $this->flushSession();

    $this->withSession(sesiNonAnggota($this->opd))
        ->get(route('non-member.orders.show', $pesanan).'?signature=ngasal')
        ->assertForbidden();
});

test('tautan bertanda tangan tidak bisa dipakai dari OPD lain', function () {
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');
    $tautan = URL::signedRoute('non-member.orders.show', $pesanan);

    $opdLain = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'opd54321']);

    $this->flushSession();

    $this->withSession(sesiNonAnggota($opdLain))->get($tautan)->assertForbidden();
});

test('login ulang membersihkan daftar pesanan sesi sebelumnya', function () {
    // Komputer bersama: orang berikutnya yang masuk dengan kode kantor tidak
    // boleh mewarisi pesanan orang sebelumnya.
    $pesanan = kirimPesananNonAnggota($this->opd, $this->beras->id, 'Teh Teti');

    $this->post(route('non-member.login.store'), [
        'opd_department_id' => $this->opd->id,
        'access_code' => 'opd12345',
    ]);

    $this->get(route('non-member.orders.show', $pesanan))->assertForbidden();
});
