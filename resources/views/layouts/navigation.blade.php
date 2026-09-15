<nav x-data="{ open: false }" class="no-print relative bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 shadow-md sticky top-0 z-40">
    {{--
        Tekstur titik-titik halus — sengaja TANPA overflow-hidden di <nav>.
        `inset-0` di bawah ini udah otomatis pas di dalam batas navbar tanpa
        perlu overflow-hidden buat "motong"-nya. Kalau overflow-hidden
        dipasang di <nav>, itu ikut motong dropdown akun (elemen di bawah,
        yang nongolnya MELEBIHI tinggi navbar) — persis bug yang bikin
        dropdown-nya nggak kelihatan penuh.
    --}}
    <div class="absolute inset-0 opacity-[0.07] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none"></div>

    <!-- Primary Navigation Menu -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo + nama koperasi -->
                <div class="shrink-0 flex items-center gap-2.5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                        {{-- Bungkus logo pakai "halo" putih tipis, badge logo aslinya
                             berwarna emerald, kalau ditaruh langsung di atas navbar
                             teal dia bakal kurang kontras nyatu sama background --}}
                        <span class="p-1 rounded-full bg-white shadow-sm">
                            <x-application-logo class="h-7 w-7" />
                        </span>
                        <span class="hidden sm:block font-semibold text-white leading-tight">
                            Mitra Dharma<br class="hidden lg:block"> Praja Mart
                        </span>
                    </a>
                </div>

                {{--
                    Navigation Links — tiap menu dikasih ikon kecil di depan teksnya
                    (sebelumnya cuma teks polos, kesannya kosong). Ikonnya konsisten
                    sama yang dipakai di kartu statistik dashboard.
                --}}
                <div class="hidden space-x-6 xl:-my-px xl:ms-10 xl:flex nav-pop-in">
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 11.5 12 4l9 7.5" /><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9" />
                        </svg>
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z" /><path d="M4 7l8 4 8-4" /><path d="M12 11v10" />
                        </svg>
                        {{ __('Produk') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        {{ __('Pesanan') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.members.index')" :active="request()->routeIs('admin.members.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="7" r="3" /><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" /><circle cx="17" cy="8" r="2.3" /><path d="M14.7 20c.25-2.35 1.7-4.25 3.65-5" />
                        </svg>
                        {{ __('Anggota') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.opd-departments.index')" :active="request()->routeIs('admin.opd-departments.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="3.5" width="14" height="17" rx="1.2" /><path d="M9 7.5h1M14 7.5h1M9 11h1M14 11h1M9 14.5h1M14 14.5h1" />
                        </svg>
                        {{ __('OPD') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.order-periods.index')" :active="request()->routeIs('admin.order-periods.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 9h18M8 3v4M16 3v4" />
                        </svg>
                        {{ __('Periode') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.product-requests.index')" :active="request()->routeIs('admin.product-requests.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 me-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18h6M10 21h4" /><path d="M12 3a6 6 0 0 0-4 10.5c.6.55 1 1.3 1 2.1v.4h6v-.4c0-.8.4-1.55 1-2.1A6 6 0 0 0 12 3Z" />
                        </svg>
                        {{ __('Permintaan') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden xl:flex xl:items-center xl:ms-6">
                <x-dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 pl-1.5 pr-3 py-1.5 border border-transparent rounded-full text-sm leading-4 font-medium text-teal-100 hover:bg-white/10 focus:outline-none transition ease-in-out duration-150">
                            {{-- Avatar dibalik warnanya (putih solid, bukan teal) biar
                                 kontras & tetap kelihatan di atas navbar yang sekarang
                                 juga teal --}}
                            <span class="h-7 w-7 rounded-full bg-white text-teal-700 flex items-center justify-center text-xs font-semibold">
                                {{ Str::upper(Str::substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span>{{ Auth::user()->name }}</span>

                            <svg class="fill-current h-4 w-4 text-teal-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- Peran akun yang sedang masuk, supaya jelas kenapa
                             sebagian menu ada atau tidak ada --}}
                        <div class="px-4 pt-2 pb-2 mb-1 border-b border-gray-100">
                            <p class="text-xs text-gray-400">Masuk sebagai</p>
                            <p class="text-sm font-medium text-gray-700">{{ Auth::user()->role->label() }}</p>
                        </div>

                        <x-dropdown-link :href="route('admin.profile.edit')">
                            {{ __('Profil Saya') }}
                        </x-dropdown-link>

                        {{-- Khusus Admin Utama. Di menu akun, bukan di deretan
                             menu atas: jarang dibuka, dan deretan atas sudah
                             penuh di layar 1280px --}}
                        @can('admin-utama')
                            <x-dropdown-link :href="route('admin.accounts.index')">
                                Akun Pengurus
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('admin.settings.edit')">
                                Pengaturan
                            </x-dropdown-link>
                        @endcan

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-teal-200 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="relative hidden xl:hidden bg-teal-700">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                {{ __('Produk') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                {{ __('Pesanan') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.members.index')" :active="request()->routeIs('admin.members.*')">
                {{ __('Anggota') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.opd-departments.index')" :active="request()->routeIs('admin.opd-departments.*')">
                {{ __('OPD') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.order-periods.index')" :active="request()->routeIs('admin.order-periods.*')">
                {{ __('Periode') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.product-requests.index')" :active="request()->routeIs('admin.product-requests.*')">
                {{ __('Permintaan') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-white/15">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-teal-200">{{ Auth::user()->email }} · {{ Auth::user()->role->label() }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('admin.profile.edit')" :active="request()->routeIs('admin.profile.*')">
                    {{ __('Profil Saya') }}
                </x-responsive-nav-link>

                @can('admin-utama')
                    <x-responsive-nav-link :href="route('admin.accounts.index')" :active="request()->routeIs('admin.accounts.*')">
                        Akun Pengurus
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">
                        Pengaturan
                    </x-responsive-nav-link>
                @endcan

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
