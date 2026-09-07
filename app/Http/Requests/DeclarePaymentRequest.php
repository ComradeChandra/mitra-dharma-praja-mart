<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pemesan menyatakan sudah membayar lewat QRIS.
 *
 * Dipakai bareng anggota dan non-anggota karena aturannya sama persis.
 * Otorisasinya sendiri dikerjakan di controller masing-masing, karena cara
 * memastikan "pesanan ini milik saya" berbeda antara keduanya: anggota
 * dicocokkan lewat member_id, non-anggota lewat OPD yang sedang login.
 */
class DeclarePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Bukti transfer sengaja opsional. Mewajibkannya menambah satu
            // langkah buat seratusan anggota tiap periode, sementara pengurus
            // tetap harus mencocokkan ke rekening apa pun yang dilampirkan.
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.image' => 'Bukti transfer harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti transfer harus JPG atau PNG.',
            'payment_proof.max' => 'Ukuran bukti transfer maksimal 2MB.',
        ];
    }
}
