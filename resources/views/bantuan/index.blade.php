{{--
    Halaman Bantuan & pertanyaan umum (FAQ). Terbuka untuk siapa saja, tanpa
    masuk: justru orang yang tidak bisa masuk yang paling butuh halaman ini.

    SEMUA JAWABAN HARUS SESUAI PERILAKU APLIKASI. Kalau aturannya berubah
    (mis. syarat pembatalan di OrderCancellationService), jawaban di sini
    ikut diubah. Persentase SHU sengaja tidak ditulis: angkanya diatur di
    config/koperasi.php dan belum ditetapkan koperasi.
--}}
<x-layouts.public :title="'Bantuan — ' . config('app.name')">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-bold text-gray-800">Bantuan &amp; Pertanyaan Umum</h1>
        <p class="mt-1 text-sm text-gray-500">
            Jawaban singkat untuk hal yang paling sering ditanyakan. Ketuk pertanyaannya untuk membuka jawaban.
        </p>

        {{-- Lompat ke kelompok pertanyaan --}}
        <nav class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="Kelompok pertanyaan">
            @foreach ([
                'memesan' => 'Memesan',
                'pembayaran' => 'Harga & pembayaran',
                'pembatalan' => 'Pembatalan & perubahan',
                'akun' => 'Akun & password',
                'anggota' => 'Anggota & SHU',
                'non-anggota' => 'Non-anggota',
                'pemakai' => 'Pemakai aplikasi',
            ] as $jangkar => $judul)
                <a href="#{{ $jangkar }}" class="px-3 py-1.5 rounded-full border border-gray-200 bg-white text-gray-600 hover:border-emerald-300 hover:text-emerald-800 transition">{{ $judul }}</a>
            @endforeach
        </nav>

        <div class="mt-8 space-y-8">
            <section id="memesan" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Memesan</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Bagaimana cara memesan?">
                        <p>Masuk, buka <strong>Pesan Produk</strong>, lalu isi jumlah pada produk yang mau dipesan. Pilih mau diantar atau diambil di koperasi, lalu tekan <strong>Kirim Pesanan</strong>.</p>
                        <p>Sebelum terkirim, muncul ringkasan pesanan untuk diperiksa sekali lagi. Tidak ada keranjang belanja; semua barang dikirim sekaligus dalam satu pesanan.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Kenapa saya tidak bisa memesan sekarang?">
                        <p>Pemesanan cuma dibuka pada periode tertentu, karena koperasi baru berbelanja setelah pesanan semua orang terkumpul. Di luar periode, halaman pesan memberi tahu bahwa belum ada periode yang dibuka.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Produknya bertuliskan “Tidak tersedia”. Artinya apa?">
                        <p>Barangnya sedang habis, jadi belum bisa dipesan. Produk yang bertuliskan “Tersedia” bisa dipesan seperti biasa. Kalau barang yang kamu cari tidak ada di katalog, usulkan lewat menu <strong>Permintaan Produk</strong> (anggota) atau <strong>Usulkan Produk</strong> (non-anggota).</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Bisa diantar ke rumah atau kantor?">
                        <p>Bisa. Saat memesan, pilih <strong>Diantar</strong> dan tulis alamatnya. Anggota yang sudah mengisi alamat di Profil Saya tidak perlu mengetik ulang; alamatnya terisi otomatis dan tetap bisa diubah untuk pesanan itu saja. Pilih <strong>Ambil di koperasi</strong> kalau mau mengambil sendiri.</p>
                    </x-faq-item>
                </x-card>
            </section>

            <section id="pembayaran" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Harga &amp; pembayaran</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Kenapa ada produk yang harganya “menyusul”?">
                        <p>Harga barang seperti telur dan sayur berubah-ubah dari hari ke hari. Harganya diisi pengurus setelah barangnya dibeli, jadi total pesanan yang memuat barang ini baru pasti setelah itu.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Kapan dan bagaimana saya membayar?">
                        <p>Setelah totalnya pasti, pengurus mengirim tagihan lewat WhatsApp. Buka pesananmu (tautannya ada di tagihan, atau lewat <strong>Pesanan Saya</strong> untuk anggota). Di sana ada QRIS koperasi beserta nominalnya.</p>
                        <p>Bayar dengan aplikasi apa pun yang berlogo QRIS, lalu tekan <strong>Saya sudah bayar</strong>. Bukti transfer boleh dilampirkan supaya pengurus lebih cepat mencocokkannya.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Saya sudah bayar, kenapa belum lunas?">
                        <p>Status <strong>Menunggu pengurus mencocokkan</strong> berarti pengurus sedang memeriksa uangnya di rekening koperasi. Pesanan dianggap lunas setelah pengurus mengonfirmasinya.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Apakah ada struk?">
                        <p>Ada. Pesanan yang totalnya sudah pasti punya <strong>struk resmi</strong> di halaman pesanannya, yang bisa dicetak atau disimpan jadi PDF, dan bisa dibagikan lewat WhatsApp.</p>
                    </x-faq-item>
                </x-card>
            </section>

            <section id="pembatalan" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Pembatalan &amp; perubahan</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Bisakah saya membatalkan pesanan?">
                        <p>Bisa sendiri, selama periode pemesanannya masih dibuka dan pesanannya belum dibayar: buka pesanannya, lalu tekan <strong>Batalkan pesanan</strong> di bagian bawah.</p>
                        <p>Setelah itu, pembatalan lewat pengurus, karena koperasi mungkin sudah membelanjakan barangnya. Pesanan yang dibatalkan tetap tercatat, tapi tidak ditagih.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Kenapa isi pesanan saya berubah?">
                        <p>Kalau ada barang yang tidak bisa dipenuhi, misalnya habis di grosir, pengurus bisa menghapus barang itu dari pesananmu. Totalnya dihitung ulang, dan alasannya tertulis di kotak <strong>Catatan dari pengurus</strong> di halaman pesanan.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Saya salah memesan. Bisa diubah?">
                        <p>Pesanan yang sudah terkirim tidak bisa diubah sendiri. Selama periode masih dibuka dan belum dibayar, batalkan saja lalu kirim pesanan baru. Kalau sudah lewat, hubungi pengurus.</p>
                    </x-faq-item>
                </x-card>
            </section>

            <section id="akun" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Akun &amp; password</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Saya anggota dan lupa password. Bagaimana?">
                        <p>Di halaman masuk, tekan <strong>Lupa password?</strong>, pilih namamu, lalu kirim. Pengurus akan membuatkan password baru dan mengirimkannya lewat WhatsApp ke nomor yang terdaftar.</p>
                        <p>Setelah berhasil masuk, ganti password itu dengan milikmu sendiri di <strong>Profil Saya</strong>.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Nomor WhatsApp saya sudah ganti.">
                        <p>Kalau masih bisa masuk, ubah sendiri di <strong>Profil Saya</strong>; tagihan berikutnya dikirim ke nomor itu. Kalau tidak bisa masuk, tulis di kolom keterangan saat mengajukan lupa password, atau hubungi pengurus.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Apa saja yang bisa saya ubah sendiri?">
                        <p>Anggota bisa mengubah foto, nomor WhatsApp, alamat, dan password di <strong>Profil Saya</strong>. Nama dan kode anggota dikelola pengurus, supaya rekap koperasi tetap rapi; hubungi pengurus kalau ada yang keliru.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Kenapa saya tidak bisa mendaftar sendiri?">
                        <p>Data anggota didaftarkan pengurus koperasi, termasuk password pertamanya. Kalau kamu anggota tapi namamu belum ada di halaman masuk, hubungi pengurus.</p>
                    </x-faq-item>
                </x-card>
            </section>

            <section id="anggota" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Anggota &amp; SHU</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Apa itu perkiraan SHU di Beranda?">
                        <p>SHU (Sisa Hasil Usaha) adalah bagian keuntungan koperasi yang dikembalikan ke anggota di akhir tahun, sebanding dengan belanjanya. Perkiraannya dihitung dari total belanjamu tahun ini, dari pesanan yang harganya sudah pasti.</p>
                        <p>Persentase pastinya ditetapkan koperasi. Selama belum ditetapkan, yang tampil berupa rentang perkiraan.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Apa bedanya anggota dan non-anggota?">
                        <p>Keduanya bisa memesan, membayar, dan mengusulkan produk. Anggota punya akun pribadi, riwayat pesanan, dan perkiraan SHU. Non-anggota masuk lewat kode akses kantornya dan tidak mendapat SHU.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Usulan produk saya diterima atau tidak?">
                        <p>Pengurus menyetujui atau menolak setiap usulan. Anggota bisa melihat hasilnya di menu <strong>Permintaan Produk</strong>.</p>
                    </x-faq-item>
                </x-card>
            </section>

            <section id="non-anggota" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Non-anggota</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Bagaimana non-anggota masuk?">
                        <p>Di halaman masuk, pilih <strong>Non-anggota</strong>, pilih instansi (OPD) kamu, lalu masukkan kode akses kantor. Kode ini dibagikan pengurus ke setiap kantor dan dipakai bersama; kalau belum tahu, tanyakan ke rekan sekantor atau hubungi pengurus.</p>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Bagaimana membuka lagi pesanan saya?">
                        <p>Selama belum keluar, pesanan yang kamu kirim di periode berjalan tercantum di halaman <strong>Pesan</strong>. Setelah itu, buka lewat tautan di tagihan WhatsApp dari pengurus. Walau kode aksesnya dipakai sekantor, pesananmu tidak bisa dibuka rekan sekantor.</p>
                    </x-faq-item>
                </x-card>
            </section>

            <section id="pemakai" class="scroll-mt-24">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Pemakai aplikasi</h2>
                <x-card class="overflow-hidden">
                    <x-faq-item pertanyaan="Siapa saja yang memakai aplikasi ini?">
                        <p>Ada empat tingkatan:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Anggota</strong>: memesan, membayar, melihat riwayat dan perkiraan SHU.</li>
                            <li><strong>Non-anggota</strong>: staf OPD yang memesan lewat kode akses kantornya.</li>
                            <li><strong>Pengurus</strong>: staf koperasi yang mengurus pesanan, pembayaran, produk, anggota, dan membantu yang lupa password.</li>
                            <li><strong>Admin Utama</strong>: pengurus yang juga mengelola akun staf dan pengaturan koperasi.</li>
                        </ul>
                    </x-faq-item>
                    <x-faq-item pertanyaan="Apakah data saya aman?">
                        <p>Harga beli dan angka stok cuma terlihat oleh pengurus. Bukti transfer disimpan tertutup dan cuma bisa dibuka pemesannya sendiri dan pengurus. Password disimpan teracak, sehingga pengurus pun tidak bisa melihatnya; kalau lupa, yang dibuat adalah password baru.</p>
                    </x-faq-item>
                </x-card>
            </section>

            {{-- Jalan terakhir kalau jawabannya tidak ada di atas --}}
            <x-card class="p-6 text-center">
                <h2 class="font-semibold text-gray-800">Masih ada pertanyaan?</h2>
                <p class="mt-1 text-sm text-gray-500">Pengurus koperasi siap membantu.</p>
                <div class="mt-4 flex justify-center">
                    <x-kontak-pengurus
                        varian="tautan"
                        class="px-4 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100"
                        label="Tanya pengurus lewat WhatsApp"
                        pesan="Saya punya pertanyaan soal Mitra Dharma Praja Mart."
                    />
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.public>
