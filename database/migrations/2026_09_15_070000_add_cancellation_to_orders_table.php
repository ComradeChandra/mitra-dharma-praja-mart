<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembatalan pesanan (15 Sep 2026).
 *
 * Pesanan yang dibatalkan TIDAK dihapus: statusnya jadi "cancelled" dan tetap
 * ada di riwayat, tapi tidak dihitung di rekap, grafik, maupun SHU. Kolom di
 * bawah mencatat kapan, oleh siapa, dan kenapa, supaya pengurus tahu
 * ceritanya waktu membuka pesanan itu lagi.
 *
 * Kolom status sendiri bertipe string, jadi nilai "cancelled" tidak butuh
 * perubahan kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('payment_confirmed_at');
            // 'pemesan' atau 'pengurus', lihat App\Enums\CancelledBy
            $table->string('cancelled_by', 16)->nullable()->after('cancelled_at');
            // Alasan opsional dari pengurus, mis. "Telur habis di grosir"
            $table->string('cancellation_reason')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });
    }
};
