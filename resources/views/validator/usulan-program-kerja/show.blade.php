@extends('validator.layout.app')

@section('title', 'Detail Usulan Program Kerja')
@section('subtitle', $usulan->nama_usulan)

@section('content')
    <a href="{{ route('validator.usulan-program-kerja.index', ['status' => $usulan->status_validasi]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali
    </a>

    <div x-data="{ tolakOpen: false }" class="mt-4 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <p class="font-mono text-xs font-semibold text-brand-700">{{ $usulan->iku->kode }} — Tahun {{ $usulan->tahun }}</p>
                <h2 class="mt-1 text-lg font-bold text-ink-900">{{ $usulan->nama_usulan }}</h2>
                <p class="mt-1 text-xs text-slate-400">Tim Kerja: {{ $usulan->iku->timKerja->nama_tim ?? '—' }}</p>
            </div>
            <x-status-badge :status="$usulan->status_validasi" />
        </div>

        @if ($usulan->status_validasi === 'rejected' && $usulan->catatan_revisi)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                <p class="font-semibold">Catatan Revisi:</p>
                <p class="mt-1">{{ $usulan->catatan_revisi }}</p>
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-card">
            <h3 class="text-sm font-bold text-ink-900">Deskripsi</h3>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $usulan->deskripsi ?: '—' }}</p>

            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-file-preview id="kak-{{ $usulan->id }}" label="File KAK (PDF)" :url="$usulan->file_kak_pdf"
                    :preview-url="route('validator.usulan-program-kerja.file.preview', [$usulan->id, 'kak'])"
                    :download-url="route('validator.usulan-program-kerja.file.unduh', [$usulan->id, 'kak'])" />

                <x-file-preview id="rab-pdf-{{ $usulan->id }}" label="File RAB (PDF)" :url="$usulan->file_rab_pdf"
                    :preview-url="route('validator.usulan-program-kerja.file.preview', [$usulan->id, 'rab-pdf'])"
                    :download-url="route('validator.usulan-program-kerja.file.unduh', [$usulan->id, 'rab-pdf'])" />

                <div>
                    <label class="block text-sm font-medium text-ink-900">File RAB (Excel)</label>
                    @if ($usulan->file_rab_excel)
                        <a href="{{ route('validator.usulan-program-kerja.file.unduh', [$usulan->id, 'rab-excel']) }}" class="mt-1 block truncate text-xs font-medium text-brand-700 hover:underline">Unduh</a>
                    @else
                        <p class="mt-1 text-xs text-slate-400">Belum ada file.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-card">
            <h3 class="text-sm font-bold text-ink-900">Detail Kegiatan</h3>
            @if ($usulan->detailKegiatan)
                <div class="mt-4 space-y-2 text-sm">
                    <p><span class="font-medium text-ink-900">Nama Detail:</span> <span class="text-slate-600">{{ $usulan->detailKegiatan->nama_detail }}</span></p>
                    <p><span class="font-medium text-ink-900">Tempat Pelaksanaan:</span> <span class="text-slate-600">{{ $usulan->detailKegiatan->tempat_pelaksanaan }}</span></p>
                    <p><span class="font-medium text-ink-900">Bentuk Kegiatan:</span> <span class="text-slate-600">{{ $usulan->detailKegiatan->bentuk_kegiatan }}</span></p>
                    <p><span class="font-medium text-ink-900">Bulan Kegiatan:</span> <span class="text-slate-600">{{ collect($usulan->detailKegiatan->bulan_kegiatan)->map(fn ($b) => $bulanIndo[$b])->join(', ') }}</span></p>
                    <p><span class="font-medium text-ink-900">Anggaran:</span> <span class="text-slate-600">Rp {{ number_format($usulan->detailKegiatan->anggaran, 0, ',', '.') }}</span></p>
                </div>
            @else
                <p class="mt-2 text-sm text-slate-400">Belum ada Detail Kegiatan.</p>
            @endif
        </div>

        @if ($usulan->status_validasi === 'menunggu_validasi')
            <div class="flex justify-end gap-3 rounded-2xl bg-white p-5 shadow-card">
                <button type="button" @click="tolakOpen = true" class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Tolak</button>
                <form method="POST" action="{{ route('validator.usulan-program-kerja.setujui', $usulan->id) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Setujui</button>
                </form>
            </div>
        @endif

        <div x-show="tolakOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="tolakOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="tolakOpen = false"></div>
            <div x-show="tolakOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900">Tolak Usulan</h3>
                    <button type="button" @click="tolakOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('validator.usulan-program-kerja.tolak', $usulan->id) }}" class="px-6 py-4">
                    @csrf
                    @method('PUT')
                    <x-form.textarea label="Catatan Revisi" name="catatan_revisi" :rows="4" required />
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="tolakOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection