<a href="{{ route('admin.dashboard') }}" class="{{ $linkBase }} {{ request()->routeIs('admin.dashboard') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    Dashboard
</a>

<!-- Master Data -->
<div x-data="{ open: {{ $isMasterData ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isMasterData ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
        <span class="flex-1 text-left">Master Data</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('admin.master-data.tim-kerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.master-data.tim-kerja.*') ? $sublinkActive : $sublinkIdle }}">Tim Kerja</a>
        <a href="{{ route('admin.master-data.user.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.master-data.user.*') ? $sublinkActive : $sublinkIdle }}">User</a>
    </div>
</div>

<!-- Target Kinerja -->
<div x-data="{ open: {{ $isTargetKinerja ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isTargetKinerja ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75l3-1.5" /></svg>
        <span class="flex-1 text-left">Target Kinerja</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('admin.target-kinerja.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.target-kinerja.*') && !request()->routeIs('admin.iku-lldikti.*') ? $sublinkActive : $sublinkIdle }}">Target Kinerja</a>
        <a href="{{ route('admin.iku-lldikti.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.iku-lldikti.*') ? $sublinkActive : $sublinkIdle }}">IKU LLDikti</a>
        <a href="{{ route('admin.rencana-aksi.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.rencana-aksi.*') ? $sublinkActive : $sublinkIdle }}">Rencana Aksi Triwulan</a>
    </div>
</div>

<!-- Tools -->
<div x-data="{ open: {{ $isTools ? 'true' : 'false' }} }">
    <button @click="open = !open" class="{{ $groupHeadBase }} {{ $isTools ? 'text-white' : $linkIdle }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L1.5 3l1.5-1.5L7.5 4.5v1.409l4.65 4.65" /></svg>
        <span class="flex-1 text-left">Tools</span>
        <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
    </button>
    <div x-show="open" x-collapse class="mt-1 space-y-1">
        <a href="{{ route('admin.tools.triwulan.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.triwulan.*') ? $sublinkActive : $sublinkIdle }}">Setting Triwulan</a>
        <a href="{{ route('admin.tools.tahun.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.tahun.*') ? $sublinkActive : $sublinkIdle }}">Setting Tahun</a>
        <a href="{{ route('admin.tools.jumlah-mahasiswa.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.jumlah-mahasiswa.*') ? $sublinkActive : $sublinkIdle }}">Jumlah Mahasiswa</a>
        <a href="{{ route('admin.tools.jumlah-pts.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.jumlah-pts.*') ? $sublinkActive : $sublinkIdle }}">Jumlah PTS</a>
        <a href="{{ route('admin.tools.sinkronisasi.index') }}" class="{{ $sublinkBase }} {{ request()->routeIs('admin.tools.sinkronisasi.*') ? $sublinkActive : $sublinkIdle }}">
            Sinkronisasi Data
            <span class="ml-auto rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold text-brand-100/70">Segera</span>
        </a>
    </div>
</div>