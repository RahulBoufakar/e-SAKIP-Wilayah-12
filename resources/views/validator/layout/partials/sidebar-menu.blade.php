@php
    $linkBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $linkIdle = 'text-brand-100/70 hover:bg-white/5 hover:text-white';
    $linkActive = 'bg-brand-500 text-white shadow-card';

    $sublinkBase = 'flex items-center gap-2 rounded-lg py-2 pl-9 pr-3 text-sm transition-colors';
    $sublinkIdle = 'text-brand-100/60 hover:bg-white/5 hover:text-white';
    $sublinkActive = 'bg-white/10 text-white font-medium';

    $groupHeadBase = 'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $isProgramKerja = request()->routeIs('validator.usulan-program-kerja.*', 'validator.data-proker.*', 'validator.kalender-proker.*', 'validator.pelaporan-kegiatan.*');
    $isCapaianKinerja = request()->routeIs('validator.capaian-kinerja.*', 'validator.analisis-kinerja.*');
@endphp

<a href="{{ route('validator.dashboard') }}" class="{{ $linkBase }} {{ request()->routeIs('validator.dashboard') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    Dashboard
</a>

<!-- Program Kerja -->
<div x-data="{ open: {{ $isProgramKerja ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isProgramKerja ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5M3.75 15h16.5M6 6h12a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0118 20H6a2.25 2.25 0 01-2.25-2.25v-9.5A2.25 2.25 0 016 6z" /></svg>
        <span class="flex-1 text-left">Program Kerja</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('validator.usulan-program-kerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('validator.usulan-program-kerja.*') ? $sublinkActive : $sublinkIdle }}">Usulan Proker</a>
        <a href="{{ route('validator.data-proker.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('validator.data-proker.*') ? $sublinkActive : $sublinkIdle }}">Data Proker</a>
        <a href="{{ route('validator.kalender-proker.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('validator.kalender-proker.*') ? $sublinkActive : $sublinkIdle }}">Kalender Proker</a>
        <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('validator.pelaporan-kegiatan.*') ? $sublinkActive : $sublinkIdle }}">Pelaporan Kegiatan</a>
    </div>
</div>

<!-- Capaian Kinerja -->
<div x-data="{ open: {{ $isCapaianKinerja ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isCapaianKinerja ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span class="flex-1 text-left">Capaian Kinerja</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('validator.capaian-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('validator.capaian-kinerja.*') ? $sublinkActive : $sublinkIdle }}">Capaian Kinerja</a>
        <a href="{{ route('validator.analisa-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('validator.analisa-kinerja.*') ? $sublinkActive : $sublinkIdle }}">Analisis Kinerja</a>
    </div>
</div>