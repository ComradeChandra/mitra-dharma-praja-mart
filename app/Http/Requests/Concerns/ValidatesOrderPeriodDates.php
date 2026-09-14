<?php

namespace App\Http\Requests\Concerns;

use App\Enums\OrderPeriodStatus;
use App\Models\OrderPeriod;
use Illuminate\Validation\Validator;

/**
 * Pemeriksaan tanggal periode pemesanan, dipakai form tambah & ubah periode.
 *
 * 1. Tanggal yang dikirim bukan teks (mis. larik dari permintaan yang
 *    dimanipulasi) dikosongkan dulu, supaya ditolak aturan "required" dengan
 *    pesan biasa. Tanpa ini, aturan after_or_equal:start_date meneruskan larik
 *    ke Carbon::parse() dan berakhir di error server.
 *
 * 2. Tolak periode berstatus "dibuka" yang tanggalnya bertumpuk dengan
 *    periode lain yang juga sedang dibuka.
 *
 * KENAPA: kalau ada dua periode terbuka di tanggal yang sama,
 * OrderPeriod::yangSedangDibuka() cuma bisa memilih salah satu, jadi pesanan
 * masuk ke periode yang tidak ditebak pengurus dan rekapnya terbelah. Dulu
 * admin bisa membuat keadaan itu tanpa peringatan apa pun.
 *
 * Periode berstatus "ditutup" boleh bertumpuk; itu cuma arsip.
 *
 * Dipakai StoreOrderPeriodRequest dan UpdateOrderPeriodRequest. Saat
 * mengubah, periode itu sendiri tidak dihitung sebagai bentrok.
 */
trait ValidatesOrderPeriodDates
{
    protected function prepareForValidation(): void
    {
        foreach (['start_date', 'end_date'] as $kolom) {
            if ($this->has($kolom) && ! is_string($this->input($kolom))) {
                $this->merge([$kolom => null]);
            }
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Aturan dasar (tanggal kosong/terbalik) sudah gagal: biarkan pesan itu saja.
                if ($validator->errors()->isNotEmpty() || $this->input('status') !== OrderPeriodStatus::Open->value) {
                    return;
                }

                $periodeIni = $this->route('order_period');

                $bentrok = OrderPeriod::where('status', OrderPeriodStatus::Open)
                    ->whereDate('start_date', '<=', $this->input('end_date'))
                    ->whereDate('end_date', '>=', $this->input('start_date'))
                    ->when($periodeIni instanceof OrderPeriod, fn ($query) => $query->whereKeyNot($periodeIni->id))
                    ->first();

                if ($bentrok) {
                    $validator->errors()->add(
                        'status',
                        "Periode \"{$bentrok->label}\" masih dibuka di tanggal yang bertumpuk ({$bentrok->start_date->translatedFormat('d M')} – {$bentrok->end_date->translatedFormat('d M Y')}). Tutup dulu periode itu, atau pilih tanggal lain."
                    );
                }
            },
        ];
    }
}
