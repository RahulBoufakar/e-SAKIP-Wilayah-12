<a href="{{ route('admin.dashboard') }}"
   class="{{ $linkBase }} {{ request()->routeIs('admin.dashboard') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Dashboard">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Dashboard</span>
</a>

<!-- Master Data -->
<div x-data="{ open: {{ $isMasterData ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="{{ $groupHeadBase }} {{ $isMasterData ? 'text-white' : $linkIdle }}"
            :class="desktopCollapsed ? 'justify-center' : ''"
            title="Master Data">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
        <span x-show="!desktopCollapsed" x-transition class="flex-1 text-left whitespace-nowrap">Master Data</span>
        <svg x-show="!desktopCollapsed" :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('admin.master-data.tim-kerja.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.master-data.tim-kerja.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Tim Kerja">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Tim Kerja</span>
        </a>
        <a href="{{ route('admin.master-data.pts.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.master-data.pts.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Data PTS">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Data PTS</span>
        </a>
        <a href="{{ route('admin.master-data.user.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.master-data.user.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="User">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">User</span>
        </a>
    </div>
</div>

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
        <a href="{{ route('admin.target-kinerja.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.target-kinerja.*') && !request()->routeIs('admin.iku-lldikti.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Target Kinerja">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Target Kinerja</span>
        </a>
        <a href="{{ route('admin.iku-lldikti.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.iku-lldikti.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="IKU LLDikti">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">IKU LLDikti</span>
        </a>
        <a href="{{ route('admin.rencana-aksi.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.rencana-aksi.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Rencana Aksi Triwulan">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Rencana Aksi Triwulan</span>
        </a>
    </div>
</div>

<!-- Tools -->
<div x-data="{ open: {{ $isTools ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="{{ $groupHeadBase }} {{ $isTools ? 'text-white' : $linkIdle }}"
            :class="desktopCollapsed ? 'justify-center' : ''"
            title="Tools">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L1.5 3l1.5-1.5L7.5 4.5v1.409l4.65 4.65" /></svg>
        <span x-show="!desktopCollapsed" x-transition class="flex-1 text-left whitespace-nowrap">Tools</span>
        <svg x-show="!desktopCollapsed" :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('admin.tools.triwulan.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.triwulan.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Setting Triwulan">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Setting Triwulan</span>
        </a>
        <a href="{{ route('admin.tools.tahun.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.tahun.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Setting Tahun">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0V11.25A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Setting Tahun</span>
        </a>
        <a href="{{ route('admin.tools.jumlah-mahasiswa.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.jumlah-mahasiswa.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Jumlah Mahasiswa">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Jumlah Mahasiswa</span>
        </a>
        <a href="{{ route('admin.tools.jumlah-pts.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.jumlah-pts.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Jumlah PTS">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Jumlah PTS</span>
        </a>
        <a href="{{ route('admin.tools.sinkronisasi.index') }}"
           class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.sinkronisasi.*') ? $sublinkActive : $sublinkIdle }}"
           :class="desktopCollapsed ? 'justify-center !pl-3 !pr-3' : ''"
           title="Sinkronisasi Data (Segera)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Sinkronisasi Data</span>
            <span x-show="!desktopCollapsed" class="ml-auto rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold text-brand-100/70">Segera</span>
        </a>
    </div>
</div>

{{-- Audit Log --}}
<a href="{{ route('admin.audit-log.index') }}"
   class="{{ $linkBase }} {{ request()->routeIs('admin.audit-log.*') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Audit Log">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Audit Log</span>
</a>

{{-- Pengaturan --}}
<a href="{{ route('admin.pengaturan.index') }}"
   class="{{ $linkBase }} {{ request()->routeIs('admin.pengaturan.*') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Pengaturan">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Pengaturan</span>
</a>