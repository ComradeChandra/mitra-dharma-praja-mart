{{--
    Catatan dari pengurus pada sebuah pesanan (kolom catatan_pengurus), mis.
    "16 Sep 2026: Telur Ayam 1kg (2) dihapus dari pesanan oleh pengurus.
    Alasan: habis di grosir." Diisi otomatis oleh
    OrderCancellationService::hapusBarang().

    Dipakai di halaman pesanan anggota, non-anggota, dan pengurus, supaya
    semua orang melihat cerita yang sama tentang kenapa isi pesanan berubah.
    Tidak menampilkan apa pun kalau catatannya kosong.

    Props:
    - order : model Order
--}}
@props(['order'])

@if (filled($order->catatan_pengurus))
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-amber-200 bg-amber-50 px-5 py-4']) }} role="note">
        <p class="text-sm font-semibold text-amber-800">Catatan dari pengurus</p>
        <p class="mt-1 text-sm text-amber-900 whitespace-pre-line leading-relaxed">{{ $order->catatan_pengurus }}</p>
    </div>
@endif
