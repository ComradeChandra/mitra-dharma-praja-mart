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
        Schema::create('product_requests', function (Blueprint $table) {
            // ID otomatis (primary key)
            $table->id();

            // Tipe peminta: "member" (anggota) atau "non_member" (non-anggota)
            $table->string('user_type');

            // Diisi kalau peminta adalah anggota terdaftar. Nullable kalau non-anggota.
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();

            // Label identitas peminta buat ditampilkan ke admin, contoh: nama anggota
            // atau "Non-Anggota - Dinas Pendidikan". Disimpan terpisah dari member_id supaya
            // tetap terbaca meski member_id null (non-anggota) atau datanya sudah dihapus.
            $table->string('requester_label');

            // Nama produk yang diminta/diusulkan
            $table->string('product_name');

            // Status tinjauan admin: pending (belum ditinjau), approved (disetujui,
            // akan ditambahkan ke katalog), rejected (ditolak)
            $table->string('status')->default('pending');

            // created_at & updated_at otomatis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
