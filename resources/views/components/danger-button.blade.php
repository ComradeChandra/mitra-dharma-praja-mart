{{--
    Tombol merah buat aksi berbahaya (hapus data). Dipakai di form delete
    tiap modul CRUD admin (produk, anggota, OPD, periode). Disamakan gaya
    sama primary-button (normal-case, rounded-lg).
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-red-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-300 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
