@extends('admin.layout.app')

@section('title', 'Master Data — Data PTS')
@section('subtitle', 'Kelola daftar PTS LLDikti Wilayah XII')

@section('content')
    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            mode: {{ $errors->any() ? '\'edit\'' : '\'create\'' }},
            form: { id: null, kode_pts: '{{ old('kode_pts', '') }}', nama_pts: '{{ old('nama_pts', '') }}', status_pts: '{{ old('status_pts', 'aktif') }}', akreditasi_pts: '{{ old('akreditasi_pts', '') }}' },
            openCreate() { this.mode = 'create'; this.form = { id: null, kode_pts: '', nama_pts: '', status_pts: 'aktif', akreditasi_pts: '' }; this.modalOpen = true },
            openEdit(row) { this.mode = 'edit'; this.form = { id: row.id, kode_pts: row.kode_pts, nama_pts: row.nama_pts, status_pts: row.status_pts, akreditasi_pts: row.akreditasi_pts }; this.modalOpen = true },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="w-full max-w-sm">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama PTS..."
                           class="w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
                </div>
            </form>
            <button @click="openCreate()" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah PTS
            </button>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-28 px-5 py-3 font-semibold">Kode PTS</th>
                        <th class="px-5 py-3 font-semibold">Nama PTS</th>
                        <th class="w-36 px-5 py-3 font-semibold">Status</th>
                        <th class="w-40 px-5 py-3 font-semibold">Akreditasi</th>
                        <th class="w-40 px-5 py-3 text-center font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $statusLabel = ['aktif' => 'Aktif', 'alih_bentuk' => 'Alih Bentuk', 'tutup' => 'Tutup', 'alih_kelola' => 'Alih Kelola', 'pembinaan' => 'Pembinaan'];
                        $statusBadge = [
                            'aktif' => 'bg-emerald-50 text-emerald-700',
                            'alih_bentuk' => 'bg-amber-50 text-amber-700',
                            'tutup' => 'bg-rose-50 text-rose-700',
                            'alih_kelola' => 'bg-amber-50 text-amber-700',
                            'pembinaan' => 'bg-slate-100 text-slate-600',
                        ];
                        $akreditasiLabel = ['unggul' => 'Unggul', 'terakreditasi' => 'Terakreditasi', 'tidak_terakreditasi' => 'Tidak Terakreditasi'];
                    @endphp
                    @forelse ($ptsList as $row)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-brand-700">{{ $row->kode_pts }}</td>
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $row->nama_pts }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full {{ $statusBadge[$row->status_pts] }} px-2.5 py-0.5 text-xs font-semibold">{{ $statusLabel[$row->status_pts] }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $akreditasiLabel[$row->akreditasi_pts] ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </div>
                                @include('admin.layout.confirm-delete', [
                                    'refName' => 'confirm-'.$row->id,
                                    'action' => route('admin.master-data.pts.destroy', $row->id),
                                    'label' => 'PTS '.$row->nama_pts,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                                Belum ada data PTS. Klik "Tambah PTS" untuk menambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ptsList->hasPages())
            <div class="mt-4">{{ $ptsList->links() }}</div>
        @endif

        @include('admin.master-data.pts.modal-form')
    </div>
@endsection