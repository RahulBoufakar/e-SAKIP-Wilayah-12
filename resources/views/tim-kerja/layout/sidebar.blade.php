@php
    $isTargetKinerja = request()->routeIs('tim-kerja.target-kinerja.*', 'tim-kerja.rencana-aksi.*', 'tim-kerja.iku-lldikti.*');
    $isProgramKerja = request()->routeIs('tim-kerja.usulan-program-kerja.*', 'tim-kerja.data-proker.*', 'tim-kerja.kalender-proker.*', 'tim-kerja.pelaporan-kegiatan.*');
    $isCapaianKinerja = request()->routeIs('tim-kerja.capaian-kinerja.*', 'tim-kerja.analisa-kinerja.*', 'tim-kerja.analisis-kinerja.*');

    $linkBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $linkIdle = 'text-brand-100/70 hover:bg-white/5 hover:text-white';
    $linkActive = 'bg-brand-500 text-white shadow-card';

    $sublinkBase = 'flex items-center gap-2 rounded-lg py-2 pl-9 pr-3 text-sm transition-colors';
    $sublinkIdle = 'text-brand-100/60 hover:bg-white/5 hover:text-white';
    $sublinkActive = 'bg-white/10 text-white font-medium';

    $groupHeadBase = 'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
@endphp

<!-- Mobile Sidebar Overlay -->
<div x-data="{ mobileOpen: false }"
     x-cloak
     @keydown.escape.window="mobileOpen = false"
     @open-mobile-sidebar.window="mobileOpen = true"
     class="lg:hidden">

    <div x-show="mobileOpen" x-transition.opacity class="fixed inset-0 z-40 bg-ink-950/60" @click="mobileOpen = false"></div>

    <aside :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-ink-950 transition-transform duration-300 ease-in-out">

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
            @if ($pengaturanAplikasi->logo_url)
                <img src="{{ $pengaturanAplikasi->logo_url }}" alt="Logo" class="h-9 w-9 rounded-lg bg-white object-contain">
            @else
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-mono text-sm font-bold text-white">e</div>
            @endif
            <div class="leading-tight">
                <p class="text-sm font-bold text-white">eSAKIP</p>
                <p class="text-[11px] font-medium text-brand-100/60">Tim Kerja</p>
            </div>
            <button @click="mobileOpen = false" class="ml-auto text-brand-100/60 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @include('tim-kerja.layout.partials.sidebar-menu')
        </nav>

        <div class="border-t border-white/10 p-4">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-brand-100/60 transition-colors hover:bg-white/5 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>
</div>

<!-- Desktop Sidebar -->
<aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:flex-col lg:bg-ink-950">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        @if ($pengaturanAplikasi->logo_url)
            <img src="{{ $pengaturanAplikasi->logo_url }}" alt="Logo" class="h-9 w-9 rounded-lg bg-white object-contain">
        @else
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-mono text-sm font-bold text-white">e</div>
        @endif
        <div class="leading-tight">
            <p class="text-sm font-bold text-white">eSAKIP</p>
            <p class="text-[11px] font-medium text-brand-100/60">Tim Kerja</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @include('tim-kerja.layout.partials.sidebar-menu')
    </nav>

    <div class="border-t border-white/10 p-4">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-brand-100/60 transition-colors hover:bg-white/5 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>