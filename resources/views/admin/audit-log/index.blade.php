@extends('admin.layout.app')

@section('title', 'Audit Log')
@section('subtitle', 'Riwayat aktivitas Tim Kerja dan Pengguna')

@section('content')
    <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-6">
        <div>
            <label class="block text-xs font-medium text-slate-500">Tim Kerja</label>
            <select name="tim_kerja_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua Tim Kerja</option>
                @foreach ($timKerjaOptions as $tim)
                    <option value="{{ $tim->id }}" @selected(request('tim_kerja_id') == $tim->id)>{{ $tim->nama_tim }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Pengguna</label>
            <select name="user_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua Pengguna</option>
                @foreach ($userOptions as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}"
                class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-slate-500">Cari Aktivitas</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="mis. menyetujui, menolak, mengirim..."
                class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div class="flex items-end gap-2 sm:col-span-6">
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Terapkan Filter</button>
            @if (request()->anyFilled(['tim_kerja_id', 'user_id', 'tanggal_mulai', 'tanggal_selesai', 'search']))
                <a href="{{ route('admin.audit-log.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </div>
    </form>

    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-40 px-5 py-3 font-semibold">Waktu</th>
                    <th class="w-56 px-5 py-3 font-semibold">Pengguna</th>
                    <th class="px-5 py-3 font-semibold">Aktivitas</th>
                    <th class="w-24 px-5 py-3 text-center font-semibold">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($activities as $log)
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-ink-900">{{ $log->causer?->name ?? 'Sistem' }}</p>
                            @if ($log->causer?->timKerja->isNotEmpty())
                                <p class="text-xs text-slate-400">{{ $log->causer->timKerja->pluck('nama_tim')->join(', ') }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $log->description }}</td>
                        <td class="px-5 py-3 text-center">
                            <div x-data>
                                <button type="button" @click="$refs['detail-{{ $log->id }}'].showModal()" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Lihat</button>

                                <dialog x-ref="detail-{{ $log->id }}" @click.self="$el.close()" class="m-auto w-full max-w-md rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                    <div class="p-6">
                                        <h3 class="text-sm font-bold text-ink-900">Detail Aktivitas</h3>
                                        <table class="mt-4 w-full text-xs">
                                            <tbody class="divide-y divide-slate-100">
                                                <tr>
                                                    <td class="w-32 py-1.5 align-top font-medium text-ink-900">Objek</td>
                                                    <td class="py-1.5 align-top text-slate-400">:</td>
                                                    <td class="py-1.5 align-top text-slate-600">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                                                </tr>
                                                @foreach ($log->properties as $key => $value)
                                                    <tr>
                                                        <td class="py-1.5 align-top font-medium text-ink-900">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                                        <td class="py-1.5 align-top text-slate-400">:</td>
                                                        <td class="py-1.5 align-top text-slate-600">{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="mt-5 flex justify-end">
                                            <button type="button" @click="$refs['detail-{{ $log->id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                        </div>
                                    </div>
                                </dialog>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada riwayat aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($activities->hasPages())
        <div class="mt-4">{{ $activities->links() }}</div>
    @endif
@endsection