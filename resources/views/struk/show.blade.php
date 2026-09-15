<x-layouts.public :title="'Struk ' . $order->nomorStruk() . ' — ' . config('app.name')">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="no-print mb-4">
            <x-back-link :href="$kembali">Kembali</x-back-link>
        </div>

        <x-struk :order="$order" />

        {{--
            Bilah tombol. Diberi no-print supaya tidak ikut tercetak.

            Tombol "Simpan PDF" memanggil dialog cetak bawaan browser, di situ
            ada pilihan "Save as PDF". Sengaja tidak memakai pustaka pembuat PDF
            di server: hasilnya sama, tidak menambah dependensi, dan tidak ada
            berkas menumpuk di server yang perlu dibersihkan.
        --}}
        <div class="no-print mt-6 flex flex-col sm:flex-row gap-3">
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H4v-6h16v6h-2M8 14h8v6H8z" />
                </svg>
                Cetak / Simpan PDF
            </button>

            @if ($tautanWhatsApp)
                <a
                    href="{{ $tautanWhatsApp }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.1-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Zm4.4-5.8c-.2-.1-1.4-.7-1.7-.8s-.4-.1-.5.1-.6.8-.7.9-.3.2-.5 0a6.6 6.6 0 0 1-3.2-2.8c-.2-.4.2-.4.6-1.2a.4.4 0 0 0 0-.4l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3A2.8 2.8 0 0 0 5 8.5a4.9 4.9 0 0 0 1 2.6 11 11 0 0 0 4.3 3.8c1.6.7 2.2.7 3 .6a2.5 2.5 0 0 0 1.7-1.2 2 2 0 0 0 .1-1.2c0-.1-.2-.2-.4-.3Z" />
                    </svg>
                    Kirim Struk lewat WhatsApp
                </a>
            @endif
        </div>

        {{--
            Keterangan jujur soal batasannya. Tautan wa.me cuma bisa mengisi
            TEKS, tidak bisa melampirkan berkas — itu batasan WhatsApp sendiri.
            Melampirkan PDF otomatis butuh WhatsApp Business API yang berbayar.
            Daripada menjanjikan yang tidak bisa dilakukan, caranya dijelaskan
            saja di sini.

            Cuma tampil kalau tombol WhatsApp-nya memang ada. Pesanan yang
            belum final harganya atau sudah dibatalkan tidak diberi tombol itu.
        --}}
        @if ($tautanWhatsApp)
            <p class="no-print mt-4 text-xs text-gray-500 leading-relaxed">
                Tombol WhatsApp mengirim struk dalam bentuk <strong>teks</strong>, langsung terisi di kotak
                ketikan. Kalau mau mengirim versi PDF-nya, simpan dulu lewat tombol
                &ldquo;Cetak / Simpan PDF&rdquo; (pilih <em>Save as PDF</em> di dialog cetak), lalu lampirkan
                berkasnya seperti biasa di WhatsApp.
            </p>
        @endif
    </div>
</x-layouts.public>
