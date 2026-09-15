<?php

namespace App\Services;

use App\Models\Order;

/**
 * Menyiapkan teks invoice dan tautan wa.me untuk satu pesanan (Modul 7).
 *
 * Ini bukan integrasi WhatsApp API berbayar. Yang disiapkan cuma teks dan
 * tautannya; admin sendiri yang menekan kirim di WhatsApp-nya. Tidak ada
 * pesan yang terkirim otomatis dari sistem.
 *
 * Cara merakit tautan wa.me-nya (merapikan nomor, meng-encode teks) ada di
 * WhatsAppLinkService, dipakai bersama tombol "Hubungi Pengurus".
 */
class WhatsAppInvoiceService
{
    public function __construct(
        private OrderLinkService $tautanPesanan,
        private WhatsAppLinkService $tautanWhatsApp,
    ) {}

    /**
     * Teks invoice, siap ditempel ke WhatsApp. Isinya ditentukan di template
     * Blade admin/orders/_invoice-text.blade.php (bukan digabung manual di
     * sini) biar gampang diubah kata-katanya tanpa sentuh kode PHP.
     *
     * Tautan ke halaman pesanan ikut dikirim karena di halaman itulah QRIS
     * dan tombol "Saya sudah bayar" berada. Invoice adalah pesan yang
     * memberi tahu pemesan berapa yang harus dibayar, jadi di situ juga
     * seharusnya dia tahu cara membayarnya.
     */
    public function generateInvoiceText(Order $order): string
    {
        $order->loadMissing('orderItems.product', 'member', 'orderPeriod');

        return trim(view('admin.orders._invoice-text', [
            'order' => $order,
            'tautanPesanan' => $this->tautanPesanan->untukPemesan($order),
        ])->render());
    }

    /**
     * Link wa.me yang begitu diklik langsung membuka chat WhatsApp ke nomor
     * pemesan dengan teks invoice sudah terisi otomatis di kotak ketikan
     * (belum terkirim, admin masih harus klik "Kirim" sendiri).
     */
    public function generateWhatsAppLink(Order $order): string
    {
        return $this->tautanWhatsApp->keNomor($order->whatsapp_number, $this->generateInvoiceText($order));
    }

    /**
     * Link "bagikan struk" buat SI PEMESAN sendiri, beda dari
     * generateWhatsAppLink() yang dipakai admin.
     *
     * Bedanya: tidak ada nomor tujuan. wa.me tanpa nomor akan membuka daftar
     * kontak WhatsApp, jadi anggota bebas mengirim struknya ke siapa pun —
     * ke dirinya sendiri buat arsip, ke pasangan, atau ke pengurus kalau ada
     * yang mau ditanyakan. Kalau nomornya dipatok ke nomornya sendiri,
     * anggota malah cuma bisa kirim ke diri sendiri.
     */
    public function generateShareLink(Order $order): string
    {
        return $this->tautanWhatsApp->tanpaNomor($this->generateInvoiceText($order));
    }
}
