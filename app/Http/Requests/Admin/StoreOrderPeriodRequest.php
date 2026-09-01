<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderPeriodStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah periode pemesanan baru.
 */
class StoreOrderPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            // end_date wajib sama atau setelah start_date, tidak boleh mundur
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            // Rule::enum: nilai status wajib salah satu dari case yang ada di OrderPeriodStatus
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
