@extends('validator.layout.app')

@section('title', 'Usulan Program Kerja')
@section('subtitle', 'Validasi Usulan Program Kerja Tim Kerja')

@section('content')
    <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        @foreach (['menunggu_validasi' => 'Menunggu Validasi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
               class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                      {{ $status === $key ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-b-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-24 px-4 py-3 font-semibold">IKU</th>
                    <th class="px-4 py-3 font-semibold">Nama Usulan</th>
                    <th class="w-40 px-4 py-3 font-semibold">Tim Kerja</th>
                    <th class="w-24 px-4 py-3 text-center font-semibold">Tahun</th>
                    <th class="w-28 px-4 py-3 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($usulanList as $row)
                    <tr class="hover:bg-brand-50/40">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-brand-700">{{ $row->iku->kode }}</td>
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $row->nama_usulan }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->iku->timKerja->nama_tim ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $row->tahun }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('validator.usulan-program-kerja.show', $row->id) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">Tidak ada Usulan Program Kerja pada status ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($usulanList->hasPages())
        <div class="mt-4">{{ $usulanList->links() }}</div>
    @endif
@endsection