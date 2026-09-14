{{--
    Jendela konfirmasi sebelum pesanan terkirim. Dipakai di dalam
    x-order.picker, jadi berlaku untuk form anggota maupun non-anggota.

    KENAPA ADA: tombol "Kirim Pesanan" dulu langsung mengirim. Pemesan tidak
    pernah melihat sekali lagi barang apa saja yang akan terkirim, padahal
    pesanan yang sudah dikirim tidak bisa diubah sendiri. Pak Emir di rapat
    pertama juga menggambarkan langkah ini: "baru nanti di sini spot total
    ... oke konfirmasi pemesanan" (CLAUDE.md, Lampiran A).

    Ini BUKAN checkout bertahap. Tidak ada halaman baru, tidak ada keranjang;
    cuma satu jendela berisi apa yang sudah diisi di form, lalu kirim atau
    periksa lagi.

    Isinya dari komponen Alpine "formPesan" (resources/js/order-form.js):
    rincianDipilih, totalSementara, adaHargaMenyusul, dan ringkasKirim (cara
    terima & identitas, dibaca dari form saat jendela dibuka).

    Kalau JavaScript mati, jendela ini tidak pernah muncul dan form terkirim
    langsung seperti sebelumnya.
--}}
<div
    x-show="konfirmasiTerbuka"
    x-cloak
    x-effect="document.body.classList.toggle('overflow-hidden', konfirmasiTerbuka)"
    @keydown.escape.window="tutupKonfirmasi()"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="judul-konfirmasi-pesanan"
>
    {{-- Latar gelap. Diklik = sama dengan "Periksa lagi". --}}
    <div class="absolute inset-0 bg-gray-900/50" @click="tutupKonfirmasi()"></div>

    <div class="relative w-full sm:max-w-md max-h-[90vh] flex flex-col bg-white rounded-t-2xl sm:rounded-2xl shadow-xl">
        <div class="px-5 pt-5 pb-3 border-b border-gray-100">
            <h2 id="judul-konfirmasi-pesanan" class="font-semibold text-gray-900">Periksa pesananmu</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Setelah dikirim, pesanan tidak bisa diubah sendiri. Kalau ada yang salah, hubungi pengurus.
            </p>
        </div>

        {{-- Daftar barang. Bisa panjang, jadi bagian ini yang bergulir,
             tombolnya tetap kelihatan di bawah. --}}
        <ul class="flex-1 overflow-y-auto px-5 py-2 divide-y divide-gray-50">
            <template x-for="item in rincianDipilih" :key="item.id">
                <li class="flex items-baseline gap-3 py-2 text-sm">
                    <span class="flex-1 min-w-0 text-gray-800" x-text="item.label"></span>
                    <span class="shrink-0 text-gray-500 tabular-nums" x-text="item.jumlah + '×'"></span>
                    <span
                        class="shrink-0 w-24 text-right tabular-nums"
                        :class="item.subtotal === null ? 'text-amber-600 text-xs' : 'text-gray-800'"
                        x-text="item.subtotal === null ? 'harga menyusul' : rupiah(item.subtotal)"
                    ></span>
                </li>
            </template>
        </ul>

        <div class="px-5 py-3 border-t border-gray-100 space-y-2 text-sm">
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-gray-500">Total perkiraan</span>
                <span class="text-base font-semibold text-gray-900 tabular-nums" x-text="rupiah(totalSementara)"></span>
            </div>
            <p x-show="adaHargaMenyusul" class="text-xs text-amber-600">
                Belum termasuk barang yang harganya baru dipastikan pengurus.
            </p>

            <dl class="pt-1 space-y-1 text-xs">
                <div class="flex gap-2">
                    <dt class="w-20 shrink-0 text-gray-400">Cara terima</dt>
                    <dd class="text-gray-700" x-text="ringkasKirim.cara"></dd>
                </div>
                <div x-show="ringkasKirim.butuhAlamat" class="flex gap-2">
                    <dt class="w-20 shrink-0 text-gray-400">Alamat</dt>
                    <dd
                        :class="ringkasKirim.alamat ? 'text-gray-700' : 'text-amber-600'"
                        x-text="ringkasKirim.alamat || 'Belum diisi'"
                    ></dd>
                </div>
                {{-- Cuma non-anggota yang mengisi nama & nomor WA di form --}}
                <div x-show="ringkasKirim.nama" class="flex gap-2">
                    <dt class="w-20 shrink-0 text-gray-400">Atas nama</dt>
                    <dd class="text-gray-700" x-text="ringkasKirim.nama + (ringkasKirim.wa ? ' · ' + ringkasKirim.wa : '')"></dd>
                </div>
            </dl>
        </div>

        <div class="px-5 pb-5 pt-2 flex gap-3">
            <button
                type="button"
                @click="tutupKonfirmasi()"
                class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
                Periksa lagi
            </button>
            <button
                type="button"
                x-ref="tombolKirimFinal"
                @click="kirimSekarang()"
                class="flex-1 py-2.5 rounded-lg bg-emerald-700 text-sm font-semibold text-white hover:bg-emerald-800 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
            >
                Ya, kirim pesanan
            </button>
        </div>
    </div>
</div>
