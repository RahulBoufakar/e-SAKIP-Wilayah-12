@extends('validator.layout.app')

@section('title', 'IKU LLDIKTI — Target & Realisasi')
@section('subtitle', 'Tampilan baca-saja, dapat berpindah Triwulan')

@section('content')
    <div class="mt-4">
        <!-- Tabs Triwulan: semua dapat diklik untuk melihat periode lain -->
        <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
            @foreach ($triwulanList as $tw)
                <a href="{{ request()->fullUrlWithQuery(['triwulan' => $tw->kode]) }}"
                   class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                          {{ $triwulanDipilih && $triwulanDipilih->id === $tw->id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                    {{ $tw->kode }}
                </a>
            @endforeach
        </div>

        <!-- Tabel -->
        <div class="overflow-x-auto rounded-b-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th rowspan="2" class="w-1/4 px-4 py-3 text-center align-middle font-semibold">Sasaran Kegiatan</th>
                        <th rowspan="2" class="w-2/5 px-4 py-3 text-center align-middle font-semibold">Indikator Kinerja (IKU/IKK)</th>
                        <th rowspan="2" class="w-24 whitespace-nowrap px-4 py-3 text-center align-middle font-semibold">Target PK</th>
                        <th rowspan="2" class="w-20 px-4 py-3 text-center align-middle font-semibold">Satuan</th>
                        <th colspan="2" class="px-4 py-2 text-center align-middle font-semibold">{{ $triwulanDipilih->kode ?? '—' }}</th>
                    </tr>
                    <tr class="bg-ink-900 text-white">
                        <th class="px-4 py-2 text-center align-middle text-xs font-semibold">Target</th>
                        <th class="px-4 py-2 text-center align-middle text-xs font-semibold">Realisasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-slate-200">
                    @forelse ($sasaranList as $sasaran)
                        @php $jumlahIku = $sasaran->iku->count(); @endphp
                        @forelse ($sasaran->iku as $iku)
                            @php $r = $iku->capaianKinerja->first(); @endphp
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
                                <td class="whitespace-nowrap px-4 py-3 text-center text-slate-600">{{ rtrim(rtrim(number_format($iku->target_pk, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $iku->satuan }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $r?->target ?? '—' }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $r?->realisasi ?? '—' }}</td>
                            </tr>
                        @empty
                        @endforelse
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                                Belum ada Sasaran Kegiatan untuk Tahun Anggaran ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection