{{--
    Kaki halaman untuk layout publik, anggota, dan non-anggota: semboyan
    resmi koperasi (dari logonya) plus tautan ke halaman Bantuan.

    Tautan Bantuan ditaruh di sini karena header non-anggota di HP sudah
    penuh (tiga tautan + Keluar), jadi di sanalah tempat yang pasti terlihat
    di semua halaman.
--}}
<footer class="no-print mt-12 py-6 text-center text-xs text-gray-400">
    <p class="text-gray-500 font-medium tracking-wide">Kebersamaan untuk Kesejahteraan</p>
    <p class="mt-1">
        &copy; {{ date('Y') }} Koperasi Mitra Dharma Praja ·
        <a href="{{ route('bantuan') }}" class="text-emerald-700 hover:text-emerald-900">Bantuan &amp; pertanyaan umum</a>
    </p>
</footer>
