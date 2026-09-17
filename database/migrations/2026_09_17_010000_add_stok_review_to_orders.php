<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tinjauan stok untuk pesanan yang melebihi stok tercatat (17 Sep 2026).
 *
 * Sistem pre-order tidak menolak pesanan yang melebihi stok, tapi pengurus
 * perlu diberi tahu supaya bisa memutuskan (belanja lebih / sesuaikan). Dua
 * hal yang disimpan:
 *
 * - order_items.stok_saat_pesan: snapshot stok tercatat SAAT barang dipesan
 *   (hanya untuk produk yang stoknya dilacak; null untuk pre-order murni).
 *   Dari sini "melebihi stok" dihitung: quantity > stok_saat_pesan.
 * - orders.keputusan_stok + siapa & kapan: keputusan pengurus atas pesanan
 *   yang melebihi stok (menunggu → disetujui/ditolak).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Stok tercatat pada saat barang ini dipesan. Null = produknya tidak
            // dilacak stoknya, jadi tidak pernah dianggap "melebihi stok".
            $table->integer('stok_saat_pesan')->nullable()->after('quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Keputusan pengurus atas kelebihan stok. Null = pesanan ini tidak
            // memuat barang yang melebihi stok, jadi tidak perlu ditinjau.
            $table->string('keputusan_stok')->nullable()->after('catatan_pengurus');
            // Siapa pengurus yang memutuskan, dan kapan. Ikut null selama belum
            // diputuskan. nullOnDelete supaya menghapus akun pengurus tidak
            // menghapus pesanannya.
            $table->foreignId('keputusan_stok_oleh')->nullable()->after('keputusan_stok')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('keputusan_stok_pada')->nullable()->after('keputusan_stok_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('keputusan_stok_oleh');
            $table->dropColumn(['keputusan_stok', 'keputusan_stok_pada']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('stok_saat_pesan');
        });
    }
};
