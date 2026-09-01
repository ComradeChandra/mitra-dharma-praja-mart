{{--
    Badge "Aktif"/"Nonaktif" buat kolom is_active — dipakai di 3 tempat
    (dashboard admin, daftar anggota, daftar produk) yang sebelumnya masing-
    masing nulis ulang ternary warna+label yang sama persis. Beda dari status
    yang berbasis Enum (OrderStatus, ProductRequestStatus — warnanya
    dipusatkan lewat method badgeColor() di enum-nya masing-masing), is_active
    itu kolom boolean polos, jadi dipusatkan lewat komponen ini aja.

    Props:
    - active: boolean (nilai kolom is_active)
--}}
@props(['active'])

<x-admin.badge :color="$active ? 'green' : 'gray'">
    {{ $active ? 'Aktif' : 'Nonaktif' }}
</x-admin.badge>
