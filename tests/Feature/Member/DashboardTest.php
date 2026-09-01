<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;

beforeEach(function () {
    $this->member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);
});

test('beranda anggota nampilin belanja tahun ini & estimasi SHU kalau sudah pernah belanja', function () {
    $periode = OrderPeriod::create([
        'label' => 'Pemesanan Agustus 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    Order::create([
        'order_period_id' => $periode->id,
        'user_type' => UserType::Member,
        'member_id' => $this->member->id,
        'whatsapp_number' => $this->member->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 1000000,
    ]);

    $this->actingAs($this->member, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('Rp1.000.000')
        // Estimasi SHU 0,5%-1% dari Rp1.000.000 = Rp5.000 - Rp10.000
        ->assertSee('Rp5.000')
        ->assertSee('Rp10.000');
});

test('beranda anggota nampilin pesan wajar kalau belum pernah belanja', function () {
    $this->actingAs($this->member, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('Belum ada belanja terverifikasi tahun ini');
});
