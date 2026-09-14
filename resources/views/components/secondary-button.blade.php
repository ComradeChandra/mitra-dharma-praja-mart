{{--
    Tombol abu-abu buat aksi sekunder (mis. "Batal"). Dipakai di form
    create/edit tiap modul CRUD admin. Disamakan gaya sama primary-button
    (normal-case, rounded-lg) — sebelumnya masih style default Breeze
    (huruf kapital kecil semua).
--}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
