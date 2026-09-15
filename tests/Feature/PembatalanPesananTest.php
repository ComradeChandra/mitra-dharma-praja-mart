<?php

use App\Enums\CancelledBy;
use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\OrderService;
use App\Services\RecapService;

/*
|--------------------------------------------------------------------------
| Pembatalan pesanan (15 Sep 2026)
|--------------------------------------------------------------------------
| Aturan yang diputuskan Chandra:
| - pemesan boleh membatalkan sendiri selama periode masih dibuka dan
|   belum dibayar; selebihnya lewat pengurus
| - pengurus boleh kapan saja; kalau pembayaran sudah berjalan wajib
|   mencentang bahwa uangnya dikembalikan
| - pesanan batal tetap tercatat "Dibatalkan", stoknya kembali, dan tidak
|   dihitung di rekap, SHU, maupun status belanja
*/

beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan September 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $buatAnggota = fn (string $kode, string $nama) => Member::create([
        'member_code' => $kode,
        'full_name' => $nama,
        'whatsapp_number' => '62812000'.substr($kode, 0, 4),
        'password' => 'anggota123',
        'is_active' => true,
    ]);
    $this->anggota = $buatAnggota('0001 A', 'Siti Nurhaliza');
    $this->anggotaLain = $buatAnggota('0002 A', 'Budi Santoso');

    // Beras stoknya dilacak, supaya pengembalian stok bisa diperiksa
    $this->beras = Product::create([
        'category' => 'Sembako', 'name' => 'Beras 5kg', 'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => true, 'stock' => 10, 'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'rahasia123']);

    $this->pesanBeras = fn (Member $member, int $jumlah = 2) => app(OrderService::class)->createOrder(
        $member,
        $this->periode,
        [['product_id' => $this->beras->id, 'quantity' => $jumlah]],
        DeliveryMethod::Ambil,
        null,
    );
});

test('anggota bisa membatalkan pesanannya sendiri dan stoknya kembali', function () {
    $order = ($this->pesanBeras)($this->anggota, 2);
    expect($this->beras->fresh()->stock)->toBe(8);

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.cancel', $order))
        ->assertRedirect(route('member.orders.show', $order));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->cancelled_by)->toBe(CancelledBy::Pemesan);
    expect($order->cancelled_at)->not->toBeNull();
    expect($this->beras->fresh()->stock)->toBe(10);
});

test('membatalkan dua kali tidak mengembalikan stok dua kali', function () {
    $order = ($this->pesanBeras)($this->anggota, 2);

    $this->actingAs($this->anggota, 'member')->post(route('member.orders.cancel', $order))->assertRedirect();
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.cancel', $order))->assertRedirect();
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.cancel', $order))->assertRedirect();

    expect($this->beras->fresh()->stock)->toBe(10);
});

test('anggota tidak bisa membatalkan pesanan anggota lain', function () {
    $order = ($this->pesanBeras)($this->anggota);

    $this->actingAs($this->anggotaLain, 'member')
        ->post(route('member.orders.cancel', $order))
        ->assertForbidden();

    expect($order->fresh()->status)->not->toBe(OrderStatus::Cancelled);
    expect($this->beras->fresh()->stock)->toBe(8);
});

test('setelah periode ditutup anggota harus lewat pengurus', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $this->periode->update(['status' => OrderPeriodStatus::Closed]);

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.cancel', $order))
        ->assertStatus(422);

    expect($order->fresh()->status)->not->toBe(OrderStatus::Cancelled);

    // Halamannya menjelaskan kenapa, tanpa tombol batal
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertSee('hubungi pengurus koperasi')
        ->assertDontSee('Batalkan pesanan');
});

test('anggota tidak bisa membatalkan pesanan yang pembayarannya sudah berjalan', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $order->update(['payment_status' => PaymentStatus::AwaitingConfirmation]);

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.cancel', $order))
        ->assertStatus(422);

    expect($order->fresh()->status)->not->toBe(OrderStatus::Cancelled);
});

test('non-anggota bisa membatalkan pesanan dari sesinya, rekan sekantor tidak', function () {
    $order = app(OrderService::class)->createNonMemberOrder(
        $this->opd, 'Teti', '6281234', $this->periode,
        [['product_id' => $this->beras->id, 'quantity' => 1]], DeliveryMethod::Ambil, null,
    );

    // Rekan sekantor: kode akses OPD sama, tapi pesanannya bukan dari sesinya
    $this->withSession(['non_member_opd_id' => $this->opd->id, 'non_member_order_ids' => []])
        ->post(route('non-member.orders.cancel', $order))
        ->assertForbidden();
    expect($order->fresh()->status)->not->toBe(OrderStatus::Cancelled);

    $this->flushSession();
    $this->withSession(['non_member_opd_id' => $this->opd->id, 'non_member_order_ids' => [$order->id]])
        ->post(route('non-member.orders.cancel', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($this->beras->fresh()->stock)->toBe(10);
});

test('pengurus bisa membatalkan setelah periode ditutup, dan alasannya terlihat pemesan', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $this->periode->update(['status' => OrderPeriodStatus::Closed]);

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.cancel', $order), ['alasan' => '  Beras habis di grosir  '])
        ->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->cancelled_by)->toBe(CancelledBy::Pengurus);
    expect($order->cancellation_reason)->toBe('Beras habis di grosir');

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertSee('Pesanan ini dibatalkan')
        ->assertSee('Dibatalkan oleh pengurus')
        ->assertSee('Alasan: Beras habis di grosir');
});

test('pengurus wajib mencentang pengembalian uang kalau pembayaran sudah berjalan', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $order->update(['payment_status' => PaymentStatus::Paid, 'payment_confirmed_at' => now()]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $order))
        ->assertSeeInOrder(['Pesanan ini sudah lunas.', 'Saya memastikan uangnya dikembalikan ke pemesan.']);

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.cancel', $order))
        ->assertSessionHasErrors('uang_dikembalikan');
    expect($order->fresh()->status)->not->toBe(OrderStatus::Cancelled);

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.cancel', $order), ['uang_dikembalikan' => '1'])
        ->assertRedirect();
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

test('pesanan batal tidak dihitung di rekap, SHU, status belanja, dan pembayaran yang menunggu', function () {
    $order = ($this->pesanBeras)($this->anggota, 2); // langsung terverifikasi, Rp140.000
    $order->update(['payment_status' => PaymentStatus::AwaitingConfirmation]);
    $rekap = app(RecapService::class);

    expect($rekap->memberYearlySpending($this->anggota))->toBe(140000.0);
    expect($rekap->memberOrderProgressForPeriod($this->periode)['sudah'])->toBe(1);
    expect($rekap->productsForPeriod($this->periode))->toHaveCount(1);
    expect(app(DashboardStatsService::class)->summary()['pembayaranMenunggu'])->toBe(1);

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.cancel', $order), ['uang_dikembalikan' => '1']);

    expect($rekap->memberYearlySpending($this->anggota))->toBe(0.0);
    expect($rekap->memberOrderProgressForPeriod($this->periode)['sudah'])->toBe(0);
    expect($rekap->productsForPeriod($this->periode))->toHaveCount(0);
    expect($rekap->revenueByPeriod()['labels'])->toBe([]);
    expect($rekap->memberOrderStatusForPeriod($this->periode)->firstWhere('kode', '0001 A')['sudahPesan'])->toBeFalse();
    expect(app(DashboardStatsService::class)->summary()['pembayaranMenunggu'])->toBe(0);
});

test('pesanan batal tidak bisa dibayar, dikonfirmasi, diverifikasi, atau ditandai terkirim', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.cancel', $order));

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $order))
        ->assertStatus(422);

    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.confirm-payment', $order))->assertStatus(422);
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.verify', $order))->assertStatus(422);
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.mark-invoiced', $order))->assertStatus(422);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('halaman pesanan batal tidak menampilkan QRIS, tombol batal, maupun struk untuk dibagikan', function () {
    $order = ($this->pesanBeras)($this->anggota);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertSee('Batalkan pesanan')
        ->assertSee('Saya sudah bayar');

    $this->actingAs($this->anggota, 'member')->post(route('member.orders.cancel', $order));

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertSee('Pesanan ini dibatalkan')
        ->assertSee('Tidak ada yang perlu dibayar.')
        ->assertDontSee('Saya sudah bayar')
        ->assertDontSee('Batalkan pesanan')
        ->assertDontSee('Bagikan Struk lewat WhatsApp');

    // Pengurus: tanpa invoice, tanpa kartu batal, tetap bisa membuka struk bertanda batal
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Dibatalkan oleh pemesan')
        ->assertDontSee('Invoice WhatsApp')
        ->assertDontSee('Batalkan pesanan');

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.struk', $order))
        ->assertOk()
        ->assertSee('Pesanan ini dibatalkan');
});

test('pemberitahuan "sudah mengirim pesanan" tidak menyebut pesanan yang dibatalkan', function () {
    $order = ($this->pesanBeras)($this->anggota);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.create'))
        ->assertSee('Kamu sudah mengirim 1 pesanan di periode ini');

    $this->actingAs($this->anggota, 'member')->post(route('member.orders.cancel', $order));

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.create'))
        ->assertDontSee('Kamu sudah mengirim');
});

test('daftar pesanan pengurus punya penyaring Dibatalkan', function () {
    $batal = ($this->pesanBeras)($this->anggota);
    ($this->pesanBeras)($this->anggotaLain); // pesanan yang tetap berlaku
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.cancel', $batal));

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.index', ['status' => 'cancelled']))
        ->assertOk()
        ->assertSee('Siti Nurhaliza')
        ->assertDontSee('Budi Santoso');
});

test('pesanan yang sudah lunas tidak diberi ajakan membatalkan', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $order->update(['payment_status' => PaymentStatus::Paid, 'payment_confirmed_at' => now()]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertDontSee('Batalkan pesanan')
        ->assertDontSee('hubungi pengurus koperasi');
});

test('struk pesanan batal memakai catatannya sendiri', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $order->update(['payment_status' => PaymentStatus::AwaitingConfirmation]);
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.cancel', $order), ['uang_dikembalikan' => '1']);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.struk', $order))
        ->assertOk()
        ->assertSee('Pesanan ini sudah dibatalkan dan tidak ditagih.')
        ->assertSee('Pembayaran yang sudah masuk dikembalikan oleh pengurus koperasi.')
        ->assertDontSee('Barang dibelanjakan koperasi')
        // Tidak ada tombol WhatsApp, jadi keterangannya juga tidak muncul
        ->assertDontSee('Tombol WhatsApp mengirim struk');
});

test('pengurus masih melihat status bayar pesanan batal untuk pengembalian uang', function () {
    $order = ($this->pesanBeras)($this->anggota);
    $order->update(['payment_status' => PaymentStatus::AwaitingConfirmation]);
    $this->actingAs($this->admin, 'web')->patch(route('admin.orders.cancel', $order), ['uang_dikembalikan' => '1']);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Pembayaran sebelum dibatalkan')
        ->assertSee('Menunggu Konfirmasi');
});

test('isian pembatalan yang ngawur ditolak dengan rapi', function () {
    $order = ($this->pesanBeras)($this->anggota);

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.cancel', $order), ['alasan' => ['bukan', 'teks']])
        ->assertSessionHasErrors('alasan');

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.cancel', $order), ['alasan' => str_repeat('a', 256)])
        ->assertSessionHasErrors('alasan');

    expect($order->fresh()->status)->not->toBe(OrderStatus::Cancelled);
});
