{{--
    Catatan halus untuk PEMESAN kalau sebagian barang yang dipesan jumlahnya
    melebihi persediaan koperasi saat itu (17 Sep 2026).

    SENGAJA tidak menampilkan angka stok apa pun — aturan tegas di CLAUDE.md,
    pemesan tidak boleh melihat angka stok. Cuma pemberitahuan lembut bahwa
    sebagian barang mungkin menyusul, supaya pemesan tidak kaget kalau nanti
    pengurus menyesuaikan jumlahnya.

    Hilang dengan sendirinya begitu pengurus menolak/menyesuaikan (jumlahnya
    tidak lagi melebihi stok). Kalau pengurus menyetujui (belanja lebih),
    catatan ini tetap muncul karena barangnya memang masih melebihi stok.

    Dipakai di halaman pesanan anggota & non-anggota (bukan pengurus — pengurus
    punya panel Tinjauan Stok sendiri). Tidak menampilkan apa pun kalau tidak
    ada barang yang melebihi stok.

    Props:
    - order : model Order
--}}
@props(['order'])

@if ($order->adaMelebihiStok())
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-slate-50 px-5 py-4']) }} role="note">
        <p class="text-sm font-semibold text-slate-700">Sebagian barang mungkin menyusul</p>
        <p class="mt-1 text-sm text-slate-600 leading-relaxed">
            Beberapa barang yang kamu pesan jumlahnya melebihi persediaan koperasi saat ini.
            Pesananmu tetap masuk; kalau ada yang belum bisa dipenuhi, pengurus akan mengabari lewat WhatsApp.
        </p>
    </div>
@endif
