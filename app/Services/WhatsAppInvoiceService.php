<?php

namespace App\Services;

use App\Models\Order;

/**
 * Menyiapkan teks invoice dan tautan wa.me untuk satu pesanan (Modul 7).
 *
 * Ini bukan integrasi WhatsApp API berbayar. Yang disiapkan cuma teks dan
 * tautannya; admin sendiri yang menekan kirim di WhatsApp-nya. Tidak ada
 * pesan yang terkirim otomatis dari sistem.
 */
class WhatsAppInvoiceService
{
    public function __construct(private OrderLinkService $tautanPesanan) {}

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
        $nomorWa = $this->normalizePhoneNumber($order->whatsapp_number);
        $teks = $this->generateInvoiceText($order);

        return "https://wa.me/{$nomorWa}?text=".$this->encodeTeks($teks);
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
        return 'https://wa.me/?text='.$this->encodeTeks($this->generateInvoiceText($order));
    }

    /**
     * rawurlencode, BUKAN urlencode. urlencode mengubah spasi jadi "+", dan
     * tidak semua aplikasi WhatsApp mengembalikannya jadi spasi: di sebagian
     * HP invoice terbaca "Total:+Rp105.000". Contoh resmi WhatsApp untuk
     * tautan wa.me memakai %20, yang dihasilkan rawurlencode.
     */
    private function encodeTeks(string $teks): string
    {
        return rawurlencode($teks);
    }

    /**
     * wa.me butuh format nomor internasional tanpa "+", "0" di depan, atau
     * spasi/strip (mis. "628123456789", bukan "08123456789" atau
     * "+62 812-3456-789"). Admin yang input nomor whatsapp_number bisa saja
     * ngetik dalam format apa pun, jadi dirapikan dulu di sini biar link-nya
     * selalu valid apa pun format aslinya.
     */
    private function normalizePhoneNumber(string $nomor): string
    {
        // Buang semua karakter selain angka (spasi, strip, tanda +, dst).
        $angkaSaja = preg_replace('/\D/', '', $nomor);

        // Nomor lokal biasanya diawali "0" (mis. 08123456789), diganti jadi
        // kode negara "62" biar formatnya internasional.
        if (str_starts_with($angkaSaja, '0')) {
            return '62'.substr($angkaSaja, 1);
        }

        return $angkaSaja;
    }
}
