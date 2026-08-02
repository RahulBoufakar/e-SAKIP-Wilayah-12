@extends('admin.layout.app')

@section('title', 'Detail Sasaran Kegiatan')
@section('subtitle', $sasaran->kode.' — '.$sasaran->nama_sasaran)
@section('content')
    <a href="{{ route('admin.target-kinerja.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Target Kinerja
    </a>

    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            mode: {{ old('id') ? '\'edit\'' : '\'create\'' }},
            form: { 
                id: @js(old('id', null)), 
                deskripsi: @js(old('deskripsi', '')), 
                target_pk: @js(old('target_pk', '')), 
                tim: @js(old('tim', '')),
                satuan: @js(old('satuan', '%')),
                deskripsi_target: @js(old('deskripsi_target', '')),
                tim_kerja_id: @js(old('tim_kerja_id', '')) 
            },
            openCreate() { 
                this.mode = 'create'; 
                this.form = { id: null, deskripsi: '', target_pk: '', tim: '', satuan: '%', deskripsi_target: '', tim_kerja_id: '' }; 
                this.modalOpen = true;
            },
            openEdit(row) { 
                this.mode = 'edit'; 
                this.form = { id: row.id, deskripsi: row.deskripsi, target_pk: row.target_pk, tim: row.tim, satuan: row.satuan, deskripsi_target: row.deskripsi_target, tim_kerja_id: row.tim_kerja_id }; 
                this.modalOpen = true;
            }
        }"
        class="mt-4"
    >
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="font-mono text-xs font-semibold text-brand-700">{{ $sasaran->kode }}</p>
            <h2 class="mt-1 text-lg font-bold text-ink-900">{{ $sasaran->nama_sasaran }}</h2>
        </div>

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="w-full max-w-sm">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi IKU..."
                           class="w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
                </div>
            </form>
            <button @click="openCreate()" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah IKU
            </button>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-25 px-5 py-3 font-semibold">Kode</th>
                        <th class="px-5 py-3 font-semibold">Deskripsi IKU</th>
                        <th class="w-32 px-5 py-3 text-center font-semibold">Target</th>
                        <th class="w-28 px-5 py-3 text-center font-semibold">Satuan</th>
                        <th class="w-36 px-5 py-3 text-center font-semibold">Tim Kerja</th>
                        <th class="w-40 px-5 py-3 text-center font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ikuList as $row)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-1 py-3 font-mono text-xs font-semibold text-brand-700">{{ $row->kode }}</td>
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $row->deskripsi }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ rtrim(rtrim(number_format($row->target_pk, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-5 py-3 text-center font-medium text-ink-900">{{ $row->satuan }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $row->timKerja->nama_tim ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="$refs['detail-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Detail</button>
                                    <button @click="openEdit(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </div>
                                @include('admin.layout.confirm-delete', [
                                    'refName' => 'confirm-'.$row->id,
                                    'action' => route('admin.iku.destroy', $row->id),
                                    'label' => 'IKU '.$row->kode,
                                ])

                                <dialog x-ref="detail-{{ $row->id }}" class="w-full max-w-md rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-900/40">
                                    <div class="p-6">
                                        <h3 class="text-sm font-semibold text-ink-900">Detail Target IKU {{ $row->kode }}</h3>
                                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $row->deskripsi_target }}</p>
                                        <div class="mt-5 flex justify-end">
                                            <button @click="$refs['detail-{{ $row->id }}'].close()" type="button" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                        </div>
                                    </div>
                                </dialog>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">
                                Belum ada IKU untuk Sasaran Kegiatan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ikuList->hasPages())
            <div class="mt-4">{{ $ikuList->links() }}</div>
        @endif
        @include('admin.target-kinerja.iku.modal-form')
    </div>
@endsection
