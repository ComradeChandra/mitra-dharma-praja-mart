<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\ProductRequest;
use Illuminate\Database\Seeder;

/**
 * Periode + pesanan + usulan produk CONTOH.
 *
 * Kenapa perlu: tanpa pesanan sama sekali, dashboard admin isinya grafik
 * kosong dan semua halaman rekap bertuliskan "belum ada yang bisa direkap" —
 * fitur yang paling penting justru tidak kelihatan saat didemokan.
 *
 * Semua data di sini fiktif, termasuk angka rupiahnya. Hapus lewat halaman
 * admin sebelum aplikasi dipakai sungguhan.
 *
 * Dipisah dari DemoDataSeeder (yang mengurus anggota & produk) supaya bisa
 * dijalankan/dilewati sendiri: `php artisan db:seed --class=DemoOrderSeeder`.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan pesanan contoh lama dulu, kalau tidak, menjalankan seeder
        // ini dua kali bikin pesanan menumpuk dan angka rekapnya jadi dobel.
        // Aman: yang dihapus cuma pesanan di periode contoh yang dibuat di sini.
        $this->bersihkanPesananContoh();

        $periodeSelesai = $this->buatPeriode('Pemesanan Juli 2026', -60, -45, OrderPeriodStatus::Closed);
        $periodeLalu = $this->buatPeriode('Pemesanan Agustus 2026', -30, -15, OrderPeriodStatus::Closed);
        $periodeAktif = $this->buatPeriode('Pemesanan September 2026', -2, 12, OrderPeriodStatus::Open);

        // Dua periode lampau diisi penuh & sudah final, biar grafik keuntungan
        // per periode punya batang yang bisa dibandingkan.
        $this->isiPeriode($periodeSelesai, jumlahAnggota: 12, jumlahNonAnggota: 4, final: true);
        $this->isiPeriode($periodeLalu, jumlahAnggota: 15, jumlahNonAnggota: 6, final: true);

        // Periode berjalan sengaja BELUM semua anggota memesan, supaya rekap
        // "sudah / belum belanja" ada isinya di kedua kolom, dan ada pesanan
        // berstatus menunggu verifikasi buat didemokan alurnya.
        $this->isiPeriode($periodeAktif, jumlahAnggota: 8, jumlahNonAnggota: 3, final: false);

        $this->buatUsulanProduk();
    }

    private function bersihkanPesananContoh(): void
    {
        $label = ['Pemesanan Juli 2026', 'Pemesanan Agustus 2026', 'Pemesanan September 2026'];

        $periodeIds = OrderPeriod::whereIn('label', $label)->pluck('id');

        foreach (Order::whereIn('order_period_id', $periodeIds)->get() as $order) {
            $order->orderItems()->delete();
            $order->delete();
        }
    }

    private function buatPeriode(string $label, int $mulaiHari, int $selesaiHari, OrderPeriodStatus $status): OrderPeriod
    {
        return OrderPeriod::updateOrCreate(
            ['label' => $label],
            [
                'start_date' => now()->addDays($mulaiHari),
                'end_date' => now()->addDays($selesaiHari),
                'status' => $status,
            ]
        );
    }

    /**
     * Isi satu periode dengan pesanan anggota & non-anggota.
     *
     * @param  bool  $final  true = harga sudah dikunci (verified/invoiced,
     *                       ikut terhitung di rekap uang); false = sebagian
     *                       masih menunggu verifikasi admin.
     */
    private function isiPeriode(OrderPeriod $periode, int $jumlahAnggota, int $jumlahNonAnggota, bool $final): void
    {
        $produkBerharga = Product::where('is_active', true)->whereNotNull('sell_price')->get();
        $produkFluktuatif = Product::where('is_active', true)->whereNull('sell_price')->get();
        $anggota = Member::where('is_active', true)->take($jumlahAnggota)->get();
        $opd = OpdDepartment::take($jumlahNonAnggota)->get();

        if ($produkBerharga->isEmpty() || $anggota->isEmpty()) {
            return;
        }

        foreach ($anggota as $i => $member) {
            // Sebagian kecil pesanan sengaja memuat produk fluktuatif supaya
            // statusnya "menunggu verifikasi", memperlihatkan alur harga
            // telur/sayur yang dikunci admin.
            $pakaiFluktuatif = ! $final && $i % 3 === 2 && $produkFluktuatif->isNotEmpty();

            $this->buatPesanan(
                periode: $periode,
                pemesan: $member,
                opd: null,
                produkBerharga: $produkBerharga,
                produkFluktuatif: $pakaiFluktuatif ? $produkFluktuatif : null,
                urutan: $i,
            );
        }

        foreach ($opd as $i => $instansi) {
            $this->buatPesanan(
                periode: $periode,
                pemesan: null,
                opd: $instansi,
                produkBerharga: $produkBerharga,
                produkFluktuatif: null,
                urutan: $i,
            );
        }
    }

    private function buatPesanan(
        OrderPeriod $periode,
        ?Member $pemesan,
        ?OpdDepartment $opd,
        $produkBerharga,
        $produkFluktuatif,
        int $urutan,
    ): void {
        $namaStaf = ['Rina Oktaviani', 'Firman Maulana', 'Dini Anggraeni', 'Yoga Pratama', 'Mega Puspita', 'Andri Nugraha'];

        // Diantar / ambil sendiri diselang-seling biar dua-duanya kelihatan
        // di daftar pesanan admin.
        $diantar = $urutan % 2 === 0;
        $alamat = $pemesan?->address ?? 'Kantor '.($opd?->name ?? 'OPD');

        $order = Order::create([
            'order_period_id' => $periode->id,
            'user_type' => $pemesan ? UserType::Member : UserType::NonMember,
            'member_id' => $pemesan?->id,
            'non_member_name' => $pemesan ? null : $namaStaf[$urutan % count($namaStaf)],
            'opd_id' => $opd?->id,
            'whatsapp_number' => $pemesan?->whatsapp_number ?? '62812900'.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT),
            'delivery_method' => $diantar ? DeliveryMethod::Antar : DeliveryMethod::Ambil,
            'delivery_address' => $diantar ? $alamat : null,
            'status' => OrderStatus::Pending,
        ]);

        // 2–4 produk per pesanan, dipilih berputar biar sebarannya merata
        // (tidak semua orang memesan barang yang sama).
        $jumlahJenis = 2 + ($urutan % 3);
        $dipilih = $produkBerharga->slice($urutan % max($produkBerharga->count() - $jumlahJenis, 1), $jumlahJenis);

        foreach ($dipilih as $produk) {
            $order->orderItems()->create([
                'product_id' => $produk->id,
                'quantity' => 1 + ($urutan % 4),
                'price_at_order' => $produk->sell_price,
            ]);
        }

        // Produk fluktuatif ditambahkan TANPA harga, inilah yang bikin
        // pesanannya bertahan di status "menunggu verifikasi".
        if ($produkFluktuatif) {
            $order->orderItems()->create([
                'product_id' => $produkFluktuatif->first()->id,
                'quantity' => 2,
                'price_at_order' => null,
            ]);

            return; // biarkan Pending, totalnya belum final
        }

        $total = $order->orderItems->sum(fn ($item) => $item->quantity * $item->price_at_order);

        $order->update([
            'total_amount' => $total,
            // Sebagian ditandai invoice sudah terkirim, sebagian baru
            // terverifikasi, biar filter status di daftar pesanan ada isinya.
            'status' => $urutan % 3 === 0 ? OrderStatus::Invoiced : OrderStatus::Verified,
        ]);
    }

    /**
     * Usulan produk contoh, supaya panel "Permintaan produk menunggu ditinjau"
     * di dashboard admin ada isinya.
     */
    private function buatUsulanProduk(): void
    {
        $anggota = Member::where('is_active', true)->take(3)->get();
        $opd = OpdDepartment::first();

        $usulan = [
            ['Sabun Cuci Piring Sunlight 1,6L', ProductRequestStatus::Pending],
            ['Popok Bayi Sekali Pakai M isi 40', ProductRequestStatus::Pending],
            ['Kopi Sachet 1 Renceng', ProductRequestStatus::Approved],
        ];

        foreach ($usulan as $i => [$nama, $status]) {
            $member = $anggota->get($i);

            if (! $member) {
                continue;
            }

            ProductRequest::updateOrCreate(
                ['product_name' => $nama],
                [
                    'user_type' => UserType::Member,
                    'member_id' => $member->id,
                    'requester_label' => $member->full_name,
                    'status' => $status,
                ]
            );
        }

        // Satu usulan dari non-anggota, biar terlihat keduanya bisa mengusulkan.
        if ($opd) {
            ProductRequest::updateOrCreate(
                ['product_name' => 'Minyak Kayu Putih 60ml'],
                [
                    'user_type' => UserType::NonMember,
                    'member_id' => null,
                    'requester_label' => 'Firman Maulana ('.$opd->name.')',
                    'status' => ProductRequestStatus::Pending,
                ]
            );
        }
    }
}
