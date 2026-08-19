@extends('tim-kerja.layout.app')

@section('title', 'IKU LLDIKTI XII')
@section('subtitle', 'Seluruh IKU tahun anggaran berjalan')

@section('content')
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-28 px-5 py-3 font-semibold">Kode</th>
                    <th class="px-5 py-3 font-semibold">Nama IKU</th>
                    <th class="w-56 px-5 py-3 font-semibold">Sub-Tim / PIC</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ikuList as $iku)
                    @php $isTimSaya = $timKerjaIds->contains($iku->tim_kerja_id); @endphp
                    <tr class="{{ $isTimSaya ? 'bg-brand-50/60' : ($loop->even ? 'bg-slate-50/60' : '') }} hover:bg-brand-50/40">
                        <td class="px-5 py-3 font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</td>
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $iku->deskripsi }}</td>
                        <td class="px-5 py-3">
                            @if ($iku->timKerja)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isTimSaya ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                                    @if ($isTimSaya)
                                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                    @endif
                                    {{ $iku->timKerja->nama_tim }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-12 text-center text-sm text-slate-400">
                            Belum ada IKU untuk tahun anggaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection