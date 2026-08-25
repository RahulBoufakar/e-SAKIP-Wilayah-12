@extends('validator.layout.app')

@section('title', 'Capaian Kinerja')
@section('subtitle', 'Capaian kinerja seluruh IKU per Triwulan')

@section('content')
    <!-- Tabs Triwulan: semua dapat diklik -->
    <div class="mt-4 flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        @foreach ($triwulanList as $tw)
            <a href="{{ request()->fullUrlWithQuery(['triwulan' => $tw->kode]) }}"
               class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                      {{ $triwulanDipilih && $triwulanDipilih->id === $tw->id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                {{ $tw->kode }}
            </a>
        @endforeach
    </div>

    @unless ($isTriwulanAktif)
        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <p class="text-sm font-medium text-amber-800">
                {{ $triwulanDipilih->kode ?? 'Triwulan ini' }} bukan periode Triwulan aktif. Anda tidak dapat melampirkan atau memvalidasi kinerja IKU pada periode ini.
            </p>
        </div>
    @endunless

    <div class="mt-4 overflow-hidden rounded-b-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th rowspan="2" class="w-2/5 px-4 py-3 align-middle font-semibold">Indikator Kinerja Utama (IKU)</th>
                    <th rowspan="2" class="w-24 px-4 py-3 text-center align-middle font-semibold">Target PK</th>
                    <th rowspan="2" class="w-20 px-4 py-3 text-center align-middle font-semibold">Satuan</th>
                    <th colspan="2" class="px-4 py-2 text-center align-middle font-semibold">{{ $triwulanDipilih->kode ?? '—' }}</th>
                    <th rowspan="2" class="w-24 px-4 py-3 text-center align-middle font-semibold">Aksi</th>
                </tr>
                <tr class="bg-ink-900 text-white">
                    <th class="px-4 py-2 text-center align-middle text-xs font-semibold">Target</th>
                    <th class="px-4 py-2 text-center align-middle text-xs font-semibold">Realisasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ikuList as $iku)
                    @php $c = $iku->capaianKinerja->first(); @endphp
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-4 py-3 align-middle">
                            <div class="flex items-center gap-1.5">
                                <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</span>
                                <span class="min-w-0 max-w-[320px] break-words text-ink-900">{{ $iku->deskripsi }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ rtrim(rtrim(number_format($iku->target_pk, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $iku->satuan }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $c?->target ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $c?->realisasi ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($isTriwulanAktif)
                                <button type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Detail</button>
                            @else
                                <button type="button" disabled class="cursor-not-allowed rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300">Detail</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada IKU untuk tahun anggaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection