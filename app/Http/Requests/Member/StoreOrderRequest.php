<?php

namespace App\Http\Requests\Member;

use App\Http\Requests\Concerns\ValidatesOrderQuantities;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form pemesanan anggota. Bentuk form-nya: satu input jumlah per
 * produk aktif, nama field "quantity[{product_id}]", bukan konsep
 * "keranjang" terpisah, sesuai CLAUDE.md (Modul 3: "isi jumlah langsung").
 *
 * Validasi field quantity-nya dipakai bareng NonMember\StoreOrderRequest
 * lewat trait ValidatesOrderQuantities (lihat trait itu buat detailnya).
 */
class StoreOrderRequest extends FormRequest
{
    use ValidatesOrderQuantities;

    /**
     * Boleh diakses siapa saja yang lolos middleware 'auth:member' di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->quantityRules(),
            ...$this->deliveryRules(),
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->quantityMessages(),
            ...$this->deliveryMessages(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateQuantities($validator));
    }
}
