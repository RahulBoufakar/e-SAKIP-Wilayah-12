@extends('tim-kerja.layout.app')

@section('title', 'Target Kinerja')
@section('subtitle', 'Target Kinerja untuk Tim Kerja Anda')

@section('content')
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-1/4 px-4 py-3 font-semibold">Sasaran Kegiatan</th>
                    <th class="w-2/5 px-4 py-3 font-semibold">Indikator Kinerja (IKU/IKK)</th>
                    <th class="w-24 px-4 py-3 text-center font-semibold">Target</th>
                    <th class="w-20 px-4 py-3 text-center font-semibold">Satuan</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-slate-200">
                @forelse ($sasaranList as $sasaran)
                    @php $jumlahIku = $sasaran->iku->count(); @endphp
                    @forelse ($sasaran->iku as $iku)
                        <tr class="hover:bg-brand-50/40">
                            @if ($loop->first)
                                <td rowspan="{{ $jumlahIku }}" class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $sasaran->kode }}</span>
                                        <span class="min-w-0 max-w-[220px] break-words text-ink-900">{{ $sasaran->nama_sasaran }}</span>
                                    </div>
                                </td>
                            @endif
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-1.5">
                                    <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</span>
                                    <span class="min-w-0 max-w-[320px] break-words text-ink-900">{{ $iku->deskripsi }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ rtrim(rtrim(number_format($iku->target_pk, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $iku->satuan }}</td>
                        </tr>
                    @empty
                    @endforelse
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada Target Kinerja untuk Tim Kerja Anda pada tahun anggaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection