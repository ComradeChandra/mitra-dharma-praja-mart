<?php

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderPeriod;
use Database\Seeders\DatabaseSeeder;

/*
| Data contoh dipakai waktu presentasi, jadi kejanggalannya kelihatan orang.
| Dua yang pernah lolos: semua pesanan bertanggal hari seeder dijalankan
| (pesanan periode Juli tertulis "dikirim September"), dan semua pesanan
| belum dibayar sehingga tab "Lunas" kosong.
*/

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('tanggal kirim setiap pesanan contoh jatuh di dalam rentang periodenya', function () {
    foreach (Order::with('orderPeriod')->get() as $order) {
        $periode = $order->orderPeriod;

        expect($order->created_at->between(
            $periode->start_date->copy()->startOfDay(),
            $periode->end_date->copy()->endOfDay(),
        ))->toBeTrue("Pesanan #{$order->id} bertanggal {$order->created_at} di luar {$periode->label}");

        expect($order->created_at->isFuture())->toBeFalse();
    }
});

test('periode lampau sebagian besar lunas tapi tetap ada contoh tunggakan', function () {
    $lampau = Order::whereIn('order_period_id', OrderPeriod::where('label', '!=', 'Pemesanan September 2026')->pluck('id'));

    expect((clone $lampau)->where('payment_status', PaymentStatus::Paid)->count())->toBeGreaterThan(0);
    expect((clone $lampau)->where('payment_status', PaymentStatus::Unpaid)->count())->toBeGreaterThan(0);

    // Lunas harus punya jejak waktu yang urut dan tidak di masa depan.
    foreach ((clone $lampau)->where('payment_status', PaymentStatus::Paid)->get() as $order) {
        expect($order->paid_declared_at->gte($order->created_at))->toBeTrue();
        expect($order->payment_confirmed_at->gte($order->paid_declared_at))->toBeTrue();
        expect($order->payment_confirmed_at->isFuture())->toBeFalse();
    }
});

test('periode berjalan belum ada yang dibayar supaya alur bayar bisa didemokan dari awal', function () {
    $berjalan = OrderPeriod::yangSedangDibuka();

    expect($berjalan)->not->toBeNull();
    expect(Order::where('order_period_id', $berjalan->id)->where('payment_status', '!=', PaymentStatus::Unpaid)->count())->toBe(0);
});
