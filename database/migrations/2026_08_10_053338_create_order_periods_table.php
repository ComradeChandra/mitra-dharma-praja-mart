<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_periods', function (Blueprint $table) {
            // ID otomatis (primary key)
            $table->id();

            // Label periode, contoh: "Pemesanan Agustus 2026"
            $table->string('label');

            // Tanggal mulai & selesai periode pemesanan dibuka
            $table->date('start_date');
            $table->date('end_date');

            // Status periode: "open" (sedang bisa dipesan) atau "closed" (sudah ditutup).
            // Default "closed" karena admin yang membuka periode secara manual, bukan otomatis.
            $table->string('status')->default('closed');

            // created_at & updated_at otomatis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_periods');
    }
};
