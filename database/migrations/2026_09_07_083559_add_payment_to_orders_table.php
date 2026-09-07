<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom pembayaran QRIS pada pesanan.
 *
 * Diminta Pak Emir lewat Zoom 3 Sep 2026: gambar QRIS koperasi ditempel
 * setelah pesanan dikirim, lalu status bayarnya kelihatan di dashboard
 * pengurus.
 *
 * QRIS-nya statis, jadi tidak ada webhook yang memberi tahu aplikasi kalau
 * ada yang bayar. Perpindahan statusnya digerakkan manusia, dan kolom-kolom
 * di bawah ini yang mencatat siapa menyatakan apa dan kapan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 32)
                ->default('unpaid')
                ->after('status');

            // Bukti transfer, opsional. Yang mengunggah memudahkan pengurus
            // mencocokkan; yang tidak, tetap dicek lewat mutasi rekening.
            $table->string('payment_proof_path')->nullable()->after('payment_status');

            // Saat pemesan menyatakan sudah bayar.
            $table->timestamp('paid_declared_at')->nullable()->after('payment_proof_path');

            // Saat pengurus mencocokkan ke rekening dan mengonfirmasi.
            $table->timestamp('payment_confirmed_at')->nullable()->after('paid_declared_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_proof_path',
                'paid_declared_at',
                'payment_confirmed_at',
            ]);
        });
    }
};
