{{--
    Kotak putih yang jadi wadah hampir semua isi halaman: tabel, form, ringkasan,
    panel di dashboard. Sebelumnya kombinasi class ini diketik ulang di puluhan
    tempat, jadi kalau warnanya mau diubah harus disisir satu-satu.

    Class tambahan tinggal dioper seperti biasa dan otomatis digabung:
    <x-card class="p-6">, <x-card class="overflow-hidden">, dan seterusnya.
--}}
<div {{ $attributes->merge(['class' => 'bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-card']) }}>
    {{ $slot }}
</div>
