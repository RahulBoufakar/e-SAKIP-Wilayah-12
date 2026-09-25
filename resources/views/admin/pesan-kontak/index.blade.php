@extends('admin.layout.app')

@section('title', 'Pesan Hubungi Kami')
@section('subtitle', 'Daftar pesan yang masuk dari halaman Hubungi Kami')

@section('content')
    <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl bg-white p-4 shadow-card sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-slate-500">Cari Nama / Email</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="mis. Budi, budi@email.com"
                   class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Tim Kerja</label>
            <select name="tim_kerja_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">Semua Tim Kerja</option>
                @foreach ($timKerjaOptions as $tim)
                    <option value="{{ $tim->id }}" @selected(request('tim_kerja_id') == $tim->id)>{{ $tim->nama_tim }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Terapkan Filter</button>
            @if (request()->anyFilled(['search', 'tim_kerja_id']))
                <a href="{{ route('admin.pesan-kontak.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </div>
    </form>

    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-40 px-5 py-3 font-semibold">Waktu</th>
                    <th class="px-5 py-3 font-semibold">Nama</th>
                    <th class="px-5 py-3 font-semibold">Email</th>
                    <th class="w-40 px-5 py-3 font-semibold">Tim Kerja</th>
                    <th class="w-24 px-5 py-3 text-center font-semibold">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pesanList as $pesan)
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $pesan->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $pesan->nama }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $pesan->email }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $pesan->timKerja->nama_tim ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <div x-data>
                                <button type="button" @click="$refs['detail-{{ $pesan->id }}'].showModal()" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Lihat</button>

                                <dialog x-ref="detail-{{ $pesan->id }}" @click.self="$el.close()" class="m-auto w-full max-w-lg rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                    <div class="p-6">
                                        <div class="relative">
                                            <div>
                                                <h3 class="text-sm font-bold text-ink-900">{{ $pesan->nama }}</h3>
                                                <p class="text-xs text-slate-400">{{ $pesan->email }} &middot; {{ $pesan->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                            <button type="button" @click="$refs['detail-{{ $pesan->id }}'].close()" class="absolute right-0 top-0 text-slate-400 hover:text-slate-600">&times;</button>
                                        </div>

                                        <p class="mt-3 text-xs font-medium text-slate-500">Tim Kerja: {{ $pesan->timKerja->nama_tim ?? '—' }}</p>

                                        <p class="mt-4 whitespace-pre-line text-sm text-ink-900">{{ $pesan->pesan }}</p>

                                        @if ($pesan->gambar_url)
                                            <img src="{{ $pesan->gambar_url }}" alt="Lampiran" class="mt-4 max-h-64 w-full rounded-lg border border-slate-200 object-contain">
                                        @endif

                                        <div class="mt-5 flex justify-end">
                                            <button type="button" @click="$refs['detail-{{ $pesan->id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                        </div>
                                    </div>
                                </dialog>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada pesan masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pesanList->hasPages())
        <div class="mt-4">{{ $pesanList->links() }}</div>
    @endif
@endsection