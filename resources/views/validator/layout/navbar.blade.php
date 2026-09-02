<header class="sticky top-0 z-30 flex h-20 shrink-0 items-center gap-4 bg-gray-300 px-4 sm:px-6 lg:px-8 shadow-sm">

    {{-- Tombol Toggle Sidebar (Desktop) --}}
    <button
        @click="desktopCollapsed = !desktopCollapsed"
        class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-gray-200 hover:text-[#0b3168] lg:flex"
        :title="desktopCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
            <rect width="18" height="18" x="3" y="3" rx="2" />
            <path d="M9 3v18" />
            <path x-show="!desktopCollapsed" d="m16 15-3-3 3-3" />
            <path x-show="desktopCollapsed" d="m14 9 3 3-3 3" />
        </svg>
    </button>

    {{-- Tombol Mobile Sidebar --}}
    <button 
        x-data 
        @click="$dispatch('open-mobile-sidebar')" 
        class="text-slate-500 hover:text-blue-900 lg:hidden" 
        aria-label="Buka menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    {{-- Judul & Subjudul --}}
    <div class="min-w-0 flex-1">
        <h1 class="truncate text-2xl font-bold text-[#0b3168] sm:text-3xl">@yield('title', 'Dashboard')</h1>
        <p class="truncate text-sm text-gray-500">
            @yield('subtitle', 'Ringkasan kinerja Tim Kerja Anda')
        </p>
    </div>

    {{-- Area Konteks & Aksi (Kanan) --}}
    <div class="flex shrink-0 items-center gap-3 sm:gap-4">

        {{-- Badge Tim Kerja --}}
        @if (Auth::user()->timKerja->isNotEmpty())
            <span class="hidden items-center rounded-full bg-gray-100 px-4 py-1.5 text-sm font-bold text-gray-600 sm:inline-flex">
                {{ Auth::user()->timKerja->first()->nama_tim }}
            </span>
        @endif

        {{-- Badge Triwulan --}}
        @if ($ctxTriwulanAktif)
            <span class="hidden items-center rounded-full bg-cyan-100 px-4 py-1.5 text-sm font-bold text-cyan-800 sm:inline-flex">
                {{ $ctxTriwulanAktif->triwulan->kode }}
            </span>
        @endif

        {{-- Dropdown Tahun Anggaran --}}
        @if ($ctxTahunList->isNotEmpty())
            <form method="POST" action="{{ route('admin.context.tahun-anggaran') }}" x-data @change="$el.submit()">
                @csrf
                <label class="sr-only" for="ctx-tahun">Tahun Anggaran</label>
                <select id="ctx-tahun" name="tahun_anggaran_id"
                        class="min-w-[110px] cursor-pointer appearance-none rounded-full border-none bg-cyan-100 px-5 py-1.5 text-sm font-bold text-cyan-800 focus:ring-2 focus:ring-cyan-500 text-left">
                    @foreach ($ctxTahunList as $tahun)
                        <option value="{{ $tahun->id }}" @selected($tahun->id == $ctxTahunAktifId)>TA {{ $tahun->tahun }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        {{-- Ikon Notifikasi Lonceng --}}
        <button class="relative ml-2 flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="h-7 w-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75v-.7V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white ring-2 ring-white">
                1
            </span>
        </button>

    </div>
</header>