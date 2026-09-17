<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasAlasan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi tombol "Tolak" pada panel Tinjauan Stok di halaman detail pesanan
 * pengurus. Tidak perlu memilih barang: penolakan berlaku untuk semua barang
 * yang melebihi stok sekaligus (disesuaikan ke stok tercatat). Yang diisi cuma
 * alasan opsional, yang ikut terlihat oleh pemesan.
 */
class TolakStokRequest extends FormRequest
{
    use HasAlasan;

    /**
     * Rutenya sudah dijaga middleware auth:web (cuma pengurus).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan' => $this->aturanAlasan(),
        ];
    }

    public function messages(): array
    {
        return $this->pesanAlasan();
    }
}
