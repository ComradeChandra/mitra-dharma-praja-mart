<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

/*
| Aplikasi ini berbahasa Indonesia, tapi sampai 14 Sep 2026 tidak punya
| berkas bahasa sama sekali. Semua teks bawaan Laravel jatuh ke bahasa
| Inggris: pesan salah password admin, paginasi, pesan validasi yang tidak
| diberi pesan khusus, dan semua halaman error. Test di bawah menjaga supaya
| tidak ada yang balik lagi.
*/

test('salah password admin dijawab dalam bahasa Indonesia', function () {
    $admin = User::factory()->create();

    $this->from(route('login'))->post(route('login'), ['email' => $admin->email, 'password' => 'salahketik']);

    $this->get(route('login'))
        ->assertSee('Email atau password salah.')
        ->assertDontSee('These credentials');
});

test('paginasi daftar admin berbahasa Indonesia', function () {
    $admin = User::factory()->create();
    foreach (range(1, 16) as $i) {
        Product::create(['category' => 'Sembako', 'name' => "Produk $i", 'buy_price' => 1000, 'sell_price' => 1200, 'is_active' => true]);
    }

    $this->actingAs($admin)->get(route('admin.products.index'))
        ->assertSee('Menampilkan')
        ->assertDontSee('Showing');
});

test('pesan validasi tanpa pesan khusus tetap berbahasa Indonesia', function () {
    // Form profil admin tidak menulis messages() sendiri, jadi pesannya
    // datang dari lang/id/validation.php.
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.profile.edit'))
        ->patch(route('admin.profile.update'), ['name' => '', 'email' => 'bukan-email'])
        ->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'email' => 'Email harus berupa alamat email yang valid.',
        ]);
});

test('halaman 404 berbahasa Indonesia dan punya jalan kembali', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSee('lang="id"', false)
        ->assertSee('Halaman tidak ditemukan')
        ->assertSee('Ke halaman utama')
        ->assertDontSee('Not Found');
});

test('penjaga 422 menampilkan pesannya sendiri, bukan halaman polos', function () {
    // Mis. "sudah bayar" terkirim dua kali: yang kedua ditolak OrderService
    // dengan pesan berbahasa Indonesia, dan pesan itu yang harus sampai.
    $periode = OrderPeriod::create(['label' => 'Pemesanan Agustus 2026', 'start_date' => now()->subDay(), 'end_date' => now()->addWeek(), 'status' => OrderPeriodStatus::Open]);
    $anggota = Member::create(['member_code' => '0001 A', 'full_name' => 'Siti', 'whatsapp_number' => '628123', 'password' => 'rahasia123', 'is_active' => true]);
    $pesanan = Order::create(['order_period_id' => $periode->id, 'user_type' => UserType::Member, 'member_id' => $anggota->id, 'whatsapp_number' => '628123', 'status' => OrderStatus::Verified, 'total_amount' => 70000]);

    $this->actingAs($anggota, 'member')->post(route('member.orders.declare-paid', $pesanan));

    $this->actingAs($anggota, 'member')->post(route('member.orders.declare-paid', $pesanan))
        ->assertStatus(422)
        ->assertSee('KODE 422')
        ->assertSee('Pembayaran pesanan ini sudah pernah dinyatakan.');
});
