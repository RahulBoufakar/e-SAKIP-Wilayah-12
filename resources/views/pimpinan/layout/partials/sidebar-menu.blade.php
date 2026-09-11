<a href="{{ route('pimpinan.dashboard') }}"
   class="{{ $linkBase }} {{ request()->routeIs('pimpinan.dashboard') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Dashboard Eksekutif">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Dashboard Eksekutif</span>
</a>

<a href="{{ route('pimpinan.iku-lldikti.index') }}"
   class="{{ $linkBase }} {{ request()->routeIs('pimpinan.iku-lldikti.*') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Target &amp; Capaian Kinerja">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Target &amp; Capaian Kinerja</span>
</a>

<a href="{{ route('pimpinan.rencana-aksi.index') }}"
   class="{{ $linkBase }} {{ request()->routeIs('pimpinan.rencana-aksi.*') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Rencana Aksi Triwulan">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Rencana Aksi Triwulan</span>
</a>

<a href="{{ route('pimpinan.kalender-proker.index', ['tahun' => 'berjalan']) }}"
   class="{{ $linkBase }} {{ request()->routeIs('pimpinan.kalender-proker.*') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Program Kerja">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5M3.75 15h16.5M6 6h12a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0118 20H6a2.25 2.25 0 01-2.25-2.25v-9.5A2.25 2.25 0 016 6z" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Program Kerja</span>
</a>

<a href="{{ route('pimpinan.laporan.index') }}"
   class="{{ $linkBase }} {{ request()->routeIs('pimpinan.laporan.*') ? $linkActive : $linkIdle }}"
   :class="desktopCollapsed ? 'justify-center' : ''"
   title="Laporan Kinerja">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.144L12 15.75m1.481-1.644l1.938-1.937M8.25 21h7.5a2.25 2.25 0 002.25-2.25V11.25a4.5 4.5 0 00-4.5-4.5h-3.75a4.5 4.5 0 00-4.5 4.5v7.5A2.25 2.25 0 008.25 21z" /></svg>
    <span x-show="!desktopCollapsed" x-transition class="whitespace-nowrap">Laporan Kinerja</span>
</a>
