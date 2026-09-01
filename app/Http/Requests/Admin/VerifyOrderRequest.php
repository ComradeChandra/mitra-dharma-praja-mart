<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form verifikasi pesanan, admin mengisi harga final buat tiap
 * item produk FLUKTUATIF yang masih kosong harganya di satu pesanan
 * (produk non-fluktuatif sudah otomatis punya harga sejak dipesan, tidak
 * perlu diisi ulang di sini).
 *
 * Rule-nya dinamis: cuma minta harga buat order_item yang price_at_order-nya
 * masih null, sesuai isi pesanan yang sedang dibuka (via route model binding).
 */
class VerifyOrderRequest extends FormRequest
{
    /**
     * Boleh diakses siapa saja yang lolos middleware 'auth' (admin) di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');

        $rules = [];

        foreach ($order->orderItems as $item) {
            if ($item->price_at_order === null) {
                $rules["prices.{$item->id}"] = ['required', 'numeric', 'min:0'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'prices.*.required' => 'Harga produk fluktuatif wajib diisi sebelum pesanan bisa diverifikasi.',
            'prices.*.numeric' => 'Harga harus berupa angka.',
            'prices.*.min' => 'Harga tidak boleh negatif.',
        ];
    }

    /**
     * Ambil harga-harga yang divalidasi, siap dipakai OrderService::verifyOrder().
     *
     * @return array<int, float>
     */
    public function itemPrices(): array
    {
        return collect($this->validated('prices', []))
            ->map(fn ($price) => (float) $price)
            ->all();
    }
}
