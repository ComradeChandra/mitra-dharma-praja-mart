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
        Schema::create('orders', function (Blueprint $table) {
            // ID otomatis (primary key)
            $table->id();

            // Periode pemesanan mana pesanan ini masuk. Wajib ada (tidak nullable) karena
            // pesanan selalu terjadi di dalam satu periode yang sedang dibuka admin.
            $table->foreignId('order_period_id')->constrained('order_periods');

            // Tipe pemesan: "member" (anggota) atau "non_member" (non-anggota)
            $table->string('user_type');

            // Diisi kalau pemesan adalah anggota (login pilih nama). Nullable kalau non-anggota.
            // nullOnDelete: kalau data anggota dihapus, riwayat pesanan lama tetap ada
            // (member_id jadi null), tidak ikut terhapus.
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();

            // Diisi kalau pemesan non-anggota (ketik nama sendiri saat pesan)
            $table->string('non_member_name')->nullable();

            // Diisi kalau pemesan non-anggota (wajib pilih dari dropdown OPD, bukan ketik manual)
            $table->foreignId('opd_id')->nullable()->constrained('opd_departments')->nullOnDelete();

            // Nomor WhatsApp pemesan, dipakai kirim invoice
            $table->string('whatsapp_number');

            // Status alur pesanan:
            // - pending   : baru dikirim, belum diverifikasi admin
            // - verified  : admin sudah cek & kunci harga (khusus produk fluktuatif)
            // - invoiced  : invoice teks sudah dikirim ke WhatsApp pemesan
            $table->string('status')->default('pending');

            // Total harga pesanan. Nullable karena baru terisi setelah admin verifikasi
            // (harga produk fluktuatif belum pasti saat pesanan pertama kali dibuat).
            $table->decimal('total_amount', 12, 2)->nullable();

            // created_at & updated_at otomatis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
