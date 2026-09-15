<?php

namespace App\Services;

/**
 * Pembuat tautan wa.me. Satu tempat untuk semua tautan WhatsApp di aplikasi:
 * invoice dari pengurus ke pemesan (WhatsAppInvoiceService) dan tombol
 * "Hubungi Pengurus" dari pemesan ke koperasi (KontakPengurusService).
 *
 * Tidak ada pesan yang terkirim otomatis. Tautannya cuma membuka WhatsApp
 * dengan teks yang sudah terisi; orangnya sendiri yang menekan kirim.
 */
class WhatsAppLinkService
{
    /**
     * Tautan chat ke satu nomor, dengan teks yang sudah terisi kalau ada.
     */
    public function keNomor(string $nomor, string $teks = ''): string
    {
        $tautan = 'https://wa.me/'.$this->nomorInternasional($nomor);

        return $teks === '' ? $tautan : $tautan.'?text='.$this->encodeTeks($teks);
    }

    /**
     * Tautan TANPA nomor tujuan. wa.me tanpa nomor membuka daftar kontak
     * WhatsApp, jadi orangnya bebas memilih mau dikirim ke siapa.
     */
    public function tanpaNomor(string $teks): string
    {
        return 'https://wa.me/?text='.$this->encodeTeks($teks);
    }

    /**
     * wa.me butuh format nomor internasional tanpa "+", "0" di depan, atau
     * spasi/strip (mis. "628123456789", bukan "08123456789" atau
     * "+62 812-3456-789"). Nomor bisa saja diketik dalam format apa pun, jadi
     * dirapikan dulu di sini biar tautannya selalu valid.
     */
    public function nomorInternasional(string $nomor): string
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
}
