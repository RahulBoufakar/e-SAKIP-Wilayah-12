@extends('admin.layout.app')

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

    <div class="mt-5 space-y-4">
        @forelse ($ikuList as $iku)
            @php
                $uraianByTriwulan = $iku->rencanaAksi->keyBy('triwulan_id');
            @endphp
            <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="overflow-hidden rounded-2xl bg-white shadow-card">
                <button type="button" @click="open = !open" class="flex w-full items-center gap-4 px-5 py-4 text-left">
                    <span class="font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink-900">{{ $iku->deskripsi }}</span>
                    <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 text-slate-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>

                <div x-show="open" x-collapse>
                    <form method="POST" action="{{ route('admin.rencana-aksi.update', $iku->id) }}" class="border-t border-slate-100 px-5 py-5">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($triwulanList as $tw)
                                <div>
                                    <label for="uraian-{{ $iku->id }}-{{ $tw->id }}" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $tw->kode }}</label>
                                    <textarea name="uraian[{{ $tw->id }}]" id="uraian-{{ $iku->id }}-{{ $tw->id }}" rows="4"
                                              class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500"
                                              placeholder="Uraian rencana aksi...">{{ old("uraian.{$tw->id}", $uraianByTriwulan[$tw->id]->uraian ?? '') }}</textarea>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan Rencana Aksi</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white px-5 py-12 text-center text-sm text-slate-400 shadow-card">
                Belum ada IKU untuk tahun anggaran ini. Tambahkan IKU lewat menu Target Kinerja terlebih dahulu.
            </div>
        @endforelse
    </div>

    @if ($ikuList->hasPages())
        <div class="mt-4">{{ $ikuList->links() }}</div>
    @endif
@endsection
