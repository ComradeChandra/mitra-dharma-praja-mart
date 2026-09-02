{{--
    Tautan "kembali" di pojok kiri atas halaman form dan halaman detail.
    Panahnya ikut di sini supaya arahnya konsisten di semua halaman.

    <x-back-link :href="route('admin.members.index')">Kembali ke Data Anggota</x-back-link>
--}}
<a {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4']) }}>
    ← {{ $slot }}
</a>
