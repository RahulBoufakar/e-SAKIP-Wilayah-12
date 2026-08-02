@extends('admin.layout.app')

@section('title', 'Target Kinerja')
@section('subtitle', 'Sasaran Kegiatan dan IKU tahun anggaran berjalan')

@section('content')
    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            mode: {{ $errors->any() ? '\'edit\'' : '\'create\'' }},
            form: { id: null, nama_sasaran: '{{ old('nama_sasaran', '') }}' },
            openCreate() { this.mode = 'create'; this.form = { id: null, nama_sasaran: '' }; this.modalOpen = true },
            openEdit(row) { this.mode = 'edit'; this.form = { id: row.id, nama_sasaran: row.nama_sasaran }; this.modalOpen = true },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="w-full max-w-sm">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama sasaran kegiatan..."
                           class="w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
                </div>
            </form>
            <button @click="openCreate()" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Sasaran Kegiatan
            </button>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-20 px-5 py-3 font-semibold">Kode</th>
                        <th class="px-5 py-3 font-semibold">Nama Sasaran Kegiatan</th>
                        <th class="w-48 px-5 py-3 text-center font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sasaranList as $row)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-brand-700">{{ $row->kode }}</td>
                            <td class="px-5 py-3 font-medium text-ink-900">
                                <a href="{{ route('admin.target-kinerja.show', $row->id) }}" class="hover:text-brand-700 hover:underline">{{ $row->nama_sasaran }}</a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.target-kinerja.show', $row->id) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-ink-900 hover:bg-slate-100">Lihat IKU</a>
                                    <button @click="openEdit(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </div>
                                @include('admin.layout.confirm-delete', [
                                    'refName' => 'confirm-'.$row->id,
                                    'action' => route('admin.target-kinerja.destroy', $row->id),
                                    'label' => $row->nama,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-12 text-center text-sm text-slate-400">
                                Belum ada data Sasaran Kegiatan untuk tahun anggaran ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sasaranList->hasPages())
            <div class="mt-4">{{ $sasaranList->links() }}</div>
        @endif

        @include('admin.target-kinerja.modal-form')
    </div>
@endsection
