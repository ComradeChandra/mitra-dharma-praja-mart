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
        Schema::table('members', function (Blueprint $table) {
            // Password anggota, di-hash. Nullable karena anggota lama (yang
            // diinput sebelum fitur ini ada) belum tentu langsung punya
            // password, admin perlu set/reset dulu lewat form kelola anggota
            // sebelum anggota itu bisa login. Ditaruh setelah kolom whatsapp_number
            // biar urutannya mengikuti urutan input di form.
            $table->string('password')->nullable()->after('whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
