<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use App\Http\Requests\Concerns\HasAlasan;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form "Batalkan pesanan" di halaman detail pesanan pengurus.
 *
 * Alasannya opsional, tapi membantu waktu pesanan itu dibuka lagi nanti
 * ("telur habis di grosir"). Kalau pembayarannya sudah berjalan, pengurus
 * wajib mencentang bahwa uang pemesan dikembalikan: aplikasi tidak bisa
 * mengembalikan uang, jadi minimal pengurus sadar itu harus dilakukan.
 */
class CancelOrderRequest extends FormRequest
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
        /** @var Order $order */
        $order = $this->route('order');

        return [
            'alasan' => $this->aturanAlasan(),
            // Rule::when, BUKAN ['required_if', 'nullable', 'accepted']:
            // "accepted" di Laravel tetap diperiksa walau kotaknya tidak
            // dikirim, jadi pesanan yang belum dibayar (yang tidak menampilkan
            // centang ini sama sekali) malah ikut ditolak.
            'uang_dikembalikan' => Rule::when(
                $order->payment_status !== PaymentStatus::Unpaid,
                ['required', 'accepted'],
                ['nullable'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->pesanAlasan(),
            'uang_dikembalikan.required' => 'Pembayaran pesanan ini sudah berjalan. Centang dulu bahwa uang pemesan dikembalikan.',
            'uang_dikembalikan.accepted' => 'Pembayaran pesanan ini sudah berjalan. Centang dulu bahwa uang pemesan dikembalikan.',
        ];
    }

    public function uangDikembalikan(): bool
    {
        return $this->boolean('uang_dikembalikan');
    }
}
