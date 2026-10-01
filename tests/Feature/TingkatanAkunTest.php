<?php

use App\Enums\AdminRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Tingkatan akun pengurus (16 Sep 2026)
|--------------------------------------------------------------------------
| Dua peran di sisi pengurus:
| - Admin Utama : semua pekerjaan + kelola akun pengurus + pengaturan
| - Pengurus    : semua pekerjaan harian
| Akun staf yang tidak bertugas dinonaktifkan, bukan dihapus.
*/

beforeEach(function () {
    $this->utama = User::factory()->create(['name' => 'Bu Ketua']);
    $this->staf = User::factory()->pengurus()->create(['name' => 'Staf Satu', 'email' => 'staf@koperasi.test']);
});

test('akun pertama dari seeder adalah Admin Utama', function () {
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', config('koperasi.admin.email'))->first()->role)->toBe(AdminRole::AdminUtama);
});

test('Admin Utama bisa menambah akun pengurus', function () {
    $this->actingAs($this->utama, 'web')
        ->post(route('admin.accounts.store'), [
            'name' => 'Staf Dua',
            'email' => 'staf2@koperasi.test',
            'role' => 'pengurus',
            'password' => 'rahasia-123',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.accounts.index'))
        ->assertSessionHasNoErrors();

    $baru = User::where('email', 'staf2@koperasi.test')->first();
    expect($baru->role)->toBe(AdminRole::Pengurus);
    expect($baru->is_active)->toBeTrue();
    expect(Hash::check('rahasia-123', $baru->password))->toBeTrue();
});

test('email akun harus unik', function () {
    $this->actingAs($this->utama, 'web')
        ->post(route('admin.accounts.store'), [
            'name' => 'Kembar', 'email' => 'staf@koperasi.test', 'role' => 'pengurus', 'password' => 'rahasia-123',
        ])
        ->assertSessionHasErrors('email');
});

test('Pengurus tidak bisa membuka akun pengurus maupun pengaturan', function () {
    foreach ([route('admin.accounts.index'), route('admin.accounts.create'), route('admin.accounts.edit', $this->utama), route('admin.settings.edit')] as $url) {
        $this->actingAs($this->staf, 'web')->get($url)->assertForbidden();
    }

    // Termasuk mencoba menaikkan perannya sendiri lewat kiriman langsung
    $this->actingAs($this->staf, 'web')
        ->put(route('admin.accounts.update', $this->staf), [
            'name' => 'Staf Satu', 'email' => 'staf@koperasi.test', 'role' => 'admin_utama', 'is_active' => '1',
        ])
        ->assertForbidden();

    expect($this->staf->fresh()->role)->toBe(AdminRole::Pengurus);
});

test('Pengurus tetap bisa mengerjakan pekerjaan harian', function () {
    foreach ([
        route('admin.dashboard'), route('admin.orders.index'), route('admin.products.index'),
        route('admin.members.index'), route('admin.opd-departments.index'), route('admin.order-periods.index'),
        route('admin.product-requests.index'), route('admin.password-requests.index'), route('admin.profile.edit'),
    ] as $url) {
        $this->actingAs($this->staf, 'web')->get($url)->assertOk();
    }
});

test('tab Pengurus dan menu Pengaturan cuma muncul untuk Admin Utama', function () {
    // Akun Pengurus dibuka dari tab "Pengurus" di halaman Anggota (1 Okt 2026),
    // bukan lagi dari menu akun. Pengaturan tetap di menu akun.
    $this->actingAs($this->utama, 'web')
        ->get(route('admin.members.index'))
        ->assertSee('aria-label="Jenis pengguna"', false)
        ->assertSee(route('admin.accounts.index'), false);

    $this->actingAs($this->utama, 'web')
        ->get(route('admin.dashboard'))
        ->assertSee(route('admin.settings.edit'), false);

    $this->actingAs($this->staf, 'web')
        ->get(route('admin.members.index'))
        ->assertDontSee('aria-label="Jenis pengguna"', false)
        ->assertDontSee(route('admin.accounts.index'), false);

    $this->actingAs($this->staf, 'web')
        ->get(route('admin.dashboard'))
        ->assertDontSee(route('admin.accounts.index'), false)
        ->assertDontSee(route('admin.settings.edit'), false);
});

test('halaman Akun Pengurus menandai tab Pengurus yang sedang dibuka', function () {
    $this->actingAs($this->utama, 'web')
        ->get(route('admin.accounts.index'))
        ->assertOk()
        ->assertSee('aria-current="page"', false)
        ->assertSee('+ Tambah Pengurus');
});

test('akun nonaktif tidak bisa masuk', function () {
    $this->staf->update(['is_active' => false]);

    $this->post(route('login'), ['email' => 'staf@koperasi.test', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Akun ini sedang dinonaktifkan. Hubungi Admin Utama koperasi kalau ini keliru.']);

    $this->assertGuest('web');
});

test('akun yang dinonaktifkan saat masih masuk langsung dikeluarkan', function () {
    $this->actingAs($this->staf, 'web');
    $this->staf->update(['is_active' => false]);

    $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    $this->assertGuest('web');
});

test('Admin Utama tidak bisa menurunkan peran atau menonaktifkan akunnya sendiri', function () {
    $this->actingAs($this->utama, 'web')
        ->put(route('admin.accounts.update', $this->utama), [
            'name' => 'Bu Ketua', 'email' => $this->utama->email, 'role' => 'pengurus',
        ])
        ->assertSessionHasErrors(['role', 'is_active']);

    $akun = $this->utama->fresh();
    expect($akun->role)->toBe(AdminRole::AdminUtama);
    expect($akun->is_active)->toBeTrue();
});

test('Admin Utama bisa mengubah peran dan menonaktifkan akun lain, password kosong tidak mengganti', function () {
    $passwordLama = $this->staf->password;

    $this->actingAs($this->utama, 'web')
        ->put(route('admin.accounts.update', $this->staf), [
            'name' => 'Staf Satu', 'email' => 'staf@koperasi.test', 'role' => 'admin_utama', 'password' => '',
        ])
        ->assertRedirect(route('admin.accounts.index'))
        ->assertSessionHasNoErrors();

    $akun = $this->staf->fresh();
    expect($akun->role)->toBe(AdminRole::AdminUtama);
    expect($akun->is_active)->toBeFalse();
    expect($akun->password)->toBe($passwordLama);
});

test('halaman akun pengurus dan profil menjelaskan siapa bisa apa', function () {
    $this->actingAs($this->utama, 'web')
        ->get(route('admin.accounts.index'))
        ->assertOk()
        ->assertSee('Staf Satu')
        ->assertSee('Siapa bisa apa')
        ->assertSee('Khusus Admin Utama');

    $this->actingAs($this->staf, 'web')
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('Peran akun kamu')
        ->assertSee(AdminRole::Pengurus->keterangan());
});
