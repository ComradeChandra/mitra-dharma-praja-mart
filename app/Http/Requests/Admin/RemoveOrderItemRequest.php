<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasAlasan;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form "Hapus barang dari pesanan" di halaman detail pesanan
 * pengurus: satu kotak pilihan berisi barang-barang di pesanan itu, plus
 * alasan opsional yang ikut terlihat oleh pemesan.
 *
 * Barang yang dipilih WAJIB milik pesanan yang sedang dibuka. Tanpa
 * pembatasan ini, mengubah angka di kotak pilihan bisa menghapus barang
 * dari pesanan orang lain.
 */
class RemoveOrderItemRequest extends FormRequest
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
            'order_item_id' => [
                'required',
                'integer',
                Rule::exists('order_items', 'id')->where('order_id', $order->id),
            ],
            'alasan' => $this->aturanAlasan(),
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->pesanAlasan(),
            'order_item_id.required' => 'Pilih barang yang mau dihapus dari pesanan.',
            'order_item_id.integer' => 'Pilih barang yang mau dihapus dari pesanan.',
            'order_item_id.exists' => 'Barang itu tidak ada di pesanan ini. Muat ulang halamannya lalu coba lagi.',
        ];
    }

    /**
     * Barang yang dipilih, sudah dipastikan milik pesanan ini oleh rules().
     */
    public function barang(): OrderItem
    {
        return OrderItem::findOrFail($this->validated('order_item_id'));
    }
}
