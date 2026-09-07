<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Pembayaran lewat QRIS koperasi.
 *
 * Diminta Pak Emir lewat Zoom 3 Sep 2026 (lihat Lampiran C di CLAUDE.md).
 *
 * QRIS koperasi itu QRIS statis cetakan, BUKAN payment gateway. Uangnya masuk
 * langsung ke rekening koperasi dan aplikasi tidak pernah diberi tahu apa pun,
 * jadi tidak ada webhook yang bisa menandai lunas otomatis. Alurnya dua
 * langkah: pemesan menyatakan sudah bayar, pengurus mencocokkan ke mutasi
 * lalu mengonfirmasi. Ini yang disetujui Pak Emir sendiri sebagai jalan manual.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111', 'password' => 'anggota123', 'is_active' => true,
    ]);
    $this->anggotaLain = Member::create([
        'member_code' => '0002 A', 'full_name' => 'Budi Santoso',
        'whatsapp_number' => '628122222222', 'password' => 'anggota123', 'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $periode = OrderPeriod::create([
        'label' => 'Periode Uji', 'start_date' => now()->subDay(),
        'end_date' => now()->addDay(), 'status' => 'open',
    ]);

    $produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras', 'buy_price' => 65000,
        'sell_price' => 72000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);

    // Pesanan yang totalnya sudah final, jadi ada nominal yang bisa dibayar.
    $this->pesanan = Order::create([
        'order_period_id' => $periode->id, 'user_type' => UserType::Member,
        'member_id' => $this->anggota->id, 'whatsapp_number' => '628111111111',
        'status' => OrderStatus::Verified, 'total_amount' => 144000,
    ]);
    $this->pesanan->orderItems()->create([
        'product_id' => $produk->id, 'quantity' => 2, 'price_at_order' => 72000,
    ]);

    // Pesanan yang masih memuat produk fluktuatif, totalnya belum ada.
    $this->belumFinal = Order::create([
        'order_period_id' => $periode->id, 'user_type' => UserType::Member,
        'member_id' => $this->anggota->id, 'whatsapp_number' => '628111111111',
        'status' => OrderStatus::Pending, 'total_amount' => null,
    ]);
});

test('pesanan baru dimulai dari belum dibayar', function () {
    expect($this->pesanan->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('pemesan melihat QRIS dan nominalnya di halaman pesanan', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $this->pesanan))
        ->assertOk()
        ->assertSee('Nominal yang dibayar')
        ->assertSee('Rp144.000')
        ->assertSee('Saya sudah bayar');
});

test('menyatakan sudah bayar memindahkan status ke menunggu konfirmasi', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan))
        ->assertRedirect(route('member.orders.show', $this->pesanan));

    $this->pesanan->refresh();

    expect($this->pesanan->payment_status)->toBe(PaymentStatus::AwaitingConfirmation);
    expect($this->pesanan->paid_declared_at)->not->toBeNull();

    // Belum lunas. Yang menentukan itu pengurus, bukan pernyataan pemesan.
    expect($this->pesanan->payment_confirmed_at)->toBeNull();
});

test('setelah menyatakan bayar, QRIS tidak ditampilkan lagi', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan));

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $this->pesanan))
        ->assertSee('Menunggu pengurus mencocokkan')
        ->assertDontSee('Nominal yang dibayar');
});

test('bukti transfer disimpan di disk privat, bukan disk publik', function () {
    // Bukti transfer memuat nama pemilik rekening dan nomor rekening. Kalau
    // disimpan di disk publik, siapa pun yang punya URL-nya bisa membukanya
    // tanpa login. Sempat begitu, lalu dipindah ke disk 'local'.
    Storage::fake('local');
    Storage::fake('public');

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

    $this->pesanan->refresh();

    expect($this->pesanan->payment_proof_path)->not->toBeNull();
    Storage::disk('local')->assertExists($this->pesanan->payment_proof_path);
    Storage::disk('public')->assertMissing($this->pesanan->payment_proof_path);
});

test('pemilik pesanan bisa membuka bukti transfernya sendiri', function () {
    Storage::fake('local');

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.payment-proof', $this->pesanan))
        ->assertOk();
});

test('anggota lain tidak bisa membuka bukti transfer orang', function () {
    Storage::fake('local');

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

    $this->actingAs($this->anggotaLain, 'member')
        ->get(route('member.orders.payment-proof', $this->pesanan))
        ->assertForbidden();
});

test('pengurus bisa membuka bukti transfer pesanan mana pun', function () {
    Storage::fake('local');

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.payment-proof', $this->pesanan))
        ->assertOk();
});

test('pesanan tanpa bukti transfer mengembalikan 404', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.payment-proof', $this->pesanan))
        ->assertNotFound();
});

test('bukti transfer boleh dikosongkan', function () {
    // Sengaja opsional: mewajibkannya menambah satu langkah buat seratusan
    // anggota tiap periode, sementara pengurus tetap harus mencocokkan ke
    // rekening apa pun yang dilampirkan.
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan))
        ->assertRedirect();

    expect($this->pesanan->fresh()->payment_proof_path)->toBeNull();
});

test('berkas selain gambar ditolak', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan), [
            'payment_proof' => UploadedFile::fake()->create('virus.exe', 100),
        ])->assertSessionHasErrors('payment_proof');

    expect($this->pesanan->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('tidak bisa menyatakan bayar dua kali', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan));

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan))
        ->assertStatus(422);
});

test('tidak bisa membayar pesanan yang nominalnya belum final', function () {
    // Pesanan yang memuat produk fluktuatif belum punya angka yang bisa dibayar.
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->belumFinal))
        ->assertStatus(422);
});

test('halaman pesanan yang belum final tidak menampilkan QRIS', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $this->belumFinal))
        ->assertOk()
        ->assertSee('Menunggu pengurus memastikan harga')
        ->assertDontSee('Saya sudah bayar');
});

test('anggota tidak bisa membayar pesanan milik anggota lain', function () {
    $punyaOrangLain = Order::create([
        'order_period_id' => $this->pesanan->order_period_id, 'user_type' => UserType::Member,
        'member_id' => $this->anggotaLain->id, 'whatsapp_number' => '628122222222',
        'status' => OrderStatus::Verified, 'total_amount' => 72000,
    ]);

    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $punyaOrangLain))
        ->assertForbidden();

    expect($punyaOrangLain->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('pengurus mengonfirmasi lunas', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.declare-paid', $this->pesanan));

    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.confirm-payment', $this->pesanan))
        ->assertRedirect();

    $this->pesanan->refresh();

    expect($this->pesanan->payment_status)->toBe(PaymentStatus::Paid);
    expect($this->pesanan->payment_confirmed_at)->not->toBeNull();
});

test('pengurus bisa menandai lunas walau pemesan belum menyatakan apa pun', function () {
    // Kadang orang membayar tanpa menekan tombol apa pun, dan pengurus tetap
    // harus bisa menandainya setelah melihat uangnya masuk.
    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.confirm-payment', $this->pesanan))
        ->assertRedirect();

    expect($this->pesanan->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

test('anggota tidak bisa menandai lunas lewat jalur pengurus', function () {
    // actingAs($member, 'member') diam-diam memanggil shouldUse('member'),
    // yang mengganti guard DEFAULT seluruh tes jadi 'member'. Akibatnya rute
    // pengurus (yang pakai middleware auth tanpa menyebut guard) malah ikut
    // lolos, padahal di browser asli tidak begitu. Guard default dikembalikan
    // ke 'web' supaya yang diukur perilaku sebenarnya, bukan efek samping
    // alat tesnya. Pola yang sama dipakai di KeamananAksesTest.
    $this->actingAs($this->anggota, 'member');
    app('auth')->shouldUse('web');

    $this->patch(route('admin.orders.confirm-payment', $this->pesanan))
        ->assertRedirect(route('login'));

    expect($this->pesanan->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('non-anggota membayar pesanan OPD-nya sendiri', function () {
    $pesananOpd = Order::create([
        'order_period_id' => $this->pesanan->order_period_id, 'user_type' => UserType::NonMember,
        'non_member_name' => 'Rina', 'opd_id' => $this->opd->id,
        'whatsapp_number' => '628133333333', 'status' => OrderStatus::Verified, 'total_amount' => 50000,
    ]);

    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.orders.declare-paid', $pesananOpd))
        ->assertRedirect();

    expect($pesananOpd->fresh()->payment_status)->toBe(PaymentStatus::AwaitingConfirmation);
});

test('non-anggota tidak bisa membayar pesanan OPD lain', function () {
    $opdLain = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'opd54321']);

    $pesananOpdLain = Order::create([
        'order_period_id' => $this->pesanan->order_period_id, 'user_type' => UserType::NonMember,
        'non_member_name' => 'Doni', 'opd_id' => $opdLain->id,
        'whatsapp_number' => '628144444444', 'status' => OrderStatus::Verified, 'total_amount' => 50000,
    ]);

    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.orders.declare-paid', $pesananOpdLain))
        ->assertForbidden();
});

test('status pembayaran terpisah dari status pesanan', function () {
    // Dua perjalanan berbeda: yang satu perjalanan pesanan, yang satu
    // perjalanan uang. Mengonfirmasi bayar tidak boleh mengubah status
    // pesanannya, dan sebaliknya.
    $this->actingAs($this->admin, 'web')
        ->patch(route('admin.orders.confirm-payment', $this->pesanan));

    $this->pesanan->refresh();

    expect($this->pesanan->payment_status)->toBe(PaymentStatus::Paid);
    expect($this->pesanan->status)->toBe(OrderStatus::Verified);
});
