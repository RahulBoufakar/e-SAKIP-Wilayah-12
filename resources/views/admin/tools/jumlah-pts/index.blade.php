@extends('admin.layout.app')

@section('title', 'Setting Jumlah PTS')
@section('subtitle', 'Data jumlah PTS per tahun anggaran')

@section('content')
    <div x-data="{ modalOpen: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="flex justify-end">
            <button @click="modalOpen = true" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Data
            </button>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="px-5 py-3 font-semibold">Tahun Anggaran</th>
                        <th class="px-5 py-3 font-semibold">Jumlah PTS</th>
                        <th class="w-32 px-5 py-3 text-center font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($data as $row)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-5 py-3 font-mono font-semibold text-ink-900">{{ $row->tahunAnggaran->tahun }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ number_format($row->jumlah, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end mr-3">
                                    <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </div>
                                @include('admin.layout.confirm-delete', [
                                    'refName' => 'confirm-'.$row->id,
                                    'action' => route('admin.tools.jumlah-pts.destroy', $row->id),
                                    'label' => 'data Jumlah PTS TA '.$row->tahunAnggaran->tahun,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada data Jumlah PTS.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($data->hasPages())
            <div class="mt-4">{{ $data->links() }}</div>
        @endif

        @include('admin.tools.jumlah-pts._modal')
    </div>
@endsection