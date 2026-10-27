@props(['provinsiOptions' => null])

@php
    $provinsiOptions = $provinsiOptions ?? \App\Models\Pts::whereNotNull('provinsi')
        ->where('provinsi', '!=', '')
        ->distinct()
        ->orderBy('provinsi')
        ->pluck('provinsi');

    if ($provinsiOptions->isEmpty()) {
        $provinsiOptions = collect(['Maluku', 'Maluku Utara']);
    }

    $selectedStatus = request('status_pts') ?? request('status');
    $selectedAkreditasi = request('akreditasi_pts') ?? request('akreditasi');
    $selectedSumber = request('sumber_data') ?? request('sumber');
    $selectedProvinsi = request('provinsi');

    $aktif = collect([
        $selectedStatus,
        $selectedAkreditasi,
        $selectedSumber,
        $selectedProvinsi,
    ])->filter(fn ($v) => filled($v))->count();

    $filterKeys = [
        'status_pts', 'status',
        'akreditasi_pts', 'akreditasi',
        'sumber_data', 'sumber',
        'provinsi', 'page'
    ];
    $hidden = collect(request()->except($filterKeys))->filter(fn ($v) => is_scalar($v));
@endphp

<div x-data="{ open: false }" class="relative inline-flex items-center gap-2">
    <button type="button" @click="open = !open"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-card transition-colors hover:bg-slate-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8.25V19.5l-4 1.5v-8.25L3 4.5z" /></svg>
        Filter
        @if ($aktif)
            <span class="rounded-full bg-brand-600 px-1.5 text-[10px] font-bold text-white">{{ $aktif }}</span>
        @endif
    </button>

    @if ($aktif)
        <a href="{{ request()->fullUrlWithQuery([
            'status_pts' => null,
            'status' => null,
            'akreditasi_pts' => null,
            'akreditasi' => null,
            'sumber_data' => null,
            'sumber' => null,
            'provinsi' => null,
            'page' => null,
        ]) }}"
           class="text-xs font-semibold text-slate-500 hover:text-rose-600">Reset</a>
    @endif

    <div x-show="open" x-cloak x-transition @click.outside="open = false"
         class="absolute left-0 top-full z-30 mt-2 w-72 sm:w-80 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-black/5">
        <form method="GET" action="{{ url()->current() }}" class="space-y-3">
            @foreach ($hidden as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div>
                <label class="block text-xs font-medium text-slate-500">Status PTS</label>
                <select name="status_pts" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Status PTS</option>
                    <option value="aktif" @selected($selectedStatus === 'aktif')>Aktif</option>
                    <option value="non_aktif" @selected($selectedStatus === 'non_aktif')>Non Aktif</option>
                    <option value="alih_bentuk" @selected($selectedStatus === 'alih_bentuk')>Alih Bentuk</option>
                    <option value="tutup" @selected($selectedStatus === 'tutup')>Tutup</option>
                    <option value="alih_kelola" @selected($selectedStatus === 'alih_kelola')>Alih Kelola</option>
                    <option value="pembinaan" @selected($selectedStatus === 'pembinaan')>Pembinaan</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500">Status Akreditasi</label>
                <select name="akreditasi_pts" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Akreditasi</option>
                    <option value="unggul" @selected($selectedAkreditasi === 'unggul')>Unggul</option>
                    <option value="terakreditasi" @selected($selectedAkreditasi === 'terakreditasi')>Terakreditasi</option>
                    <option value="tidak_terakreditasi" @selected($selectedAkreditasi === 'tidak_terakreditasi')>Tidak Terakreditasi</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500">Sumber Data</label>
                <select name="sumber_data" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Sumber Data</option>
                    <option value="manual" @selected($selectedSumber === 'manual')>Manual</option>
                    <option value="lldikti12" @selected($selectedSumber === 'lldikti12')>LLDikti12</option>
                    <option value="pddikti" @selected($selectedSumber === 'pddikti')>PDDikti</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500">Provinsi</label>
                <select name="provinsi" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Provinsi</option>
                    @foreach ($provinsiOptions as $prov)
                        <option value="{{ $prov }}" @selected($selectedProvinsi === $prov)>{{ $prov }}</option>
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
