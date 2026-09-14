{{--
    Link "Edit" dengan ikon pensil, dipakai di semua tabel index modul admin.
    Props:
    - href: URL tujuan edit
--}}
@props(['href'])

<a href="{{ $href }}" class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-900">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 18.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" />
    </svg>
    Edit
</a>
