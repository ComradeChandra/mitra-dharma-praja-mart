<?php

use App\Enums\PasswordResetStatus;
use App\Models\Member;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Lupa password anggota: antrean untuk semua pengurus (16 Sep 2026)
|--------------------------------------------------------------------------
| Anggota mengajukan dari halaman masuk; siapa pun pengurus (bukan cuma
| Admin Utama) bisa membuatkan password baru, lalu mengirimnya lewat
| WhatsApp ke nomor yang terdaftar. Tercatat siapa yang menangani.
*/

beforeEach(function () {
    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza', 'whatsapp_number' => '081234560001',
        'password' => 'password-lama', 'is_active' => true,
    ]);
    $this->staf = User::factory()->pengurus()->create(['name' => 'Staf Satu']);
    $this->stafLain = User::factory()->pengurus()->create(['name' => 'Staf Dua']);

    $this->ajukan = fn (array $isi = []) => $this->post(route('member.password-request.store'), [
        'member_code' => '0001 A', 'note' => null, ...$isi,
    ]);
});

test('anggota bisa mengajukan lupa password tanpa masuk, nomornya disamarkan', function () {
    $this->get(route('member.password-request.create'))->assertOk()->assertSee('Siti Nurhaliza');

    ($this->ajukan)(['note' => 'HP saya hilang'])
        ->assertRedirect(route('member.password-request.create'))
        ->assertSessionHas('terkirim', '•••• 0001');

    $permintaan = PasswordResetRequest::sole();
    expect($permintaan->status)->toBe(PasswordResetStatus::Menunggu);
    expect($permintaan->note)->toBe('HP saya hilang');

    // Halaman hasilnya tidak menampilkan nomor lengkap
    $this->get(route('member.password-request.create'))->assertDontSee('081234560001');
});

test('mengajukan berkali-kali tidak membuat antrean dobel', function () {
    ($this->ajukan)();
    ($this->ajukan)(['note' => 'nomor WA saya sudah ganti']);

    expect(PasswordResetRequest::count())->toBe(1);
    expect(PasswordResetRequest::sole()->note)->toBe('nomor WA saya sudah ganti');
});

test('anggota nonaktif atau kode asal-asalan ditolak', function () {
    ($this->ajukan)(['member_code' => '9999 Z'])->assertSessionHasErrors('member_code');

    $this->anggota->update(['is_active' => false]);
    ($this->ajukan)()->assertSessionHasErrors('member_code');

    expect(PasswordResetRequest::count())->toBe(0);
});

test('halaman masuk punya tautan Lupa password', function () {
    foreach ([route('masuk'), route('member.login')] as $url) {
        $this->get($url)->assertSee(route('member.password-request.create'), false);
    }
});

test('semua pengurus melihat antrean dan pengingatnya di dasbor', function () {
    ($this->ajukan)();

    $this->actingAs($this->staf, 'web')
        ->get(route('admin.dashboard'))
        ->assertSee('lupa password dan menunggu dibuatkan password baru');

    $this->actingAs($this->staf, 'web')
        ->get(route('admin.password-requests.index'))
        ->assertOk()
        ->assertSee('Siti Nurhaliza')
        ->assertSee('081234560001');
});

test('membuatkan password baru: langsung berlaku, tercatat penangannya, dan siap dikirim lewat WhatsApp', function () {
    ($this->ajukan)();
    $permintaan = PasswordResetRequest::sole();

    $respons = $this->actingAs($this->staf, 'web')
        ->patch(route('admin.password-requests.reset', $permintaan))
        ->assertRedirect(route('admin.password-requests.index'));

    $baru = $respons->getSession()->get('passwordBaru');
    expect($baru['password'])->toMatch('/^[a-z2-9]{8}$/');
    expect(Hash::check($baru['password'], $this->anggota->fresh()->password))->toBeTrue();
    expect(Hash::check('password-lama', $this->anggota->fresh()->password))->toBeFalse();

    // Tautan WhatsApp ke nomor terdaftar, berisi password barunya
    expect($baru['tautan'])->toStartWith('https://wa.me/6281234560001?text=');
    expect($baru['tautan'])->toContain(rawurlencode('Password baru kamu untuk Mitra Dharma Praja Mart: '.$baru['password']));

    $permintaan->refresh();
    expect($permintaan->status)->toBe(PasswordResetStatus::Selesai);
    expect($permintaan->handled_by)->toBe($this->staf->id);

    // Anggota bisa masuk dengan password barunya
    $this->post(route('member.login.store'), ['member_code' => '0001 A', 'password' => $baru['password']])
        ->assertRedirect();
    $this->assertAuthenticatedAs($this->anggota->fresh(), 'member');
});

test('kalau dua pengurus menekan hampir bersamaan, yang kedua ditolak dan passwordnya tidak berubah lagi', function () {
    ($this->ajukan)();
    $permintaan = PasswordResetRequest::sole();

    $pertama = $this->actingAs($this->staf, 'web')
        ->patch(route('admin.password-requests.reset', $permintaan))
        ->getSession()->get('passwordBaru')['password'];

    $this->actingAs($this->stafLain, 'web')
        ->patch(route('admin.password-requests.reset', $permintaan))
        ->assertStatus(422)
        ->assertSee('Permintaan ini sudah ditangani Staf Satu');

    expect(Hash::check($pertama, $this->anggota->fresh()->password))->toBeTrue();
});

test('mengabaikan permintaan tidak mengubah password', function () {
    ($this->ajukan)();
    $permintaan = PasswordResetRequest::sole();

    $this->actingAs($this->staf, 'web')
        ->patch(route('admin.password-requests.dismiss', $permintaan))
        ->assertRedirect(route('admin.password-requests.index'));

    expect($permintaan->fresh()->status)->toBe(PasswordResetStatus::Diabaikan);
    expect(Hash::check('password-lama', $this->anggota->fresh()->password))->toBeTrue();
});

test('mengganti password lewat Edit Anggota ikut menutup permintaan yang menunggu', function () {
    ($this->ajukan)();

    $this->actingAs($this->staf, 'web')
        ->put(route('admin.members.update', $this->anggota), [
            'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza', 'whatsapp_number' => '081234560001',
            'password' => 'password-baru-1', 'is_active' => '1',
        ])
        ->assertRedirect(route('admin.members.index'));

    $permintaan = PasswordResetRequest::sole();
    expect($permintaan->status)->toBe(PasswordResetStatus::Selesai);
    expect($permintaan->handled_by)->toBe($this->staf->id);
});

test('anggota yang sudah masuk tidak diarahkan ke halaman lupa password', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.password-request.create'))
        ->assertRedirect(route('member.dashboard'));
});
