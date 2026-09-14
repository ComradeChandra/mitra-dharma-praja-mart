<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderPeriodStatus;
use App\Http\Requests\Concerns\ValidatesOrderPeriodDates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form edit periode pemesanan. Aturannya sama persis dengan
 * StoreOrderPeriodRequest karena label periode tidak perlu unik.
 */
class UpdateOrderPeriodRequest extends FormRequest
{
    use ValidatesOrderPeriodDates;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(OrderPeriodStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Label periode wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
