@extends('admin.layout.app')

@section('title', 'Dokumentasi')
@section('subtitle', 'Dokumen yang diunggah Tim Kerja (hanya lihat & unduh)')

@section('content')
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[14rem_minmax(0,1fr)]">

        {{-- Kiri: kategori (pill horizontal di mobile) --}}
        <nav class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:self-start lg:overflow-visible lg:rounded-2xl lg:bg-white lg:p-3 lg:pb-3 lg:shadow-card">
            @foreach ($kategoriList as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['kategori' => $key, 'page' => null]) }}"
                   class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition-colors lg:w-full lg:rounded-lg
                          {{ $kategori === $key ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-brand-50 hover:text-brand-700 lg:bg-transparent' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        {{-- Kanan: filter + tabel --}}
        <div class="min-w-0 space-y-3">
            @include('admin.dokumentasi._filter')

            <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-ink-900 text-white">
                            <th class="px-4 py-3 font-semibold">Nama Dokumen</th>
                            <th class="px-4 py-3 font-semibold">IKU</th>
                            <th class="px-4 py-3 font-semibold">Tim Kerja</th>
                            <th class="px-4 py-3 font-semibold">Triwulan</th>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 text-center font-semibold">Status</th>
                            <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($dokumen as $r)
                            <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                                <td class="max-w-[16rem] px-4 py-3">
                                    <p class="break-words font-medium text-ink-900">{{ $r['nama'] }}</p>
                                    @if ($r['konteks'])
                                        <p class="mt-0.5 break-words text-xs text-slate-400">{{ $r['konteks'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($r['iku'])
                                        <span class="font-mono text-xs font-semibold text-brand-700" title="{{ $r['iku_deskripsi'] }}">{{ $r['iku'] }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $r['tim'] ? implode(', ', $r['tim']) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['triwulan'] ? implode(', ', $r['triwulan']) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $r['tanggal']?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($r['status'])
                                        <x-status-badge :status="$r['status']" />
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($r['preview_url'])
                                        <x-file-preview
                                            :id="$r['key']"
                                            :label="$r['nama']"
                                            :url="$r['download_url']"
                                            :preview-url="$r['preview_url']"
                                            :download-url="$r['download_url']"
                                            :hide-label="true"
                                        />
                                    @else
                                        {{-- RAB Excel: hanya unduh --}}
                                        <a href="{{ $r['download_url'] }}" class="text-xs font-medium text-brand-700 hover:underline">Unduh</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">Belum ada dokumen yang sesuai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($dokumen->hasPages())
                <div>{{ $dokumen->links() }}</div>
            @endif
        </div>
    </div>
@endsection