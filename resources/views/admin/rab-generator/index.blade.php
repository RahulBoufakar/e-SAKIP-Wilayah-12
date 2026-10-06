@extends('admin.layout.app')

@section('title', 'RAB Generator')
@section('subtitle', 'Kelola file Excel master RAB')

@section('content')
    <form method="POST" action="{{ route('admin.rab-generator.store') }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-5 shadow-card">
        @csrf
        <label class="block text-sm font-medium text-ink-900">Unggah File Master (.xlsx)</label>
        <div class="mt-1.5 flex flex-wrap items-center gap-3">
            <input type="file" name="file" accept=".xlsx" required class="rounded-lg border-slate-200 text-sm">
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Unggah &amp; Analisis</button>
        </div>
        <p class="mt-1 text-xs text-slate-400">Hanya .xlsx, maksimal 20 MB. Sistem mendeteksi struktur tiap sheet; Anda meninjau, mengoreksi, lalu mengaktifkannya. File yang sebelumnya aktif tetap tersimpan.</p>
        @error('file')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    </form>

    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="px-5 py-3 font-semibold">File</th>
                    <th class="w-40 px-5 py-3 font-semibold">Diunggah</th>
                    <th class="w-40 px-5 py-3 font-semibold">Oleh</th>
                    <th class="w-20 px-5 py-3 text-center font-semibold">Sheet</th>
                    <th class="w-28 px-5 py-3 text-center font-semibold">Peringatan</th>
                    <th class="w-28 px-5 py-3 text-center font-semibold">Status</th>
                    <th class="w-24 px-5 py-3 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($daftar as $file)
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $file->nama_file }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $file->tanggal_upload->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $file->pengunggah->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-center text-slate-600">{{ $file->sheets_count }}</td>
                        <td class="px-5 py-3 text-center">
                            @if ($file->jumlah_peringatan > 0)
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ $file->jumlah_peringatan }}</span>
                            @else
                                <span class="text-xs text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if ($file->aktif)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Aktif</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Tidak aktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="{{ route('admin.rab-generator.show', $file) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Tinjau</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada file master. Unggah file Excel master RAB di atas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($daftar->hasPages())
        <div class="mt-4">{{ $daftar->links() }}</div>
    @endif
@endsection
