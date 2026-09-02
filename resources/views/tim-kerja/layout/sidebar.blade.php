@php
    $isTargetKinerja = request()->routeIs('tim-kerja.target-kinerja.*', 'tim-kerja.rencana-aksi.*', 'tim-kerja.iku-lldikti.*');
    $isProgramKerja = request()->routeIs('tim-kerja.usulan-program-kerja.*', 'tim-kerja.data-proker.*', 'tim-kerja.kalender-proker.*', 'tim-kerja.pelaporan-kegiatan.*');
    $isCapaianKinerja = request()->routeIs('tim-kerja.capaian-kinerja.*', 'tim-kerja.analisa-kinerja.*', 'tim-kerja.analisis-kinerja.*');

    // Style menu
    $linkBase = 'flex items-center gap-3 rounded-md px-4 py-3 text-sm font-medium transition-colors mb-1';
    $linkIdle = 'text-white/80 hover:bg-white/10 hover:text-white';
    $linkActive = 'bg-white/10 text-white font-medium shadow';

    $sublinkBase = 'flex items-center gap-2 rounded-md py-2 pl-10 pr-3 text-sm transition-colors';
    $sublinkIdle = 'text-white/70 hover:bg-white/10 hover:text-white';
    $sublinkActive = 'bg-white/10 text-white font-medium';

    $groupHeadBase = 'flex w-full items-center gap-3 rounded-md px-4 py-3 text-sm font-medium transition-colors text-white/80 hover:bg-white/10 hover:text-white mb-1';
@endphp


{{-- ========================================================= --}}
{{-- MOBILE SIDEBAR --}}
{{-- ========================================================= --}}
<div
    x-data="{ mobileOpen: false }"
    x-cloak
    @keydown.escape.window="mobileOpen = false"
    @open-mobile-sidebar.window="mobileOpen = true"
    class="lg:hidden"
>

    {{-- Backdrop --}}
    <div
        x-show="mobileOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-gray-900/60"
        @click="mobileOpen = false"
    ></div>


    {{-- Mobile Sidebar --}}
    <aside
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 flex h-[100dvh] w-72 max-w-[85vw] flex-col overflow-hidden bg-[#f3f4f6]"
    >

        {{-- ================================================= --}}
        {{-- HEADER LOGO --}}
        {{-- ================================================= --}}
        <div class="flex h-20 shrink-0 items-center justify-between px-5">

            <div class="flex min-w-0 flex-1 items-center justify-center bg-white px-3 py-2">

                @if ($pengaturanAplikasi->logo_url)

                    <img
                        src="{{ $pengaturanAplikasi->logo_url }}"
                        alt="Logo"
                        class="h-20 w-auto max-w-full object-contain"
                    >

                @else

                    <div class="truncate text-xl font-bold text-[#002e5b]">
                        {{ $pengaturanAplikasi->nama_aplikasi ?? 'LLDIKTI 12' }}
                    </div>

                @endif

            </div>


            {{-- Tombol Close --}}
            <button
                type="button"
                @click="mobileOpen = false"
                class="ml-3 shrink-0 text-gray-500 transition-colors hover:text-gray-800"
                aria-label="Tutup menu"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

        </div>


        {{-- ================================================= --}}
        {{-- AREA NAVIGASI --}}
        {{-- ================================================= --}}
        <div class="flex min-h-0 flex-1 flex-col bg-white">


            {{-- TAB ORANGE --}}
            <div
                class="relative z-10 shrink-0 rounded-t-3xl bg-[#f0a500] py-1.5 text-center text-sm font-bold text-white shadow-sm"
            >
                {{ ucwords(str_replace(['-', '_'], ' ', Auth::user()->getRoleNames()->first() ?? 'Tim Kerja')) }}
            </div>


            {{-- CONTAINER MENU BIRU --}}
            <div
                class="relative z-0 mt-0 flex min-h-0 flex-1 flex-col overflow-hidden rounded-t-none rounded-b-none bg-[#002e5b] pt-2 shadow-lg"
            >

                {{-- Menu --}}
                <nav
                    class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-3"
                >
                    @include('tim-kerja.layout.partials.sidebar-menu')
                </nav>


                {{-- LOGOUT --}}
                <div
                    class="shrink-0 border-t border-white/10 p-4"
                >

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                        class="w-full"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-red-400 transition-colors hover:bg-white/5 hover:text-red-300"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                />
                            </svg>

                            <span>Keluar</span>

                        </button>
                    </form>

                </div>

            </div>

        </div>

    </aside>
</div>



{{-- ========================================================= --}}
{{-- DESKTOP SIDEBAR --}}
{{-- ========================================================= --}}
<aside
    class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:flex lg:h-[100dvh] lg:flex-col lg:overflow-hidden lg:bg-[#f3f4f6] border-r border-gray-200 transition-all duration-300 ease-in-out shadow-sm"
    :class="desktopCollapsed ? 'lg:w-20' : 'lg:w-72'"
>

    {{-- Tombol toggle ada di Header (navbar), bukan di sini --}}

    {{-- ================================================= --}}
    {{-- HEADER LOGO --}}
    {{-- ================================================= --}}
    <div
        class="flex h-20 shrink-0 items-center justify-center bg-white px-3 transition-all duration-300 overflow-hidden"
    >

        @if ($pengaturanAplikasi->logo_url)

            <img
                src="{{ $pengaturanAplikasi->logo_url }}"
                alt="Logo"
                class="h-16 w-auto max-w-full object-contain transition-all duration-300"
                :class="desktopCollapsed ? 'scale-75' : 'scale-100'"
            >

        @else

            <div
                class="truncate text-xl font-bold text-[#002e5b] transition-all duration-300"
                :class="desktopCollapsed ? 'text-xs' : 'text-xl'"
            >
                <span x-show="!desktopCollapsed">{{ $pengaturanAplikasi->nama_aplikasi ?? 'LLDIKTI 12' }}</span>
                <span x-show="desktopCollapsed" class="text-sm">L12</span>
            </div>

        @endif

    </div>


    {{-- ================================================= --}}
    {{-- AREA NAVIGASI --}}
    {{-- ================================================= --}}
    <div class="flex min-h-0 flex-1 flex-col bg-white overflow-hidden">


        {{-- TAB ORANGE --}}
        <div
            class="relative z-10 shrink-0 rounded-t-3xl bg-[#f0a500] py-1.5 text-center text-sm font-bold text-white shadow-sm transition-all duration-300 overflow-hidden whitespace-nowrap"
        >
            <span x-show="!desktopCollapsed">{{ ucwords(str_replace(['-', '_'], ' ', Auth::user()->getRoleNames()->first() ?? 'Tim Kerja')) }}</span>
            <span x-show="desktopCollapsed">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
            </span>
        </div>


        {{-- CONTAINER MENU BIRU --}}
        <div
            class="relative z-0 mt-0 flex min-h-0 flex-1 flex-col overflow-hidden rounded-t-none rounded-b-none bg-[#002e5b] pt-2 shadow-lg transition-all duration-300"
        >

            {{-- Menu Navigasi --}}
            <nav
                class="min-h-0 flex-1 space-y-1 overflow-y-auto overflow-x-hidden overscroll-contain px-3 py-4 transition-all duration-300"
            >
                @include('tim-kerja.layout.partials.sidebar-menu')
            </nav>


            {{-- LOGOUT --}}
            <div
                class="shrink-0 border-t border-white/10 p-4 transition-all duration-300"
            >

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="w-full"
                >
                    @csrf

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg px-2 py-2.5 text-sm font-medium text-red-400 transition-colors hover:bg-white/5 hover:text-red-300 whitespace-nowrap"
                        title="Keluar"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                            />
                        </svg>

                        <span x-show="!desktopCollapsed">Keluar</span>

                    </button>
                </form>

            </div>

        </div>

    </div>

</aside>