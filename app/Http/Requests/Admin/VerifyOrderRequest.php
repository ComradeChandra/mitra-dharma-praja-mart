<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        // Harga yang diisi berlaku untuk pesanan ini saja, atau sekaligus untuk
        // pesanan lain di periode yang sama yang harganya masih kosong.
        // Kosong dianggap "pesanan ini saja", pilihan yang paling aman.
        $rules = [
            'terapkan' => ['nullable', 'string', Rule::in(['pesanan-ini', 'semua'])],
        ];

        foreach ($order->orderItems as $item) {
            if ($item->price_at_order === null) {
                // Minimal Rp1: harga 0 hampir pasti salah ketik, dan membuat
                // barangnya tercatat gratis di invoice maupun rekap.
                $rules["prices.{$item->id}"] = ['required', 'numeric', 'min:1', 'max:100000000'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'prices.*.required' => 'Harga produk fluktuatif wajib diisi sebelum pesanan bisa diverifikasi.',
            'prices.*.numeric' => 'Harga harus berupa angka.',
            'prices.*.min' => 'Harga minimal Rp1. Harga 0 membuat barangnya tercatat gratis.',
            'prices.*.max' => 'Harga maksimal Rp100.000.000. Cek lagi, mungkin kelebihan angka nol.',
            'terapkan.in' => 'Pilih harga ini berlaku untuk pesanan ini saja atau untuk semua pesanan.',
            'terapkan.string' => 'Pilih harga ini berlaku untuk pesanan ini saja atau untuk semua pesanan.',
        ];
    }

    /**
     * Pengurus memilih harga ini ikut diisikan ke pesanan lain di periode
     * yang sama (lihat OrderService::verifyOrder()).
     */
    public function terapkanKeSemua(): bool
    {
        return $this->validated('terapkan') === 'semua';
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
