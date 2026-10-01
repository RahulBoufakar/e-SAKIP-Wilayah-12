{{-- Filter khusus halaman Dokumentasi Admin: IKU + Tim Kerja + Triwulan.
     Sengaja salinan terpisah dari <x-filter-iku-tim> (dipakai role lain, tidak diubah). --}}
@php
    $aktif = collect([request('iku_id'), request('tim_kerja_id'), $triwulan])->filter()->count();
    $hidden = collect(request()->except(['iku_id', 'tim_kerja_id', 'triwulan', 'kategori', 'page']))->filter(fn ($v) => is_scalar($v));
@endphp

<div x-data="{ open: false }" class="relative inline-flex items-center gap-2">
    <button type="button" @click="open = !open"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-card transition-colors hover:bg-slate-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8.25V19.5l-4 1.5v-8.25L3 4.5z" /></svg>
        Filter
        @if ($aktif)
            <span class="rounded-full bg-brand-600 px-1.5 text-[10px] font-bold text-white">{{ $aktif }}</span>
        @endif
    </button>

    @if ($aktif)
        <a href="{{ request()->fullUrlWithQuery(['iku_id' => null, 'tim_kerja_id' => null, 'triwulan' => null, 'page' => null]) }}"
           class="text-xs font-semibold text-slate-500 hover:text-rose-600">Reset</a>
    @endif

    <div x-show="open" x-cloak x-transition @click.outside="open = false"
         class="absolute left-0 top-full z-30 mt-2 w-72 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-black/5">
        <form method="GET" action="{{ url()->current() }}" class="space-y-3">
            <input type="hidden" name="kategori" value="{{ $kategori }}">
            @foreach ($hidden as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div>
                <label class="block text-xs font-medium text-slate-500">IKU</label>
                <select name="iku_id" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua IKU</option>
                    @foreach ($filterOptions['iku'] as $iku)
                        <option value="{{ $iku->id }}" @selected(request('iku_id') == $iku->id)>
                            {{ $iku->kode }} — {{ \Illuminate\Support\Str::limit($iku->deskripsi, 40) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500">Tim Kerja</label>
                <select name="tim_kerja_id" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Tim Kerja</option>
                    @foreach ($filterOptions['tim'] as $tim)
                        <option value="{{ $tim->id }}" @selected(request('tim_kerja_id') == $tim->id)>{{ $tim->nama_tim }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500">Triwulan</label>
                <select name="triwulan" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Triwulan</option>
                    @foreach ($triwulanList as $tw)
                        <option value="{{ $tw->kode }}" @selected($triwulan === $tw->kode)>{{ $tw->kode }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="open = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">Terapkan</button>
            </div>
        </form>
    </div>
</div>