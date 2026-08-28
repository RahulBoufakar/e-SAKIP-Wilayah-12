<a href="{{ route('tim-kerja.dashboard') }}" class="{{ $linkBase }} {{ request()->routeIs('tim-kerja.dashboard') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    Dashboard
</a>

<!-- Target Kinerja -->
<div x-data="{ open: {{ $isTargetKinerja ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isTargetKinerja ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75l3-1.5" /></svg>
        <span class="flex-1 text-left">Target Kinerja</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('tim-kerja.target-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.target-kinerja.*') ? $sublinkActive : $sublinkIdle }}">Target Kinerja</a>
        <a href="{{ route('tim-kerja.rencana-aksi.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.rencana-aksi.*') ? $sublinkActive : $sublinkIdle }}">Rencana Aksi Triwulan</a>
        <a href="{{ route('tim-kerja.iku-lldikti.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.iku-lldikti.*') ? $sublinkActive : $sublinkIdle }}">IKU LLDIKTI XII</a>
    </div>
</div>

<!-- Program Kerja -->
<div x-data="{ open: {{ $isProgramKerja ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isProgramKerja ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5M3.75 15h16.5M6 6h12a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0118 20H6a2.25 2.25 0 01-2.25-2.25v-9.5A2.25 2.25 0 016 6z" /></svg>
        <span class="flex-1 text-left">Program Kerja</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.usulan-program-kerja.*') ? $sublinkActive : $sublinkIdle }}">Usulan Proker</a>
        <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.data-proker.*') ? $sublinkActive : $sublinkIdle }}">Data Proker</a>
        <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.kalender-proker.*') ? $sublinkActive : $sublinkIdle }}">Kalender Proker</a>
        <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.pelaporan-kegiatan.*') ? $sublinkActive : $sublinkIdle }}">Pelaporan Kegiatan</a>
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
        <a href="{{ route('tim-kerja.capaian-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.capaian-kinerja.*') ? $sublinkActive : $sublinkIdle }}">Capaian Kinerja</a>
        <a href="{{ route('tim-kerja.analisa-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.analisa-kinerja.*') ? $sublinkActive : $sublinkIdle }}">Analisis Kinerja</a>
    </div>
</div>

