<a href="{{ route('tim-kerja.dashboard') }}"
   class="{{ $linkBase }} {{ request()->routeIs('tim-kerja.dashboard') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Dashboard">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Dashboard</span>
</a>

<!-- Target Kinerja -->
<div x-data="{ open: {{ $isTargetKinerja ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="{{ $groupHeadBase }} {{ $isTargetKinerja ? 'text-white' : $linkIdle }}"
            :class="desktopCollapsed ? 'justify-center' : ''"
            title="Target Kinerja">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75l3-1.5" /></svg>
        <span x-show="!desktopCollapsed" x-transition class="flex-1 text-left whitespace-nowrap">Target Kinerja</span>
        <svg x-show="!desktopCollapsed" :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('tim-kerja.target-kinerja.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.target-kinerja.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Target Kinerja">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Target Kinerja</span>
        </a>
        <a href="{{ route('tim-kerja.rencana-aksi.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.rencana-aksi.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Rencana Aksi Triwulan">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Rencana Aksi Triwulan</span>
        </a>
        <a href="{{ route('tim-kerja.iku-lldikti.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.iku-lldikti.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="IKU LLDIKTI XII">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">IKU LLDIKTI XII</span>
        </a>
    </div>
</div>

<!-- Program Kerja -->
<div x-data="{ open: {{ $isProgramKerja ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="{{ $groupHeadBase }} {{ $isProgramKerja ? 'text-white' : $linkIdle }}"
            :class="desktopCollapsed ? 'justify-center' : ''"
            title="Program Kerja">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5M3.75 15h16.5M6 6h12a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0118 20H6a2.25 2.25 0 01-2.25-2.25v-9.5A2.25 2.25 0 016 6z" /></svg>
        <span x-show="!desktopCollapsed" x-transition class="flex-1 text-left whitespace-nowrap">Program Kerja</span>
        <svg x-show="!desktopCollapsed" :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.usulan-program-kerja.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Usulan Proker">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Usulan Proker</span>
        </a>
        <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.data-proker.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Data Proker">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Data Proker</span>
        </a>
        <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'berjalan']) }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.kalender-proker.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Kalender Proker">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Kalender Proker</span>
        </a>
        <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.pelaporan-kegiatan.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Pelaporan Kegiatan">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.144L12 15.75m1.481-1.644l1.938-1.937M8.25 21h7.5a2.25 2.25 0 002.25-2.25V11.25a4.5 4.5 0 00-4.5-4.5h-3.75a4.5 4.5 0 00-4.5 4.5v7.5A2.25 2.25 0 008.25 21z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Pelaporan Kegiatan</span>
        </a>
    </div>
</div>

<!-- Capaian Kinerja -->
<div x-data="{ open: {{ $isCapaianKinerja ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="{{ $groupHeadBase }} {{ $isCapaianKinerja ? 'text-white' : $linkIdle }}"
            :class="desktopCollapsed ? 'justify-center' : ''"
            title="Capaian Kinerja">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span x-show="!desktopCollapsed" x-transition class="flex-1 text-left whitespace-nowrap">Capaian Kinerja</span>
        <svg x-show="!desktopCollapsed" :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('tim-kerja.capaian-kinerja.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.capaian-kinerja.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Capaian Kinerja">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Capaian Kinerja</span>
        </a>
        <a href="{{ route('tim-kerja.analisa-kinerja.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.analisa-kinerja.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Analisis Kinerja">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3-3 3 3 6-6m0 0h-4m4 0v4" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Analisis Kinerja</span>
        </a>
    </div>
</div>