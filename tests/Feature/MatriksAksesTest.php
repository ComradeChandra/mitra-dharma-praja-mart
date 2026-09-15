<?php

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Matriks akses: SETIAP rute × SETIAP peran
|--------------------------------------------------------------------------
| Daftar rutenya diambil dari tabel rute aplikasi (Route::getRoutes()),
| bukan diketik manual, jadi rute baru otomatis ikut teruji. Siapa yang
| boleh masuk diturunkan dari middleware rutenya:
|
|   auth:web           -> cuma akun pengurus; kalau ada can:admin-utama,
|                         cuma Admin Utama (akun Pengurus biasa ditolak)
|   auth:member        -> cuma anggota; rute {order} cuma pemiliknya
|   non-member.session -> cuma sesi non-anggota; rute {order} cuma sesi yang
|                         mengirim pesanannya (bukan rekan sekantor)
|
| Yang tidak berhak harus ditolak (403/404) atau dialihkan ke halaman login,
| dan TIDAK boleh mendapat isi halaman (200).
*/

beforeEach(function () {
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);
});

test('setiap rute cuma bisa dibuka peran yang berhak', function () {
    $periode = OrderPeriod::yangSedangDibuka();
    $admin = User::first();
    $anggotaA = Member::whereHas('orders', fn ($q) => $q->whereNotNull('total_amount'))->first();
    $anggotaB = Member::aktif()->whereKeyNot($anggotaA->id)->first();
    $pesananA = $anggotaA->orders()->whereNotNull('total_amount')->first();
    $pesananA->update(['payment_proof_path' => Storage::disk('local')->put('payment-proofs', new File(__FILE__))]);

    $opd = OpdDepartment::first();
    $opdLain = OpdDepartment::whereKeyNot($opd->id)->first();
    $pesananNon = Order::create(['order_period_id' => $periode->id, 'user_type' => UserType::NonMember, 'non_member_name' => 'Teti', 'opd_id' => $opd->id, 'whatsapp_number' => '62811', 'status' => OrderStatus::Verified, 'total_amount' => 70000, 'payment_proof_path' => $pesananA->payment_proof_path]);

    $staf = User::factory()->pengurus()->create();

    $peran = [
        'tamu' => fn () => null,
        'admin' => fn () => $this->actingAs($admin, 'web'),
        'pengurus' => fn () => $this->actingAs($staf, 'web'),
        'anggota-pemilik' => fn () => $this->actingAs($anggotaA, 'member'),
        'anggota-lain' => fn () => $this->actingAs($anggotaB, 'member'),
        'non-pemilik-sesi' => fn () => $this->withSession(['non_member_opd_id' => $opd->id, 'non_member_order_ids' => [$pesananNon->id]]),
        'non-rekan-sekantor' => fn () => $this->withSession(['non_member_opd_id' => $opd->id, 'non_member_order_ids' => []]),
        'non-opd-lain' => fn () => $this->withSession(['non_member_opd_id' => $opdLain->id, 'non_member_order_ids' => [$pesananNon->id]]),
    ];

    $pelanggaran = [];

    foreach (Route::getRoutes() as $rute) {
        if (! in_array('GET', $rute->methods(), true) || ! $rute->getName() || str_starts_with($rute->uri(), '_') || in_array($rute->uri(), ['up', 'storage/{path}'], true)) {
            continue;
        }

        $middleware = $rute->gatherMiddleware();
        $wilayah = match (true) {
            in_array('auth:web', $middleware, true) => 'admin',
            in_array('auth:member', $middleware, true) => 'anggota',
            in_array('non-member.session', $middleware, true) => 'non',
            default => 'publik',
        };

        if ($wilayah === 'publik') {
            continue;
        }

        $param = collect($rute->parameterNames())->mapWithKeys(fn ($p) => [$p => match ($p) {
            'order' => $wilayah === 'non' ? $pesananNon->id : $pesananA->id,
            'product' => Product::first()->id,
            'member' => $anggotaA->id,
            'opd_department' => $opd->id,
            'order_period', 'orderPeriod' => $periode->id,
            'productRequest' => ProductRequest::first()->id,
            'user' => $admin->id,
            default => throw new RuntimeException("parameter tak dikenal: $p di {$rute->uri()}"),
        }])->all();
        $url = route($rute->getName(), $param);
        $pakaiPesanan = in_array('order', $rute->parameterNames(), true);
        $khususAdminUtama = in_array('can:admin-utama', $middleware, true);

        foreach ($peran as $namaPeran => $masuk) {
            $this->flushSession();
            app('auth')->forgetGuards();
            $masuk();

            $boleh = match ($wilayah) {
                'admin' => $namaPeran === 'admin' || ($namaPeran === 'pengurus' && ! $khususAdminUtama),
                'anggota' => $pakaiPesanan ? $namaPeran === 'anggota-pemilik' : str_starts_with($namaPeran, 'anggota'),
                'non' => $pakaiPesanan ? $namaPeran === 'non-pemilik-sesi' : str_starts_with($namaPeran, 'non-'),
            };

            $status = $this->get($url)->getStatusCode();

            if ($boleh && $status >= 400) {
                $pelanggaran[] = "TERTOLAK padahal berhak: $namaPeran GET {$rute->uri()} -> $status";
            }
            if (! $boleh && $status === 200) {
                $pelanggaran[] = "BOCOR: $namaPeran GET {$rute->uri()} -> 200";
            }
            if ($status >= 500) {
                $pelanggaran[] = "ERROR: $namaPeran GET {$rute->uri()} -> $status";
            }
        }
    }

    expect($pelanggaran)->toBe([]);
});

test('aksi tulis milik peran lain tidak mengubah apa pun', function () {
    $periode = OrderPeriod::yangSedangDibuka();
    $anggotaA = Member::whereHas('orders', fn ($q) => $q->whereNotNull('total_amount'))->first();
    $anggotaB = Member::aktif()->whereKeyNot($anggotaA->id)->first();
    $pesananA = $anggotaA->orders()->whereNotNull('total_amount')->first();
    $opd = OpdDepartment::first();
    $pesananNon = Order::create(['order_period_id' => $periode->id, 'user_type' => UserType::NonMember, 'non_member_name' => 'Teti', 'opd_id' => $opd->id, 'whatsapp_number' => '62811', 'status' => OrderStatus::Verified, 'total_amount' => 70000]);
    $produk = Product::first();
    $jumlahProduk = Product::count();
    // Dicatat sebelum, dibandingkan sesudah: sebagian pesanan contoh memang sudah lunas.
    $sebelumA = $pesananA->payment_status;
    $sebelumStatusA = $pesananA->status;

    // Anggota B mencoba menyatakan bayar pesanan anggota A
    $this->actingAs($anggotaB, 'member')->post(route('member.orders.declare-paid', $pesananA))->assertForbidden();
    // Rekan sekantor mencoba menyatakan bayar pesanan non-anggota lain
    app('auth')->forgetGuards();
    $this->flushSession();
    $this->withSession(['non_member_opd_id' => $opd->id])->post(route('non-member.orders.declare-paid', $pesananNon))->assertForbidden();
    // Anggota mencoba aksi admin
    $this->flushSession();
    $this->actingAs($anggotaA, 'member')->delete(route('admin.products.destroy', $produk));
    $this->actingAs($anggotaA, 'member')->patch(route('admin.orders.confirm-payment', $pesananA));
    $this->actingAs($anggotaA, 'member')->patch(route('admin.orders.verify', $pesananA), ['prices' => []]);

    expect($pesananA->fresh()->payment_status)->toBe($sebelumA);
    expect($pesananA->fresh()->status)->toBe($sebelumStatusA);
    expect($pesananNon->fresh()->payment_status->value)->toBe('unpaid');
    expect(Product::count())->toBe($jumlahProduk);
});
