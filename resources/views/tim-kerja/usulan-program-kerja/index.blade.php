@extends('tim-kerja.layout.app')

@section('title', 'Usulan Program Kerja')
@section('subtitle', $tab === 'h_plus_1' ? 'Usulan Tahun Depan' : 'Usulan Tahun Ini')

@section('content')
    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            form: { iku_id: '{{ old('iku_id', '') }}', nama_kegiatan: '{{ old('nama_kegiatan', '') }}', permasalahan: '{{ old('permasalahan', '') }}' },
        }"
    >
        <div class="flex justify-end">
            @if ($formLocked)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                    Tidak ada Triwulan aktif — form terkunci
                </span>
            @else
                <button @click="modalOpen = true" type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Usulan Program Kerja
                </button>
            @endif
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-10 px-3 py-2.5 font-semibold">No</th>
                        <th class="w-20 px-3 py-2.5 font-semibold">Tahun</th>
                        <th class="w-28 px-3 py-2.5 font-semibold">PJ</th>
                        <th class="w-48 px-3 py-2.5 font-semibold">Judul</th>
                        <th class="w-48 px-3 py-2.5 font-semibold">IKU/IKK</th>
                        <th class="w-48 px-3 py-2.5 font-semibold">Permasalahan</th>
                        <th class="w-32 px-3 py-2.5 text-center font-semibold">Status</th>
                        <th class="w-20 px-3 py-2.5 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($usulanList as $i => $row)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-3 py-2 text-slate-500">{{ $usulanList->firstItem() + $i }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row->tahun === 'h_plus_1' ? 'TA+1' : 'Berjalan' }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row->iku->timKerja->nama_tim ?? '—' }}</td>
                            <td class="max-w-[12rem] px-3 py-2">
                                <x-truncate-cell :id="'judul-'.$row->id" :text="$row->nama_kegiatan" />
                            </td>
                            <td class="max-w-[12rem] px-3 py-2">
                                <span class="font-mono text-[11px] font-semibold text-brand-700">{{ $row->iku->kode }}</span>
                                <x-truncate-cell :id="'iku-'.$row->id" :text="$row->iku->deskripsi" />
                            </td>
                            <td class="max-w-[12rem] px-3 py-2">
                                <x-truncate-cell :id="'masalah-'.$row->id" :text="$row->permasalahan ?: '—'" />
                            </td>
                            <td class="px-3 py-2 text-center"><x-status-badge :status="$row->status" /></td>
                            <td class="px-3 py-2 text-center">
                                <a href="{{ route('tim-kerja.usulan-program-kerja.show', $row->id) }}" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50">Kelola</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada Usulan Program Kerja.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usulanList->hasPages())
            <div class="mt-4">{{ $usulanList->links() }}</div>
        @endif

        @include('tim-kerja.usulan-program-kerja.modal-form')
    </div>
@endsection