@php
    // Context bar bersifat read-only/navigasi (Desain Sistem §2), bukan bagian dari
    // logika create/update/delete — query ringan langsung di partial ini supaya tidak
    // perlu menambah compact() di setiap controller index() yang sudah ada.
    $ctxTahunList = \App\Models\TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun']);
    $ctxTahunAktifId = session('tahun_anggaran_id') ?? $ctxTahunList->first()?->id;
    $ctxTriwulanAktif = $ctxTahunAktifId
        ? \App\Models\TriwulanStatus::with('triwulan')->where('tahun_anggaran_id', $ctxTahunAktifId)->where('status', 'aktif')->first()
        : null;
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
        @if ($ctxTriwulanAktif)
            <span class="hidden items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 sm:inline-flex">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                {{ $ctxTriwulanAktif->triwulan->kode }} Aktif
            </span>
        @endif

        @if ($ctxTahunList->isNotEmpty())
            <form method="POST" action="{{ route('admin.context.tahun-anggaran') }}" x-data
                  @change="$el.submit()">
                @csrf
                <label class="sr-only" for="ctx-tahun">Tahun Anggaran</label>
                <select id="ctx-tahun" name="tahun_anggaran_id"
                        class="rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-3 pr-8 text-sm font-medium text-ink-900 focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($ctxTahunList as $tahun)
                        <option value="{{ $tahun->id }}" @selected($tahun->id == $ctxTahunAktifId)>TA {{ $tahun->tahun }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <div class="hidden h-9 w-9 items-center justify-center rounded-full bg-ink-900 text-sm font-bold text-white sm:flex">A</div>
    </div>
</header>
