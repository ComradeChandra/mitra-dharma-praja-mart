{{--
    Tampilan kosong yang lebih ramah daripada cuma teks "belum ada data" di
    tengah tabel — dipakai di semua halaman index modul admin.

    Props:
    - title       : judul singkat, contoh "Belum ada OPD"
    - description : kalimat penjelas + ajakan aksi
--}}
@props(['title', 'description'])

<div class="text-center py-14 px-6">
    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v10.125c0 .621-.504 1.125-1.125 1.125h-14.25a1.125 1.125 0 0 1-1.125-1.125V7.5M20.25 7.5H3.75M20.25 7.5l-1.5-3.75h-13.5L3.75 7.5" />
    </svg>
    <p class="mt-3 font-medium text-gray-700">{{ $title }}</p>
    <p class="mt-1 text-sm text-gray-400">{{ $description }}</p>
</div>
