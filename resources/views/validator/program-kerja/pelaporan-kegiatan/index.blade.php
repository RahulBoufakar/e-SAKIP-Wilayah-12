@extends('validator.layout.app')

@section('title', 'Pelaporan Kegiatan')
@section('subtitle', 'Tahun '.$tahun)

@section('content')
    <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}"
           class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                  {{ $tab === 'berjalan' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
            Tahun Ini ({{ $activeTahun }})
        </a>
        @if ($nextYearAvailable)
            <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'h_plus_1']) }}"
               class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                      {{ $tab === 'h_plus_1' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                Tahun Depan ({{ $nextYear }})
            </a>
        @else
            <span class="flex-1 cursor-not-allowed px-4 py-3 text-center text-sm font-semibold text-slate-300">
                Tahun Depan ({{ $nextYear }}) — Belum tersedia
            </span>
        @endif
    </div>

    <div class="overflow-x-auto rounded-b-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-28 px-4 py-3 font-semibold">Kode Proker</th>
                    <th class="px-4 py-3 font-semibold">Nama Kegiatan</th>
                    <th class="w-40 px-4 py-3 font-semibold">Tim Kerja</th>
                    <th class="w-40 px-4 py-3 text-center font-semibold">Menunggu Validasi</th>
                    <th class="w-28 px-4 py-3 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                                @forelse ($prokerList as $row)
                    @php
                        $dokumen = $row->laporanKegiatan->dokumen ?? collect();
                        $totalMenunggu = $dokumen->where('status_validasi', 'menunggu_validasi')->count();
                    @endphp
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-brand-700">{{ $row->kode_proker ?? '—' }}</td>
                        <td class="max-w-[16rem] px-4 py-3 font-medium text-ink-900">
                            <x-truncate-cell :id="'nama-'.$row->id" :text="$row->usulanProgramKerja->nama_usulan ?? '—'" />
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->usulanProgramKerja->iku->timKerja->nama_tim ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($totalMenunggu > 0)
                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ $totalMenunggu }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('validator.pelaporan-kegiatan.show', $row->id) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada kegiatan yang perlu dilaporkan pada tahun ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($prokerList->hasPages())
        <div class="mt-4">{{ $prokerList->links() }}</div>
    @endif
@endsection