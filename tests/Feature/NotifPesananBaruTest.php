<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Notifikasi pesanan baru di halaman pengurus (1 Okt 2026)
|--------------------------------------------------------------------------
| Halaman admin bertanya berkala ke rute admin.orders.baru: "sejak pesanan
| nomor sekian, sudah ada berapa pesanan baru?". Jawabannya cuma dua angka.
| Yang dihitung: pesanan yang id-nya lebih besar dari titik awal dan belum
| dibatalkan. Pengecekan pertama (tanpa titik awal) selalu 0.
*/

beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan Oktober 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    // Pembuat pesanan singkat untuk tes ini: cukup kolom yang wajib.
    $this->buatPesanan = fn (OrderStatus $status = OrderStatus::Pending) => Order::create([
        'order_period_id' => $this->periode->id,
        'user_type' => UserType::NonMember,
        'non_member_name' => 'Pemesan Uji',
        'opd_id' => $this->opd->id,
        'whatsapp_number' => '62811',
        'status' => $status,
    ]);
});

test('pengecekan pertama mencatat id terbaru dan belum menghitung apa pun', function () {
    ($this->buatPesanan)();
    $terbaru = ($this->buatPesanan)();

    $this->actingAs($this->admin)
        ->getJson(route('admin.orders.baru'))
        ->assertOk()
        ->assertExactJson(['terakhir' => $terbaru->id, 'baru' => 0]);
});

test('belum ada pesanan sama sekali: titik awalnya 0', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.orders.baru'))
        ->assertOk()
        ->assertExactJson(['terakhir' => 0, 'baru' => 0]);
});

test('menghitung pesanan yang masuk setelah titik awal, tanpa pesanan batal', function () {
    $lama = ($this->buatPesanan)();

    ($this->buatPesanan)();
    ($this->buatPesanan)(OrderStatus::Verified);
    $batal = ($this->buatPesanan)(OrderStatus::Cancelled);

    $this->actingAs($this->admin)
        ->getJson(route('admin.orders.baru', ['sejak' => $lama->id]))
        ->assertOk()
        ->assertExactJson(['terakhir' => $batal->id, 'baru' => 2]);
});

test('tidak ada yang baru kalau titik awalnya sudah pesanan terbaru', function () {
    $terbaru = ($this->buatPesanan)();

    $this->actingAs($this->admin)
        ->getJson(route('admin.orders.baru', ['sejak' => $terbaru->id]))
        ->assertOk()
        ->assertJson(['baru' => 0]);
});

test('titik awal yang bukan angka wajar ditolak', function (mixed $sejak) {
    $this->actingAs($this->admin)
        ->getJson(route('admin.orders.baru', ['sejak' => $sejak]))
        ->assertUnprocessable();
})->with([
    'huruf' => ['abc'],
    'negatif' => [-1],
    'kebesaran' => ['99999999999999999999999'],
]);

test('tamu dan anggota tidak bisa mengintip jumlah pesanan', function () {
    $this->getJson(route('admin.orders.baru'))->assertUnauthorized();

    $anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628121000001', 'password' => 'anggota123', 'is_active' => true,
    ]);

    $this->actingAs($anggota, 'member')
        ->getJson(route('admin.orders.baru'))
        ->assertUnauthorized();
});

test('kotak notifikasi terpasang di halaman admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('notifPesanan(', false)
        // @js() menulis garis miring sebagai \/, jadi yang dicocokkan cukup
        // ujung alamatnya saja.
        ->assertSee('pesanan-baru', false);
});
