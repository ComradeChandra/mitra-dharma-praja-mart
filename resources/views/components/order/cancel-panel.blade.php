{{--
    Tombol "Batalkan pesanan" untuk pemesan (anggota & non-anggota), di bawah
    halaman pesanannya.

    Boleh-tidaknya dihitung OrderCancellationService, komponen ini cuma
    menampilkan hasilnya:
    - boleh      : tombol batal, dengan konfirmasi browser dulu
    - tidak      : kalimat kenapa tidak bisa, dan bahwa harus lewat pengurus
    - lunas      : tidak menampilkan apa-apa; pesanan yang sudah selesai tidak
                   perlu diingatkan soal pembatalan setiap kali dibuka
    - dibatalkan : tidak menampilkan apa-apa (sudah ada x-order.cancelled-notice)

    Konfirmasinya ditaruh di data-confirm, sama seperti x-admin.delete-form,
    supaya teksnya tidak disisipkan langsung ke kode JavaScript.

    Props:
    - order  : model Order
    - action : URL tujuan tombol batal
    - alasan : null kalau boleh dibatalkan, atau kalimat kenapa tidak
--}}
@props(['order', 'action', 'alasan' => null])

@unless ($order->dibatalkan() || $order->payment_status === App\Enums\PaymentStatus::Paid)
    <div {{ $attributes }}>
        @if ($alasan === null)
            <form
                method="POST"
                action="{{ $action }}"
                data-confirm="Batalkan pesanan ini? Pesanan yang sudah dibatalkan tidak bisa dikembalikan. Kalau masih butuh barangnya, kirim pesanan baru."
                onsubmit="return confirm(this.dataset.confirm);"
            >
                @csrf
                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl border border-red-200 text-red-700 text-sm font-medium hover:bg-red-50 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" d="m9 9 6 6m0-6-6 6" />
                    </svg>
                    Batalkan pesanan
                </button>
            </form>
            <p class="mt-2 text-xs text-gray-400 text-center">
                Bisa dibatalkan sendiri selama periode pemesanan masih dibuka dan belum dibayar.
            </p>
        @else
            <p class="text-xs text-gray-400 text-center leading-relaxed">{{ $alasan }}</p>
        @endif
    </div>
@endunless
