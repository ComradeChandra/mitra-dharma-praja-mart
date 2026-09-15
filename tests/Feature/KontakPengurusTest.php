<?php

use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\KontakPengurusService;
use App\Services\OrderService;

/*
|--------------------------------------------------------------------------
| Nomor WhatsApp koperasi & tombol "Hubungi Pengurus" (16 Sep 2026)
|--------------------------------------------------------------------------
| Pengurus mengisi nomornya sendiri di Admin -> Pengaturan. Anggota,
| non-anggota, dan tamu mendapat tombol yang membuka chat WhatsApp ke nomor
| itu, dengan salam pembuka yang sudah menyebut siapa pengirimnya. Selama
| nomornya kosong, tombol tidak tampil di mana pun.
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
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '6281200000001',
        'password' => 'anggota123',
        'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'rahasia123']);

    $this->beras = Product::create([
        'category' => 'Sembako', 'name' => 'Beras 5kg', 'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'stock' => 0, 'is_active' => true,
    ]);

    $this->isiNomor = fn (?string $nomor = '081234567890') => app(KontakPengurusService::class)->simpanNomorWhatsApp($nomor);
});

test('pengurus bisa mengisi nomor WhatsApp koperasi, boleh dengan spasi dan strip', function () {
    $this->actingAs($this->admin, 'web')
        ->put(route('admin.settings.update'), ['whatsapp_number' => '0812-3456 7890'])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();

    expect(app(KontakPengurusService::class)->nomorWhatsApp())->toBe('081234567890');

    // Halaman pengaturan menampilkan nomornya dan tautan untuk mencobanya
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('value="081234567890"', false)
        ->assertSee('https://wa.me/6281234567890', false);
});

test('nomor bisa dikosongkan dan tombolnya ikut hilang', function () {
    ($this->isiNomor)();

    $this->actingAs($this->admin, 'web')
        ->put(route('admin.settings.update'), ['whatsapp_number' => ''])
        ->assertRedirect(route('admin.settings.edit'));

    expect(app(KontakPengurusService::class)->nomorWhatsApp())->toBeNull();

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertDontSee('wa.me/6281234567890', false);
});

test('nomor yang jelas salah ditolak dan tidak tersimpan', function (string $nomor) {
    $this->actingAs($this->admin, 'web')
        ->put(route('admin.settings.update'), ['whatsapp_number' => $nomor])
        ->assertSessionHasErrors('whatsapp_number');

    expect(app(KontakPengurusService::class)->nomorWhatsApp())->toBeNull();
})->with([
    'bukan diawali 0/62' => '12345678901',
    'terlalu pendek' => '0812',
    'ada hurufnya' => '0812abc45678',
    'kebanyakan angka' => '0812345678901234567',
]);

test('cuma Admin Utama yang bisa membuka dan mengubah pengaturan', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));

    $this->actingAs($this->anggota, 'member')
        ->put(route('admin.settings.update'), ['whatsapp_number' => '081299999999'])
        ->assertRedirect(route('login'));

    // Akun staf biasa (Pengurus) juga ditolak
    $staf = User::factory()->pengurus()->create();
    $this->actingAs($staf, 'web')->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($staf, 'web')
        ->put(route('admin.settings.update'), ['whatsapp_number' => '081299999999'])
        ->assertForbidden();

    expect(app(KontakPengurusService::class)->nomorWhatsApp())->toBeNull();
});

test('anggota melihat tombol WhatsApp dengan salam berisi nama dan kode anggotanya', function () {
    ($this->isiNomor)();

    $salam = rawurlencode('Halo pengurus Koperasi Mitra Dharma Praja, saya Siti Nurhaliza (anggota 0001 A).');

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('https://wa.me/6281234567890?text='.$salam, false);

    // Katalog memakai layout publik, tombolnya tetap ada dan tetap tahu siapa yang masuk
    $this->actingAs($this->anggota, 'member')
        ->get(route('catalog.index'))
        ->assertSee('https://wa.me/6281234567890?text='.$salam, false);
});

test('non-anggota melihat tombol WhatsApp yang menyebut OPD-nya', function () {
    ($this->isiNomor)();

    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('non-member.orders.create'))
        ->assertOk()
        ->assertSee(rawurlencode('saya non-anggota dari Dinas Kesehatan.'), false);
});

test('di form pesan, tombol melayang diganti tautan di atas daftar produk', function () {
    ($this->isiNomor)();

    // Tanda di tag form yang menyembunyikan tombol melayang lewat CSS, plus
    // tautan pengganti yang menyebut periodenya. Dicek dengan ">" di
    // belakangnya: nama tanda yang sama juga ada di class tombol melayang.
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.create'))
        ->assertOk()
        ->assertSee('data-tanpa-kontak-melayang>', false)
        ->assertSee('Ada pertanyaan soal produk? Hubungi pengurus')
        ->assertSee(rawurlencode('Saya mau bertanya soal produk di periode "Pemesanan September 2026".'), false);

    // Halaman lain tidak membawa tanda itu, jadi tombol melayangnya tampil
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertSee('aria-label="Hubungi pengurus lewat WhatsApp"', false)
        ->assertDontSee('data-tanpa-kontak-melayang>', false);
});

test('selama nomor belum diisi, tombol dan tautan WhatsApp ke pengurus tidak tampil', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertDontSee('Hubungi pengurus lewat WhatsApp');

    $this->get(route('masuk'))
        ->assertOk()
        ->assertDontSee('Butuh bantuan untuk masuk? Hubungi pengurus');
});

test('halaman masuk menawarkan bantuan lewat WhatsApp pengurus', function () {
    ($this->isiNomor)();

    foreach ([route('masuk'), route('member.login'), route('non-member.login')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('Butuh bantuan untuk masuk? Hubungi pengurus')
            ->assertSee(rawurlencode('Saya butuh bantuan untuk masuk ke aplikasi Mitra Dharma Praja Mart.'), false);
    }
});

test('pesanan yang harus dibatalkan lewat pengurus langsung diberi tautan WhatsApp-nya', function () {
    ($this->isiNomor)();

    $order = app(OrderService::class)->createOrder(
        $this->anggota, $this->periode,
        [['product_id' => $this->beras->id, 'quantity' => 1]],
        DeliveryMethod::Ambil, null,
    );
    $pesanBatal = rawurlencode('Saya ingin membatalkan pesanan periode "Pemesanan September 2026"');

    // Masih bisa dibatalkan sendiri: tidak perlu tautan ke pengurus
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertDontSee($pesanBatal, false);

    // Periode ditutup: pembatalan harus lewat pengurus, tautannya muncul
    $this->periode->update(['status' => OrderPeriodStatus::Closed]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $order))
        ->assertOk()
        ->assertSee($pesanBatal, false);
});

test('pesan pembatalan dari non-anggota menyebut nama pemesannya', function () {
    ($this->isiNomor)();

    $order = app(OrderService::class)->createNonMemberOrder(
        $this->opd, 'Rina Marlina', '081200000002', $this->periode,
        [['product_id' => $this->beras->id, 'quantity' => 1]],
        DeliveryMethod::Ambil, null,
    );
    $this->periode->update(['status' => OrderPeriodStatus::Closed]);

    $this->withSession(['non_member_opd_id' => $this->opd->id, 'non_member_order_ids' => [$order->id]])
        ->get(route('non-member.orders.show', $order))
        ->assertOk()
        ->assertSee(rawurlencode(' atas nama Rina Marlina.'), false);
});

test('tombol Hubungi Pengurus tidak tampil di area pengurus', function () {
    ($this->isiNomor)();

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertDontSee('aria-label="Hubungi pengurus lewat WhatsApp"', false);
});

test('dasbor mengingatkan Admin Utama selama nomor WhatsApp belum diisi', function () {
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.dashboard'))
        ->assertSee('Nomor WhatsApp koperasi belum diisi');

    // Pengurus biasa tidak bisa mengisinya, jadi tidak diingatkan
    $this->actingAs(User::factory()->pengurus()->create(), 'web')
        ->get(route('admin.dashboard'))
        ->assertDontSee('Nomor WhatsApp koperasi belum diisi');

    ($this->isiNomor)();

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.dashboard'))
        ->assertDontSee('Nomor WhatsApp koperasi belum diisi');
});
