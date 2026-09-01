<?php

use App\Enums\DeliveryMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah cara penerimaan barang ke pesanan.
 *
 * - `delivery_method` : "antar" atau "ambil" (lihat App\Enums\DeliveryMethod).
 * - `delivery_address`: alamat pengantaran, hanya diisi kalau metodenya
 *   "antar". Kalau "ambil", pemesan datang sendiri ke koperasi jadi tidak ada
 *   alamat yang perlu dicatat.
 *
 * Alamatnya disimpan di pesanan (bukan cuma mengacu ke `members.address`)
 * supaya riwayat pesanan lama tidak ikut berubah kalau anggota memperbarui
 * alamat profilnya, pola yang sama dengan penguncian harga di
 * `order_items.price_at_order`.
 *
 * Default "ambil" dipilih untuk pesanan yang sudah terlanjur ada sebelum
 * migrasi ini: itu pilihan yang paling aman/netral, karena tidak
 * mengarang-ngarang alamat pengantaran yang tidak pernah diisi pemesannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_method')
                ->default(DeliveryMethod::Ambil->value)
                ->after('whatsapp_number');

            $table->string('delivery_address')->nullable()->after('delivery_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'delivery_address']);
        });
    }
};
