<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use Illuminate\Support\Collection;

/**
 * Rekap pemesanan buat dashboard admin, rekap per periode, dan profil belanja
 * anggota (Modul 6 & 9 di CLAUDE.md).
 *
 * Semua perhitungan di sini cuma memakai pesanan berstatus verified atau
 * invoiced. Pesanan pending belum dihitung karena harganya belum dikunci.
 *
 * Cakupan pemesannya beda-beda per jenis rekap:
 * - Rekap uang & kebutuhan produk ikut menghitung anggota dan non-anggota.
 * - topMembers() dan memberYearlySpending() khusus anggota, karena SHU cuma
 *   hak anggota.
 * - opdDistribution() khusus non-anggota, karena OPD cuma relevan di sana.
 */
class RecapService
{
    /**
     * Status yang harganya sudah final dan boleh masuk hitungan rekap.
     *
     * @return array<int, OrderStatus>
     */
    private function statusFinal(): array
    {
        return [OrderStatus::Verified, OrderStatus::Invoiced];
    }

    /**
     * Query dasar semua rekap: pesanan final beserta item & produknya.
     *
     * @param  OrderPeriod|null  $period  batasi ke satu periode saja
     * @param  UserType|null  $userType  batasi ke anggota atau non-anggota saja
     */
    private function ordersFinal(?OrderPeriod $period = null, ?UserType $userType = null): Collection
    {
        return Order::with('orderItems.product', 'member', 'opdDepartment')
            ->whereIn('status', $this->statusFinal())
            ->when($userType, fn ($query) => $query->where('user_type', $userType))
            ->when($period, fn ($query) => $query->where('order_period_id', $period->id))
            ->get();
    }

    /**
     * Pendapatan kotor, modal, dan laba bersih per periode. Dipakai grafik
     * batang + garis di dashboard.
     *
     * Periode tanpa pesanan final dilewati supaya grafiknya tidak penuh
     * batang kosong.
     *
     * @return array{labels: array<int, string>, gross: array<int, float>, modal: array<int, float>, net: array<int, float>}
     */
    public function revenueByPeriod(): array
    {
        $orders = $this->ordersFinal()->groupBy('order_period_id');

        $periods = OrderPeriod::whereIn('id', $orders->keys())
            ->orderBy('start_date')
            ->get();

        $labels = [];
        $gross = [];
        $modal = [];
        $net = [];

        foreach ($periods as $period) {
            $itemsInPeriod = $orders->get($period->id, collect())
                ->flatMap(fn (Order $order) => $order->orderItems);

            $grossPeriod = (float) $itemsInPeriod->sum(fn ($item) => $item->quantity * $item->price_at_order);
            $modalPeriod = (float) $itemsInPeriod->sum(fn ($item) => $item->quantity * $item->product->buy_price);

            $labels[] = $period->label;
            $gross[] = round($grossPeriod, 2);
            $modal[] = round($modalPeriod, 2);
            $net[] = round($grossPeriod - $modalPeriod, 2);
        }

        return compact('labels', 'gross', 'modal', 'net');
    }

    /**
     * Anggota yang paling sering dan paling banyak belanja.
     *
     * Khusus anggota. Non-anggota tidak punya identitas personal yang tetap,
     * jadi direkap per instansi lewat opdDistribution().
     *
     * @return array{labels: array<int, string>, orderCounts: array<int, int>, totalValues: array<int, float>}
     */
    public function topMembers(int $limit = 5): array
    {
        $perMember = $this->ordersFinal(userType: UserType::Member)
            ->whereNotNull('member_id')
            ->groupBy('member_id')
            ->map(fn (Collection $orders) => [
                'nama' => $orders->first()->member?->full_name ?? 'Anggota Terhapus',
                'jumlahPesanan' => $orders->count(),
                'totalBelanja' => (float) $orders->sum('total_amount'),
            ])
            // Urutan utama jumlah pesanan, nilai belanja cuma pemecah seri.
            ->sortBy([
                ['jumlahPesanan', 'desc'],
                ['totalBelanja', 'desc'],
            ])
            ->take($limit)
            ->values();

        return [
            'labels' => $perMember->pluck('nama')->all(),
            'orderCounts' => $perMember->pluck('jumlahPesanan')->all(),
            'totalValues' => $perMember->pluck('totalBelanja')->all(),
        ];
    }

    /**
     * Instansi yang paling banyak memesan lewat stafnya. Ini bagian
     * "distribusi per OPD" dari Modul 6.
     *
     * @return array{labels: array<int, string>, orderCounts: array<int, int>, totalValues: array<int, float>}
     */
    public function opdDistribution(int $limit = 5): array
    {
        $perOpd = $this->ordersFinal(userType: UserType::NonMember)
            ->whereNotNull('opd_id')
            ->groupBy('opd_id')
            ->map(fn (Collection $orders) => [
                'nama' => $orders->first()->opdDepartment?->name ?? 'OPD Terhapus',
                'jumlahPesanan' => $orders->count(),
                'totalBelanja' => (float) $orders->sum('total_amount'),
            ])
            ->sortBy([
                ['jumlahPesanan', 'desc'],
                ['totalBelanja', 'desc'],
            ])
            ->take($limit)
            ->values();

        return [
            'labels' => $perOpd->pluck('nama')->all(),
            'orderCounts' => $perOpd->pluck('jumlahPesanan')->all(),
            'totalValues' => $perOpd->pluck('totalBelanja')->all(),
        ];
    }

    /**
     * Kelompokkan item pesanan per produk lalu jumlahkan kuantitasnya.
     *
     * Dipakai bareng topProducts() dan productsForPeriod(). Keduanya beda
     * penyajian tapi cara mengumpulkan datanya sama. Tidak dibatasi tipe
     * pemesan, karena kebutuhan belanja grosir dihitung dari semua pesanan.
     *
     * @return Collection<int, array{nama: string, kategori: string, jumlah: int, jumlahTertulis: string}>
     */
    private function productQuantities(?OrderPeriod $period = null): Collection
    {
        return $this->ordersFinal($period)
            ->flatMap(fn (Order $order) => $order->orderItems)
            ->groupBy('product_id')
            ->map(fn (Collection $items) => [
                'nama' => $items->first()->product->name,
                'kategori' => $items->first()->product->category,
                'jumlah' => (int) $items->sum('quantity'),
                // Angka yang sudah berikut satuannya, mis. "14 karung".
                // Dirakit di Product::formatJumlah() supaya aturan penulisannya
                // cuma ada di satu tempat. 'jumlah' di atas tetap integer murni
                // karena dipakai buat urut dan hitung.
                'jumlahTertulis' => $items->first()->product->formatJumlah((int) $items->sum('quantity')),
            ]);
    }

    /**
     * Produk terlaris sepanjang masa, diurutkan dari yang paling banyak
     * terjual. Ditampilkan sebagai tabel, bukan grafik.
     *
     * @return Collection<int, array{nama: string, kategori: string, jumlahTerjual: int, jumlahTerjualTertulis: string}>
     */
    public function topProducts(int $limit = 10): Collection
    {
        return $this->productQuantities()
            ->sortByDesc('jumlah')
            ->take($limit)
            ->values()
            ->map(fn (array $produk) => [
                'nama' => $produk['nama'],
                'kategori' => $produk['kategori'],
                'jumlahTerjual' => $produk['jumlah'],
                'jumlahTerjualTertulis' => $produk['jumlahTertulis'],
            ]);
    }

    /**
     * Daftar pemesan tiap produk, dipakai sebagai rincian yang bisa dibuka
     * di halaman rekap belanja grosir.
     *
     * Cakupannya disamakan dengan productQuantities() supaya angka di baris
     * induk selalu cocok dengan jumlah rinciannya.
     *
     * @return Collection<int, Collection<int, array{nama: string, asal: string, jumlah: int}>>
     */
    private function buyersByProduct(OrderPeriod $period): Collection
    {
        return $this->ordersFinal($period)
            ->flatMap(fn (Order $order) => $order->orderItems->map(fn ($item) => [
                'product_id' => $item->product_id,
                'nama' => $order->member?->full_name ?? $order->non_member_name ?? 'Tanpa Nama',
                // Anggota dibedakan dari non-anggota, dan non-anggota
                // ditandai OPD-nya, biar admin tau barangnya diantar ke mana.
                'asal' => $order->member
                    ? 'Anggota'
                    : ($order->opdDepartment?->name ?? 'Non-Anggota'),
                'jumlah' => (int) $item->quantity,
                'jumlahTertulis' => $item->product->formatJumlah((int) $item->quantity),
            ]))
            ->groupBy('product_id')
            ->map(fn (Collection $baris) => $baris->sortBy('nama')->values());
    }

    /**
     * Daftar belanja grosir untuk satu periode: semua produk yang perlu
     * dibeli beserta jumlahnya, diurutkan per nama biar gampang dicek satu
     * per satu saat belanja.
     *
     * Tiap baris membawa daftar pemesannya juga, jadi ketahuan barang itu
     * untuk siapa saja.
     *
     * @return Collection<int, array{nama: string, kategori: string, jumlahDibutuhkan: int, jumlahDibutuhkanTertulis: string, pemesan: Collection}>
     */
    public function productsForPeriod(OrderPeriod $period): Collection
    {
        $pemesan = $this->buyersByProduct($period);

        return $this->productQuantities($period)
            ->map(fn (array $produk, $productId) => [
                'nama' => $produk['nama'],
                'kategori' => $produk['kategori'],
                'jumlahDibutuhkan' => $produk['jumlah'],
                'jumlahDibutuhkanTertulis' => $produk['jumlahTertulis'],
                'pemesan' => $pemesan->get($productId, collect()),
            ])
            ->sortBy('nama')
            ->values();
    }

    /**
     * Distribusi per OPD untuk satu periode, lengkap dengan nama pemesannya.
     * Bedanya dengan opdDistribution() yang cuma berisi angka buat grafik.
     *
     * Khusus non-anggota, karena OPD cuma relevan di sana.
     *
     * @return Collection<int, array{nama: string, jumlahPesanan: int, totalBelanja: float, pemesan: Collection}>
     */
    public function opdRecapForPeriod(OrderPeriod $period): Collection
    {
        return $this->ordersFinal($period, UserType::NonMember)
            ->whereNotNull('opd_id')
            ->groupBy('opd_id')
            ->map(fn (Collection $orders) => [
                'nama' => $orders->first()->opdDepartment?->name ?? 'OPD Terhapus',
                'jumlahPesanan' => $orders->count(),
                'totalBelanja' => (float) $orders->sum('total_amount'),
                'pemesan' => $orders
                    ->map(fn (Order $order) => [
                        'id' => $order->id,
                        'nama' => $order->non_member_name ?? 'Tanpa Nama',
                        'total' => (float) $order->total_amount,
                    ])
                    ->sortBy('nama')
                    ->values(),
            ])
            ->sortByDesc('jumlahPesanan')
            ->values();
    }

    /**
     * Daftar anggota yang sudah dan belum memesan di satu periode.
     *
     * Beda dari rekap lain di file ini, yang ini menghitung semua status
     * termasuk pending. Yang ditanya kan sudah kirim pesanan atau belum,
     * dan pesanan pending tetap sudah dikirim, cuma harganya belum dikunci.
     *
     * Anggota nonaktif tidak ikut didaftar.
     *
     * @return Collection<int, array{kode: string, nama: string, sudahPesan: bool, order: Order|null}>
     */
    public function memberOrderStatusForPeriod(OrderPeriod $period): Collection
    {
        $pesananPerAnggota = Order::where('order_period_id', $period->id)
            ->whereNotNull('member_id')
            ->get()
            ->keyBy('member_id');

        return Member::aktif()
            ->orderBy('full_name')
            ->get()
            ->map(fn (Member $member) => [
                'kode' => $member->member_code,
                'nama' => $member->full_name,
                'sudahPesan' => $pesananPerAnggota->has($member->id),
                'order' => $pesananPerAnggota->get($member->id),
            ]);
    }

    /**
     * Versi ringkas memberOrderStatusForPeriod(), cuma hitungannya. Dipakai
     * kartu ringkasan di dashboard supaya tidak perlu memuat daftar seluruh
     * anggota hanya untuk menampilkan "12 dari 100".
     *
     * @return array{sudah: int, total: int}
     */
    public function memberOrderProgressForPeriod(?OrderPeriod $period): array
    {
        $total = Member::aktif()->count();

        if (! $period) {
            return ['sudah' => 0, 'total' => $total];
        }

        $sudah = Order::where('order_period_id', $period->id)
            ->whereNotNull('member_id')
            ->distinct()
            ->count('member_id');

        return ['sudah' => $sudah, 'total' => $total];
    }

    /**
     * Total belanja seorang anggota dalam setahun, dasar perhitungan SHU.
     * Hanya dari pesanan final, sama seperti rekap lain. Non-anggota tidak
     * punya profil SHU.
     */
    public function memberYearlySpending(Member $member, ?int $year = null): float
    {
        $year ??= now()->year;

        return (float) Order::where('member_id', $member->id)
            ->whereIn('status', $this->statusFinal())
            ->whereYear('created_at', $year)
            ->sum('total_amount');
    }
}
