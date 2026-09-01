<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah foto profil anggota.
 *
 * Isinya path relatif di disk 'public' (mis. "member-photos/xxx.jpg"), sama
 * pola dengan `products.image_path`. Nullable karena foto sifatnya opsional —
 * kalau kosong, tampilan jatuh ke avatar huruf depan nama seperti sekarang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
