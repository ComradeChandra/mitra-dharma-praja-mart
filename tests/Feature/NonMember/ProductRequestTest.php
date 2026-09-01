<?php

use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Models\OpdDepartment;
use App\Models\ProductRequest;
use App\Models\User;

/**
 * Usulan produk dari non-anggota (Modul 8, sisi non-anggota).
 *
 * Beda dari versi anggota: pemohon menuliskan namanya sendiri, dan tidak ada
 * halaman riwayat usulan (tidak ada identitas personal yang stabil buat
 * difilter — kode akses OPD dipakai bersama sekantor).
 */
beforeEach(function () {
    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
});

test('non-anggota bisa membuka form usulan produk', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('non-member.product-requests.create'))
        ->assertOk()
        ->assertSee('Usulkan Produk')
        ->assertSee('Dinas Pendidikan');
});

test('tamu ditolak dari form usulan produk non-anggota', function () {
    $this->get(route('non-member.product-requests.create'))
        ->assertRedirect(route('non-member.login'));
});

test('usulan tersimpan dengan nama pemohon & OPD-nya', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.product-requests.store'), [
            'requester_name' => 'Teti Suryani',
            'product_name' => 'Sabun cuci piring Sunlight 750ml',
        ])
        ->assertRedirect(route('non-member.product-requests.create'));

    $usulan = ProductRequest::first();

    expect($usulan->user_type)->toBe(UserType::NonMember);
    expect($usulan->member_id)->toBeNull();
    expect($usulan->product_name)->toBe('Sabun cuci piring Sunlight 750ml');
    // Nama + OPD digabung supaya pengurus tahu usulan ini dari siapa & dari mana.
    expect($usulan->requester_label)->toBe('Teti Suryani (Dinas Pendidikan)');
    expect($usulan->status)->toBe(ProductRequestStatus::Pending);
});

test('nama pemohon & nama produk wajib diisi', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.product-requests.store'), [])
        ->assertSessionHasErrors(['requester_name', 'product_name']);

    $this->assertDatabaseCount('product_requests', 0);
});

test('usulan non-anggota muncul di daftar tinjauan admin', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.product-requests.store'), [
            'requester_name' => 'Teti Suryani',
            'product_name' => 'Beras Pandan Wangi 5kg',
        ]);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.product-requests.index'))
        ->assertOk()
        ->assertSee('Beras Pandan Wangi 5kg')
        ->assertSee('Teti Suryani (Dinas Pendidikan)');
});

test('admin bisa menyetujui usulan dari non-anggota', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.product-requests.store'), [
            'requester_name' => 'Teti Suryani',
            'product_name' => 'Minyak goreng 2L',
        ]);

    $usulan = ProductRequest::first();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.product-requests.approve', $usulan));

    expect($usulan->fresh()->status)->toBe(ProductRequestStatus::Approved);
});
