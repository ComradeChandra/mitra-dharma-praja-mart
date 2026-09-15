<?php

namespace App\Http\Requests\Concerns;

/**
 * Kolom "alasan" opsional yang diisi pengurus, dipakai bareng oleh form
 * batalkan pesanan (CancelOrderRequest) dan hapus satu barang
 * (RemoveOrderItemRequest). Alasannya terlihat oleh pemesan, jadi aturan
 * dan cara merapikannya harus sama di kedua tempat.
 */
trait HasAlasan
{
    /**
     * Opsional, berupa teks, maksimal 255 karakter.
     *
     * @return array<int, string>
     */
    protected function aturanAlasan(): array
    {
        return ['nullable', 'string', 'max:255'];
    }

    /**
     * @return array<string, string>
     */
    protected function pesanAlasan(): array
    {
        return [
            'alasan.string' => 'Alasan harus berupa teks.',
            'alasan.max' => 'Alasan maksimal 255 karakter.',
        ];
    }

    /**
     * Alasan yang sudah dirapikan; isian kosong atau cuma spasi jadi null.
     */
    public function alasan(): ?string
    {
        $alasan = trim((string) $this->validated('alasan'));

        return $alasan === '' ? null : $alasan;
    }
}
