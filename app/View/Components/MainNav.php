<?php

namespace App\View\Components;

use App\Services\NonMemberSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Tautan navigasi di header, menyesuaikan siapa yang sedang masuk.
 *
 * Dibuat karena halaman katalog dan detail produk memakai layout publik,
 * sementara tautan navigasinya cuma ada di layout anggota. Akibatnya begitu
 * anggota menekan "Katalog", seluruh nav mendatarnya hilang dan yang tersisa
 * tinggal logo dengan menu profil.
 *
 * Sekarang tautannya ditentukan di satu tempat ini dan dipakai ketiga layout,
 * jadi tidak bisa lagi berbeda antar halaman.
 */
class MainNav extends Component
{
    /** @var array<int, array{label: string, href: string, aktif: bool}> */
    public array $tautan = [];

    /**
     * Anggota punya lima tautan plus menu profil, tidak muat di layar sempit,
     * jadi di bawah 1024px semuanya dipindah ke menu profil/hamburger.
     * Non-anggota cuma punya dua, itu masih muat, dan mereka tidak punya
     * menu profil sebagai cadangan — jadi tautannya dibiarkan tampil terus.
     */
    public bool $selaluTampil = false;

    public function __construct(NonMemberSessionService $sesiNonAnggota)
    {
        if (Auth::guard('member')->check()) {
            $this->tautan = [
                $this->tautan('Beranda', 'member.dashboard', 'member.dashboard'),
                $this->tautan('Katalog', 'catalog.index', 'catalog.*'),
                $this->tautan('Pesan Produk', 'member.orders.create', 'member.orders.create'),
                $this->tautan('Pesanan Saya', 'member.orders.index', 'member.orders.index', 'member.orders.show'),
                $this->tautan('Permintaan Produk', 'member.product-requests.index', 'member.product-requests.*'),
            ];

            return;
        }

        if ($sesiNonAnggota->sedangLogin()) {
            $this->selaluTampil = true;
            $this->tautan = [
                $this->tautan('Katalog', 'catalog.index', 'catalog.*'),
                $this->tautan('Pesan', 'non-member.orders.create', 'non-member.orders.*'),
                $this->tautan('Usulkan Produk', 'non-member.product-requests.create', 'non-member.product-requests.*'),
            ];
        }

        // Tamu tidak dapat tautan apa pun; yang tampil cuma tombol "Masuk"
        // dari x-user-menu.
    }

    /**
     * @param  string  ...$polaAktif  pola nama rute yang menandai tautan ini sedang dibuka
     * @return array{label: string, href: string, aktif: bool}
     */
    private function tautan(string $label, string $rute, string ...$polaAktif): array
    {
        return [
            'label' => $label,
            'href' => route($rute),
            'aktif' => request()->routeIs(...$polaAktif),
        ];
    }

    public function render(): View
    {
        return view('components.main-nav');
    }
}
