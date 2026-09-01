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
        Schema::create('opd_departments', function (Blueprint $table) {
            // ID otomatis (primary key)
            $table->id();

            // Nama OPD, harus unik supaya tidak ada dua OPD dengan nama sama di dropdown
            $table->string('name')->unique();

            // created_at & updated_at otomatis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opd_departments');
    }
};
