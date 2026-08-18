@php
    // Context bar read-only untuk Tim Kerja (tidak ada form ganti Tahun
    // Anggaran seperti admin — route admin.context.tahun-anggaran khusus
    // role admin).
    $navTahunAktif = \App\Models\TahunAnggaran::find(session('tahun_anggaran_id'))
        ?? \App\Models\TahunAnggaran::orderByDesc('tahun')->first();
    $navTimKerja = auth()->user()?->timKerja;
@endphp

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
    <button
        x-data
        @click="$dispatch('open-mobile-sidebar')"
        class="text-slate-500 hover:text-ink-900 lg:hidden"
        aria-label="Buka menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-base font-bold text-ink-900 sm:text-lg">@yield('title', 'Dashboard')</h1>
        @hasSection('subtitle')
            <p class="truncate text-xs text-slate-500">@yield('subtitle')</p>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
        @if ($navTimKerja && $navTimKerja->isNotEmpty())
            <span class="hidden items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 sm:inline-flex">
                {{ $navTimKerja->pluck('nama_tim')->join(', ') }}
            </span>
        @endif

        @if ($navTahunAktif)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">
                TA {{ $navTahunAktif->tahun }}
            </span>
        @endif

        <div class="hidden h-9 w-9 items-center justify-center rounded-full bg-ink-900 text-sm font-bold text-white sm:flex">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </div>
    </div>
</header>