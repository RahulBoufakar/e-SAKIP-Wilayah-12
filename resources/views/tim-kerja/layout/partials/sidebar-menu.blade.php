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

<!-- Usulan Proker -->
<a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}" class="{{ $linkBase }} {{ request()->routeIs('tim-kerja.usulan-program-kerja.*') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H5.25a2.25 2.25 0 01-2.25-2.25V6.75a2.25 2.25 0 012.25-2.25h5.379a2.25 2.25 0 011.591.659l4.121 4.121a2.25 2.25 0 01.659 1.591V16.5a2.25 2.25 0 01-2.25 2.25z" /></svg>
    Usulan Proker
</a>
<a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}" class="{{ $linkBase }} {{ request()->routeIs('tim-kerja.data-proker.*') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75h16.5M3.75 15h16.5M6 6h12a2.25 2.25 0 012.25 2.25v9.5A2.25 2.25 0 0118 20H6a2.25 2.25 0 01-2.25-2.25v-9.5A2.25 2.25 0 016 6z" /></svg>
    Data Proker
</a>
<a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'berjalan']) }}" class="{{ $linkBase }} {{ request()->routeIs('tim-kerja.kalender-proker.*') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
    Kalender Proker
</a>
<a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="{{ $linkBase }} {{ request()->routeIs('tim-kerja.pelaporan-kegiatan.*') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
    Pelaporan Kegiatan
</a>

<!-- Capaian Kinerja -->
<div x-data="{ open: {{ $isCapaianKinerja ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isCapaianKinerja ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span class="flex-1 text-left">Capaian Kinerja</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('tim-kerja.capaian-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('tim-kerja.capaian-kinerja.*') ? $sublinkActive : $sublinkIdle }}">Capaian Kinerja</a>
        <span class="{{ $sublinkBase }} cursor-not-allowed text-brand-100/30">
            Analisis Kinerja
            <span class="ml-auto rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold text-brand-100/70">Segera</span>
        </span>
    </div>
</div>