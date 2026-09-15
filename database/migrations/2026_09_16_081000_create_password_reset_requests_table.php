<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean "lupa password" anggota (16 Sep 2026).
 *
 * Anggota yang lupa password mengajukan permintaan dari halaman masuk.
 * Permintaannya masuk ke satu antrean yang bisa dilihat SEMUA pengurus,
 * siapa pun yang sedang memegang aplikasi bisa membuatkan password baru.
 * handled_by mencatat siapa yang menanganinya.
 *
 * Bukan tabel password_reset_tokens bawaan Laravel: di sini tidak ada email,
 * password barunya dibuatkan pengurus lalu dikirim lewat WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id();
            // Anggota dihapus = permintaannya ikut hilang, tidak ada gunanya lagi
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            // Keterangan opsional dari anggota, mis. "nomor WA saya sudah ganti"
            $table->string('note', 255)->nullable();
            // menunggu / selesai / diabaikan (App\Enums\PasswordResetStatus)
            $table->string('status', 16)->default('menunggu')->index();
            // Pengurus yang menangani; akun pengurus dihapus = catatannya kosong
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
