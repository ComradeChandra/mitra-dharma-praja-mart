<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Order;
use Illuminate\Support\Facades\URL;

/**
 * Tautan ke halaman sebuah pesanan, untuk dibuka oleh pemesannya sendiri.
 *
 * Dipakai di dua tempat: pengalihan setelah non-anggota mengirim pesanan, dan
 * invoice WhatsApp yang dikirim pengurus. Disatukan di sini supaya aturan
 * "tautan non-anggota harus bertanda tangan" tidak ditulis dua kali lalu
 * suatu hari berbeda.
 */
class OrderLinkService
{
    /**
     * Anggota cukup tautan biasa: halamannya dijaga login anggota, dan setelah
     * login mereka otomatis dikembalikan ke tautan ini.
     *
     * Non-anggota butuh tautan bertanda tangan. Sesinya cuma bertahan 2 jam,
     * sedangkan pembayaran baru dilakukan setelah invoice dikirim, bisa
     * beberapa hari kemudian. Non-anggota tidak punya akun maupun halaman
     * riwayat, jadi tautan inilah satu-satunya jalan kembali ke pesanannya.
     *
     * Tanda tangannya dihitung dari alamat halaman + APP_KEY, jadi tidak bisa dikarang
     * sendiri oleh rekan sekantor yang cuma menaikkan angka di URL (lihat
     * NonMember\OrderController::klaimLewatTautan()). Sengaja tanpa masa
     * berlaku, karena pesanan lama pun masih boleh dilihat pemesannya.
     */
    public function untukPemesan(Order $order): string
    {
        return $order->user_type === UserType::Member
            ? route('member.orders.show', $order)
            // absolute: false = tanda tangan dihitung dari alamat halamannya
            // saja, tanpa domain dan http/https. Tautan tetap sah walau hosting
            // memasang HTTPS lewat proxy atau domainnya kelak pindah.
            : url(URL::signedRoute('non-member.orders.show', $order, absolute: false));
    }
}
