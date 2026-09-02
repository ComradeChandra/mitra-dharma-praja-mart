{{--
    Tautan navigasi di header. Isinya ditentukan di App\View\Components\MainNav;
    berkas ini cuma soal tampilan.
--}}
@if ($tautan)
    <nav class="{{ $selaluTampil ? 'flex' : 'hidden lg:flex' }} items-center gap-5 text-sm nav-pop-in">
        @foreach ($tautan as $t)
            <a
                href="{{ $t['href'] }}"
                @if ($t['aktif']) aria-current="page" @endif
                class="whitespace-nowrap {{ $t['aktif'] ? 'text-white font-semibold' : 'text-teal-100 hover:text-white' }}"
            >{{ $t['label'] }}</a>
        @endforeach
    </nav>
@endif
