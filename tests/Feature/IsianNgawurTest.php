<?php

use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Isian ngawur ke SETIAP kolom SETIAP form
|--------------------------------------------------------------------------
| Daftar isiannya sama untuk semua kolom, bukan dipilih sesuai dugaan
| pembuat kode — supaya tidak bias ke "kolom ini pasti aman". Syaratnya satu:
| tidak ada jawaban 5xx. Ditolak dengan pesan itu wajar; error server tidak.
|
| Setelah semua isian dikirim, semua halaman dirender ulang untuk memastikan
| data janggal yang sempat tersimpan tidak merusak tampilan, dan tidak ada
| tag <script> dari isian yang ikut tercetak mentah.
|
| PENTING: jalankan juga di MySQL (lihat catatan di devlog 15 Sep). SQLite
| tidak peduli teks kepanjangan atau angka kebesaran, MySQL menolaknya.
*/

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);
});

function isianNgawur(): array
{
    return [
        'kosong' => '',
        'spasi' => '   ',
        'panjang' => str_repeat('A', 6000),
        'minus' => '-1',
        'nol' => '0',
        'pecahan' => '1.5',
        'raksasa' => '99999999999999999999',
        // Masih muat di PHP, tapi melewati kolom INT/DECIMAL MySQL. Sempat
        // tidak ada di daftar ini, jadi kolom jumlah & stok tidak teruji.
        'miliaran' => '9999999999',
        'triliun' => '99999999999999',
        'huruf' => 'abc',
        'script' => '<script>alert("xss")</script>',
        'sql' => "'\"; DROP TABLE users; --",
        'emoji' => '😀🙏🏽',
        'apostrof' => "Nur'aini & Co",
        'baris' => "baris1\nbaris2",
        'larik' => ['x'],
        'larik-bersarang' => ['k' => ['y']],
        'eksponen' => '1e9',
    ];
}

/**
 * Kirim setiap isian ngawur ke setiap kolom satu per satu, kolom lain tetap
 * berisi nilai yang benar. Kembalikan daftar yang berakhir di 5xx.
 */
function serang($test, string $method, string $url, array $dasar, array $kolomTambahan = []): array
{
    $gagal = [];

    foreach (array_merge(array_keys($dasar), $kolomTambahan) as $kolom) {
        foreach (isianNgawur() as $namaIsian => $isian) {
            $data = $dasar;
            data_set($data, $kolom, $isian);
            RateLimiter::clear('');

            $respons = $test->from($url)->call($method, $url, $data);

            if ($respons->getStatusCode() >= 500) {
                $pesan = $respons->exception ? class_basename($respons->exception).': '.mb_substr($respons->exception->getMessage(), 0, 140) : '';
                $gagal[] = "$method $url [$kolom=$namaIsian] -> {$respons->getStatusCode()} $pesan";
            }
        }
    }

    return $gagal;
}

test('form admin tahan isian ngawur', function () {
    $this->actingAs(User::first());
    $produk = Product::first();
    $anggota = Member::first();
    $opd = OpdDepartment::first();
    $periode = OrderPeriod::orderBy('id')->first();
    $pending = Order::where('status', OrderStatus::Pending)->first();
    $itemFluktuatif = $pending->orderItems()->whereNull('price_at_order')->first();

    $produkDasar = ['name' => 'Produk Uji', 'category' => 'Sembako', 'buy_price' => '1000', 'sell_price' => '1500', 'stock' => '5', 'has_stock_tracking' => '1', 'is_fluctuating' => '0', 'is_active' => '1'];
    $anggotaDasar = ['member_code' => 'UJI 1', 'full_name' => 'Uji', 'whatsapp_number' => '08123', 'address' => 'Jl. Uji', 'password' => 'rahasia123', 'is_active' => '1'];

    $gagal = [
        ...serang($this, 'POST', route('admin.products.store'), $produkDasar, ['image']),
        ...serang($this, 'PUT', route('admin.products.update', $produk), $produkDasar),
        ...serang($this, 'POST', route('admin.members.store'), $anggotaDasar),
        ...serang($this, 'PUT', route('admin.members.update', $anggota), array_merge($anggotaDasar, ['member_code' => $anggota->member_code])),
        ...serang($this, 'POST', route('admin.opd-departments.store'), ['name' => 'OPD Uji', 'access_code' => 'rahasia1']),
        ...serang($this, 'PUT', route('admin.opd-departments.update', $opd), ['name' => $opd->name, 'access_code' => '']),
        ...serang($this, 'POST', route('admin.order-periods.store'), ['label' => 'Uji', 'start_date' => '2027-01-01', 'end_date' => '2027-01-10', 'status' => 'closed']),
        ...serang($this, 'PUT', route('admin.order-periods.update', $periode), ['label' => $periode->label, 'start_date' => $periode->start_date->toDateString(), 'end_date' => $periode->end_date->toDateString(), 'status' => $periode->status->value]),
        ...serang($this, 'PATCH', route('admin.profile.update'), ['name' => 'Pengurus', 'email' => User::first()->email]),
        ...serang($this, 'PUT', route('admin.profile.password.update'), ['current_password' => 'x', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123']),
    ];

    expect($gagal)->toBe([]);
});

test('form anggota tahan isian ngawur', function () {
    $anggota = Member::whereHas('orders', fn ($q) => $q->whereNotNull('total_amount'))->first();
    $this->actingAs($anggota, 'member');
    $produk = Product::where('is_active', true)->where('has_stock_tracking', false)->first();
    $pesanan = $anggota->orders()->whereNotNull('total_amount')->first();

    $gagal = [
        ...serang($this, 'POST', route('member.orders.store'), ['delivery_method' => 'antar', 'delivery_address' => 'Jl. Uji', 'quantity' => [$produk->id => '1']], ['quantity', "quantity.{$produk->id}", 'quantity.999999']),
        ...serang($this, 'PATCH', route('member.profile.update'), ['whatsapp_number' => '08123', 'address' => 'Jl. Uji'], ['photo']),
        ...serang($this, 'PUT', route('member.profile.password.update'), ['current_password' => 'x', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123']),
        ...serang($this, 'POST', route('member.product-requests.store'), ['product_name' => 'Teh Uji']),
        ...serang($this, 'POST', route('member.orders.declare-paid', $pesanan), [], ['payment_proof']),
    ];

    expect($gagal)->toBe([]);
});

test('form non-anggota dan tamu tahan isian ngawur', function () {
    $produk = Product::where('is_active', true)->where('has_stock_tracking', false)->first();
    $opd = OpdDepartment::first();

    $gagal = [
        ...serang($this, 'POST', route('member.login.store'), ['member_code' => '0001 A', 'password' => 'salah']),
        ...serang($this, 'POST', route('non-member.login.store'), ['opd_department_id' => (string) $opd->id, 'access_code' => 'salah']),
        ...serang($this, 'POST', route('login'), ['email' => 'admin@mitradharma.test', 'password' => 'salah']),
    ];

    $this->withSession(['non_member_opd_id' => $opd->id]);

    $gagal = [
        ...$gagal,
        ...serang($this, 'POST', route('non-member.orders.store'), ['non_member_name' => 'Teti', 'whatsapp_number' => '08123', 'delivery_method' => 'antar', 'delivery_address' => 'Jl. Uji', 'quantity' => [$produk->id => '1']], ['quantity', "quantity.{$produk->id}"]),
        ...serang($this, 'POST', route('non-member.product-requests.store'), ['requester_name' => 'Teti', 'product_name' => 'Roti Uji']),
    ];

    expect($gagal)->toBe([]);
});

test('data janggal yang sempat tersimpan tidak merusak tampilan dan tidak tercetak mentah', function () {
    $admin = User::first();
    $this->actingAs($admin);

    // Simpan data berisi isian janggal yang LOLOS validasi, lewat form sungguhan.
    $this->post(route('admin.products.store'), ['name' => '<script>alert("xss")</script> Kopi\'s "Spesial"', 'category' => "Min'uman <b>tebal</b>", 'buy_price' => '1000', 'sell_price' => '1500', 'is_active' => '1']);
    $this->post(route('admin.members.store'), ['member_code' => 'X<1>', 'full_name' => '<img src=x onerror=alert(1)> Nur\'aini', 'whatsapp_number' => '08123', 'address' => '"><script>alert(2)</script>', 'password' => 'rahasia123', 'is_active' => '1']);
    $this->post(route('admin.opd-departments.store'), ['name' => 'Dinas <script>alert(3)</script>', 'access_code' => 'rahasia1']);

    $anggota = Member::where('member_code', 'X<1>')->firstOrFail();
    $produk = Product::where('name', 'like', '%Spesial%')->firstOrFail();

    $this->actingAs($anggota, 'member')->post(route('member.orders.store'), ['delivery_method' => 'antar', 'delivery_address' => '<script>alert(4)</script>', 'quantity' => [$produk->id => '1']]);
    $this->actingAs($anggota, 'member')->post(route('member.product-requests.store'), ['product_name' => '<script>alert(5)</script>']);
    $pesanan = $anggota->orders()->latest('id')->firstOrFail();

    $halaman = [
        [$admin, 'web', [route('admin.dashboard'), route('admin.products.index'), route('admin.members.index'), route('admin.opd-departments.index'), route('admin.orders.index'), route('admin.orders.show', $pesanan), route('admin.orders.struk', $pesanan), route('admin.product-requests.index'), route('admin.order-periods.rekap', OrderPeriod::yangSedangDibuka())]],
        [$anggota, 'member', [route('member.dashboard'), route('member.orders.create'), route('member.orders.index'), route('member.orders.show', $pesanan), route('member.orders.struk', $pesanan), route('member.product-requests.index'), route('catalog.index'), route('catalog.show', $produk)]],
    ];

    foreach ($halaman as [$siapa, $guard, $daftar]) {
        $this->actingAs($siapa, $guard);
        foreach ($daftar as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            expect($html)->not->toContain('<script>alert(')->not->toContain('<img src=x')->not->toContain('<b>tebal</b>');
        }
    }
});

test('verifikasi harga tahan isian ngawur (pesanan baru untuk setiap isian)', function () {
    // Terpisah dari uji admin di atas: begitu satu isian lolos, pesanannya
    // terverifikasi dan isian berikutnya tidak lagi diuji sama sekali.
    $this->actingAs(User::first());
    $periode = OrderPeriod::yangSedangDibuka();
    $telur = Product::whereNull('sell_price')->firstOrFail();
    $gagal = [];

    foreach (isianNgawur() as $nama => $isian) {
        foreach (['prices' => $isian, 'prices.item' => $isian] as $bentuk => $nilai) {
            $pesanan = Order::create(['order_period_id' => $periode->id, 'user_type' => 'member', 'member_id' => Member::first()->id, 'whatsapp_number' => '628123', 'status' => OrderStatus::Pending]);
            $item = $pesanan->orderItems()->create(['product_id' => $telur->id, 'quantity' => 999, 'price_at_order' => null]);
            $data = $bentuk === 'prices' ? ['prices' => $nilai] : ['prices' => [$item->id => $nilai]];

            $respons = $this->patch(route('admin.orders.verify', $pesanan), $data);

            if ($respons->getStatusCode() >= 500) {
                $gagal[] = "[$bentuk=$nama] -> {$respons->getStatusCode()} ".($respons->exception ? mb_substr($respons->exception->getMessage(), 0, 120) : '');
            }
        }
    }

    expect($gagal)->toBe([]);
});
