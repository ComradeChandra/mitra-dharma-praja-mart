<?php

namespace App\View\Components\Order;

use App\Models\Order;
use App\Services\OrderLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Daftar pesanan yang sudah dikirim pemesan ini di periode berjalan,
 * ditampilkan di atas form pesan.
 *
 * KENAPA ADA: kalau orang me-refresh atau menutup halaman tepat saat
 * mengirim, pesanannya bisa sudah tercatat di server walau layarnya kembali
 * ke form kosong. Tanpa pemberitahuan ini dia mengira pesanannya gagal lalu
 * mengirim ulang, dan jadilah pesanan dobel. Anggota memang boleh memesan
 * lebih dari sekali per periode, jadi server tidak bisa menolaknya; yang
 * bisa dilakukan adalah memberi tahu.
 *
 * Komponen berbasis class (bukan Blade biasa) supaya tautannya dibuat lewat
 * OrderLinkService, tidak ditulis di dalam view.
 */
class SentOrders extends Component
{
    /** @var array<int, array{waktu: string, total: string, url: string}> */
    public array $daftar = [];

    /**
     * @param  Collection<int, Order>  $orders
     */
    public function __construct(Collection $orders, OrderLinkService $tautanPesanan)
    {
        $this->daftar = $orders->map(fn (Order $order) => [
            'waktu' => $order->created_at->translatedFormat('d M, H:i'),
            'total' => $order->total_amount === null
                ? 'harga menunggu pengurus'
                : 'Rp'.number_format($order->total_amount, 0, ',', '.'),
            'url' => $tautanPesanan->untukPemesan($order),
        ])->all();
    }

    public function shouldRender(): bool
    {
        return $this->daftar !== [];
    }

    public function render(): View
    {
        return view('components.order.sent-orders');
    }
}
