@php
    $isMasterData = request()->routeIs('admin.master-data.*');
    $isTargetKinerja = request()->routeIs('admin.target-kinerja.*', 'admin.iku.*', 'admin.iku-lldikti.*', 'admin.rencana-aksi.*');
    $isTools = request()->routeIs('admin.tools.*');

    $linkBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $linkIdle = 'text-brand-100/70 hover:bg-white/5 hover:text-white';
    $linkActive = 'bg-brand-500 text-white shadow-card';

    $sublinkBase = 'flex items-center gap-2 rounded-lg py-2 pl-9 pr-3 text-sm transition-colors';
    $sublinkIdle = 'text-brand-100/60 hover:bg-white/5 hover:text-white';
    $sublinkActive = 'bg-white/10 text-white font-medium';

    $groupHeadBase = 'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
@endphp

<!-- Mobile Sidebar Overlay (Hanya muncul di layar kecil) -->
<div x-data="{ mobileOpen: false }" 
     x-cloak 
     @keydown.escape.window="mobileOpen = false" 
     @open-mobile-sidebar.window="mobileOpen = true"
     class="lg:hidden">
    
    <!-- Backdrop -->
    <div x-show="mobileOpen" 
         x-transition.opacity 
         class="fixed inset-0 z-40 bg-ink-950/60" 
         @click="mobileOpen = false"></div>

    <!-- Mobile Panel -->
    <aside :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-ink-950 transition-transform duration-300 ease-in-out">
        
        <!-- Header Sidebar Mobile -->
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-mono text-sm font-bold text-white">e</div>
            <div class="leading-tight">
                <p class="text-sm font-bold text-white">eSAKIP</p>
                <p class="text-[11px] font-medium text-brand-100/60">LLDikti Wilayah XII</p>
            </div>
            <button @click="mobileOpen = false" class="ml-auto text-brand-100/60 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Menu Navigation Mobile (Sama dengan Desktop) -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @include('admin.layout.partials.sidebar-menu')
        </nav>
        
        <div class="border-t border-white/10 p-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-brand-100/60 transition-colors hover:bg-white/5 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                Keluar ke Landing
            </a>
        </div>
    </aside>
</div>


<!-- Desktop Sidebar (Statis, selalu muncul di layar besar) -->
<aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-72 lg:flex-col lg:bg-ink-950">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-mono text-sm font-bold text-white">e</div>
        <div class="leading-tight">
            <p class="text-sm font-bold text-white">eSAKIP</p>
            <p class="text-[11px] font-medium text-brand-100/60">LLDikti Wilayah XII</p>
        </div>
    </div>

    <!-- Menu Navigation Desktop -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @include('admin.layout.partials.sidebar-menu')
    </nav>

    <div class="border-t border-white/10 p-4">
        <a href="{{ route('landing') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-brand-100/60 transition-colors hover:bg-white/5 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
            Keluar ke Landing
        </a>
    </div>
</aside>