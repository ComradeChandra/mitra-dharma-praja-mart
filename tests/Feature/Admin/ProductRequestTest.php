<?php

use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\ProductRequest;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

function buatPermintaanUji(string $status = 'pending'): ProductRequest
{
    $member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    return ProductRequest::create([
        'user_type' => UserType::Member,
        'member_id' => $member->id,
        'requester_label' => 'Anggota Uji',
        'product_name' => 'Produk Diminta',
        'status' => $status,
    ]);
}

test('admin bisa lihat daftar permintaan produk', function () {
    buatPermintaanUji();

    $this->actingAs($this->admin)
        ->get(route('admin.product-requests.index'))
        ->assertOk()
        ->assertSee('Produk Diminta');
});

test('admin bisa menyetujui permintaan produk', function () {
    $permintaan = buatPermintaanUji();

    $this->actingAs($this->admin)
        ->patch(route('admin.product-requests.approve', $permintaan))
        ->assertRedirect(route('admin.product-requests.index'));

    expect($permintaan->fresh()->status)->toBe(ProductRequestStatus::Approved);
});

test('admin bisa menolak permintaan produk', function () {
    $permintaan = buatPermintaanUji();

    $this->actingAs($this->admin)
        ->patch(route('admin.product-requests.reject', $permintaan))
        ->assertRedirect(route('admin.product-requests.index'));

    expect($permintaan->fresh()->status)->toBe(ProductRequestStatus::Rejected);
});

test('tamu yang belum login ditolak dari halaman permintaan produk admin', function () {
    $this->get(route('admin.product-requests.index'))
        ->assertRedirect(route('login'));
});
