<?php

namespace App\Http\Requests\Concerns;

/**
 * Rapikan nomor WhatsApp yang diketik orang SEBELUM divalidasi.
 *
 * Aturannya "hanya angka", tapi orang biasa mengetik nomor seperti
 * "0812-3456-7890", "0812 3456 7890", atau "+62 812 3456 7890". Dulu semua
 * itu ditolak dengan pesan galat, padahal nomornya benar. Spasi, strip,
 * titik, kurung, dan tanda + dibuang dulu; yang tersisa baru divalidasi.
 * Huruf tetap ditolak.
 *
 * Awalan 0 atau 62 dibiarkan apa adanya; WhatsAppInvoiceService yang
 * mengubahnya ke format internasional saat membuat tautan wa.me.
 *
 * Dipakai form yang punya kolom whatsapp_number: tambah/ubah anggota (admin),
 * profil anggota, dan pesanan non-anggota.
 */
trait NormalizesWhatsAppNumber
{
    protected function prepareForValidation(): void
    {
        $nomor = $this->input('whatsapp_number');

        if (is_string($nomor)) {
            $this->merge(['whatsapp_number' => preg_replace('/[\s\-.()+]/u', '', $nomor)]);
        }
    }
}
