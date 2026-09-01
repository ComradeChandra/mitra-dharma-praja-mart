<?php

use App\Models\OpdDepartment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Pola sama persis dengan MemberPasswordTest — access_code OPD wajib diisi
// pas tambah baru, opsional pas edit (kosongkan = tidak ganti).

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('admin wajib isi kode akses saat tambah OPD baru', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.opd-departments.store'), [
        'name' => 'OPD Baru',
        // access_code sengaja tidak diisi
    ]);

    $response->assertSessionHasErrors('access_code');
    expect(OpdDepartment::where('name', 'OPD Baru')->exists())->toBeFalse();
});

test('admin bisa tambah OPD baru lengkap dengan kode akses', function () {
    $this->actingAs($this->admin)->post(route('admin.opd-departments.store'), [
        'name' => 'OPD Baru',
        'access_code' => 'kode-awal',
    ])->assertRedirect(route('admin.opd-departments.index'));

    $opd = OpdDepartment::where('name', 'OPD Baru')->firstOrFail();
    expect(Hash::check('kode-awal', $opd->access_code))->toBeTrue();
});

test('edit OPD dengan field kode akses dikosongkan TIDAK mengubah kode lama', function () {
    $opd = OpdDepartment::create(['name' => 'OPD Lama', 'access_code' => 'kode-lama']);
    $hashLama = $opd->access_code;

    $this->actingAs($this->admin)->put(route('admin.opd-departments.update', $opd), [
        'name' => 'OPD Lama (diedit)',
        // access_code sengaja dikosongkan
    ])->assertRedirect(route('admin.opd-departments.index'));

    expect($opd->fresh()->access_code)->toBe($hashLama);
});

test('edit OPD dengan field kode akses diisi MENGUBAH kode lama', function () {
    $opd = OpdDepartment::create(['name' => 'OPD Lama', 'access_code' => 'kode-lama']);

    $this->actingAs($this->admin)->put(route('admin.opd-departments.update', $opd), [
        'name' => 'OPD Lama',
        'access_code' => 'kode-baru',
    ])->assertRedirect(route('admin.opd-departments.index'));

    expect(Hash::check('kode-baru', $opd->fresh()->access_code))->toBeTrue();
});
