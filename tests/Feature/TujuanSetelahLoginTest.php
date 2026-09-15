<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Tujuan setelah login (16 Sep 2026)
|--------------------------------------------------------------------------
| Laravel mengingat halaman terlindung terakhir yang dibuka, dari wilayah
| mana pun. Tanpa pembatasan, pengurus yang sempat membuka tautan halaman
| anggota lalu login sebagai pengurus diarahkan ke halaman anggota (ketemu
| saat uji pasang mode produksi). Halaman yang diingat cuma dipakai kalau
| masih di wilayah peran yang login; lihat RedirectsWithinArea.
*/

beforeEach(function () {
    $this->admin = User::factory()->create(); // password bawaan pabrik: "password"
    Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628121000001', 'password' => 'anggota123', 'is_active' => true,
    ]);
    $this->opd = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'rahasia123']);

    $this->masukPengurus = fn () => $this->post('/login', ['email' => $this->admin->email, 'password' => 'password']);
    $this->masukAnggota = fn () => $this->post(route('member.login.store'), ['member_code' => '0001 A', 'password' => 'anggota123']);
    $this->masukNonAnggota = fn () => $this->post(route('non-member.login.store'), ['opd_department_id' => $this->opd->id, 'access_code' => 'rahasia123']);
});

test('pengurus yang sempat membuka halaman anggota tetap diarahkan ke dasbor pengurus', function () {
    $this->get('/anggota/pesan')->assertRedirect(); // belum login: halaman ini diingat

    ($this->masukPengurus)()->assertRedirect(route('admin.dashboard'));
});

test('pengurus kembali ke halaman pengurus yang tadi dicoba dibuka', function () {
    $this->get(route('admin.orders.index'))->assertRedirect();

    ($this->masukPengurus)()->assertRedirect(route('admin.orders.index'));
});

test('anggota yang sempat membuka halaman pengurus diarahkan ke berandanya sendiri', function () {
    $this->get(route('admin.orders.index'))->assertRedirect();

    ($this->masukAnggota)()->assertRedirect(route('member.dashboard'));
});

test('anggota kembali ke halaman anggota yang tadi dicoba dibuka, mis. dari tautan invoice', function () {
    $this->get(route('member.orders.index'))->assertRedirect();

    ($this->masukAnggota)()->assertRedirect(route('member.orders.index'));
});

test('non-anggota tidak diarahkan ke halaman pengurus', function () {
    $this->get(route('admin.orders.index'))->assertRedirect();

    ($this->masukNonAnggota)()->assertRedirect(route('non-member.orders.create'));
});

test('tanpa halaman yang diingat, tiap peran masuk ke berandanya', function () {
    ($this->masukPengurus)()->assertRedirect(route('admin.dashboard'));
});
