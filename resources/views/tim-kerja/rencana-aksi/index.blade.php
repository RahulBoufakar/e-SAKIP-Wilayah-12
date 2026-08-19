@extends('tim-kerja.layout.app')

@section('title', 'Rencana Aksi Triwulan')
@section('subtitle', 'Uraian rencana aksi tiap IKU Tim Kerja Anda per Triwulan')

@section('content')
    <div class="mt-4 space-y-4">
        @forelse ($ikuList as $iku)
            @php $uraianByTriwulan = $iku->rencanaAksi->keyBy('triwulan_id'); @endphp
            <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="overflow-hidden rounded-2xl bg-white shadow-card">
                <button type="button" @click="open = !open" class="flex w-full items-center gap-4 px-5 py-4 text-left">
                    <span class="font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink-900">{{ $iku->deskripsi }}</span>
                    <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 text-slate-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>

                <div x-show="open" x-collapse>
                    <div class="grid grid-cols-1 gap-4 border-t border-slate-100 px-5 py-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($triwulanList as $tw)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $tw->kode }}</p>
                                <p class="mt-1.5 min-h-[4.5rem] whitespace-pre-line rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $uraianByTriwulan[$tw->id]->uraian ?? '—' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white px-5 py-12 text-center text-sm text-slate-400 shadow-card">
                Belum ada IKU untuk Tim Kerja Anda pada tahun anggaran ini.
            </div>
        @endforelse
    </div>
@endsection