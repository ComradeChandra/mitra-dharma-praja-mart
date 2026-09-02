<x-layouts.member :title="'Pesan Produk — ' . config('app.name')">
    {{-- Isi halamannya ada di x-order.picker, dipakai bareng form non-anggota.
         Bedanya cuma tujuan form, tombol batal, dan alamat bawaan. --}}
    <x-order.picker
        :period="$period"
        :products-by-category="$productsByCategory"
        :action="route('member.orders.store')"
        :batal="route('member.dashboard')"
        :alamat-tersimpan="$alamatTersimpan"
        keterangan="Isi jumlah produk yang mau kamu pesan, lalu kirim sekaligus."
    />
</x-layouts.member>
