@extends('validator.layout.app')

@section('title', 'Rencana Aksi Triwulan')
@section('subtitle', 'Uraian rencana aksi tiap IKU per Triwulan')

@section('content')
    <form method="GET" class="w-full max-w-sm">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi IKU..."
                   class="w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
        </div>
    </form>

    <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-24 px-4 py-3 font-semibold">Kode</th>
                    <th class="w-64 px-4 py-3 font-semibold">Deskripsi IKU</th>
                    @foreach ($triwulanList as $tw)
                        <th class="px-4 py-3 font-semibold">{{ $tw->kode }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ikuList as $iku)
                    @php $uraianByTriwulan = $iku->rencanaAksi->keyBy('triwulan_id'); @endphp
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</td>
                        <td class="max-w-[16rem] px-4 py-3">
                            <x-truncate-cell :id="'deskripsi-'.$iku->id" :text="$iku->deskripsi" />
                        </td>
                        @foreach ($triwulanList as $tw)
                            <td class="max-w-[12rem] px-4 py-3">
                                <div class="text-xs text-slate-600">
                                    <x-truncate-cell :id="'uraian-'.$iku->id.'-'.$tw->id" :text="$uraianByTriwulan[$tw->id]->uraian ?? '—'" />
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + $triwulanList->count() }}" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada IKU untuk tahun anggaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($ikuList->hasPages())
        <div class="mt-4">{{ $ikuList->links() }}</div>
    @endif
@endsection