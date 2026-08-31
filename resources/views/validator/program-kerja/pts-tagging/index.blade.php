@extends('validator.layout.app')

@section('title', 'Tagging PTS')
@section('subtitle', 'Daftar PTS yang ditagging Tim Kerja pada Program Kerja')

@section('content')
    <form method="GET" class="w-full max-w-sm">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama PTS..."
                   class="w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
        </div>
    </form>

    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-28 px-5 py-3 font-semibold">Kode PTS</th>
                    <th class="px-5 py-3 font-semibold">Nama PTS</th>
                    <th class="w-36 px-5 py-3 text-center font-semibold">Jumlah Tagging</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ptsList as $row)
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-5 py-3 font-mono text-xs font-semibold text-brand-700">{{ $row->kode_pts }}</td>
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $row->nama_pts }}</td>
                        <td class="px-5 py-3 text-center">
                            <div x-data>
                                @if ($row->usulan_program_kerja_count > 0)
                                    <button type="button" @click="$refs['tagging-{{ $row->id }}'].showModal()" class="font-mono text-sm font-semibold text-brand-700 hover:underline">{{ $row->usulan_program_kerja_count }}</button>
                                @else
                                    <span class="font-mono text-sm text-slate-400">0</span>
                                @endif

                                <dialog x-ref="tagging-{{ $row->id }}" @click.self="$el.close()" class="m-auto w-full max-w-lg rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                    <div class="p-6">
                                        <h3 class="text-sm font-bold text-ink-900">Proker yang menagging {{ $row->kode_pts }} — {{ $row->nama_pts }}</h3>

                                        <ul class="mt-4 divide-y divide-slate-100">
                                            @foreach ($row->usulanProgramKerja as $proker)
                                                <li class="py-2.5">
                                                    <p class="text-sm font-medium text-ink-900">{{ $proker->nama_usulan }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-400">
                                                        {{ $proker->programKerja->kode_proker ?? '—' }} · {{ $proker->iku->timKerja->nama_tim ?? '—' }}
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <div class="mt-5 flex justify-end">
                                            <button type="button" @click="$refs['tagging-{{ $row->id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                        </div>
                                    </div>
                                </dialog>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada data PTS.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($ptsList->hasPages())
        <div class="mt-4">{{ $ptsList->links() }}</div>
    @endif
@endsection