<?php

use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\ProductRequest;

beforeEach(function () {
    $this->member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);
});

test('anggota bisa lihat halaman ajukan permintaan produk', function () {
    $this->actingAs($this->member, 'member')
        ->get(route('member.product-requests.create'))
        ->assertOk();
});

test('anggota bisa mengajukan permintaan produk baru', function () {
    $response = $this->actingAs($this->member, 'member')
        ->post(route('member.product-requests.store'), [
            'product_name' => 'Kecap Manis Merek Y',
        ]);

    $response->assertRedirect(route('member.product-requests.index'));

    $this->assertDatabaseHas('product_requests', [
        'product_name' => 'Kecap Manis Merek Y',
        'member_id' => $this->member->id,
        'requester_label' => 'Anggota Uji',
        'status' => 'pending',
    ]);
});

test('nama produk wajib diisi', function () {
    $this->actingAs($this->member, 'member')
        ->post(route('member.product-requests.store'), ['product_name' => ''])
        ->assertSessionHasErrors('product_name');
});

test('anggota cuma bisa lihat permintaan miliknya sendiri', function () {
    $anggotaLain = Member::create([
        'member_code' => '0020 A',
        'full_name' => 'Anggota Lain',
        'whatsapp_number' => '628987654321',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    ProductRequest::create([
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'requester_label' => 'Anggota Uji',
        'product_name' => 'Punya Saya',
        'status' => ProductRequestStatus::Pending,
    ]);
    ProductRequest::create([
        'user_type' => UserType::Member,
        'member_id' => $anggotaLain->id,
        'requester_label' => 'Anggota Lain',
        'product_name' => 'Punya Orang Lain',
        'status' => ProductRequestStatus::Pending,
    ]);

    $response = $this->actingAs($this->member, 'member')->get(route('member.product-requests.index'));

    $response->assertSee('Punya Saya');
    $response->assertDontSee('Punya Orang Lain');
});

test('tamu yang belum login ditolak dari halaman permintaan produk', function () {
    $this->get(route('member.product-requests.index'))
        ->assertRedirect(route('member.login'));
});
