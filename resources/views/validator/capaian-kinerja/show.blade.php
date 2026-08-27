@extends('validator.layout.app')

@section('title', 'Detail Capaian Kinerja')
@section('subtitle', $iku->kode.' — '.$triwulan->kode)

@section('content')
    <a href="{{ route('validator.capaian-kinerja.index', ['triwulan' => $triwulan->kode]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Capaian Kinerja
    </a>

    <div x-data="{ tolakOpen: false }" class="mt-4 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <p class="font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }} — {{ $triwulan->kode }}</p>
                <h2 class="mt-1 text-lg font-bold text-ink-900">{{ $iku->deskripsi }}</h2>
                <p class="mt-1 text-xs text-slate-400">Tim Kerja: {{ $iku->timKerja->nama_tim ?? '—' }}</p>
            </div>
            @if ($capaian)
                <x-status-badge :status="$capaian->status" />
            @else
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Belum Diisi</span>
            @endif
        </div>

        @if (! $capaian)
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-400 shadow-card">
                Tim Kerja belum mengisi data Capaian Kinerja untuk IKU dan Triwulan ini.
            </div>
        @else
            @if ($capaian->status === 'ditolak' && $capaian->catatan_revisi)
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                    <p class="font-semibold">Catatan Revisi:</p>
                    <p class="mt-1">{{ $capaian->catatan_revisi }}</p>
                </div>
            @endif

            <div class="rounded-2xl bg-white p-6 shadow-card">
                <h3 class="text-sm font-bold text-ink-900">Nilai Capaian</h3>

                @if ($formula)
                    <div class="mt-3 rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-700">
                        Formula: {{ $formula->description() }}
                    </div>
                    <table class="mt-4 w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($formula->variables() as $var)
                                <tr>
                                    <td class="w-64 py-2 text-left align-top font-medium text-ink-900">{{ $var['label'] }}</td>
                                    <td class="w-4 py-2 text-left align-top text-slate-400">:</td>
                                    <td class="py-2 text-left align-top text-slate-600">{{ $capaian->variabel[$var['key']] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <p class="mt-4 text-sm font-semibold text-ink-900">
                    Realisasi: <span class="font-mono text-brand-700">{{ $capaian->realisasi ?? '—' }}</span>
                </p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-card">
                <h3 class="text-sm font-bold text-ink-900">Dokumen Bukti Capaian</h3>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($capaian->dokumen as $dok)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <span class="min-w-0 truncate text-sm text-ink-900">{{ $dok->nama_dokumen }}</span>
                            <x-file-preview
                                :id="'dok-'.$dok->id"
                                :label="$dok->nama_dokumen"
                                :url="$dok->file_dokumen"
                                :preview-url="route('validator.capaian-kinerja.dokumen.preview', $dok->id)"
                                :download-url="route('validator.capaian-kinerja.dokumen.unduh', $dok->id)"
                                :hide-label="true"
                            />
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-400">Belum ada dokumen.</p>
                    @endforelse
                </div>
            </div>

            @if ($capaian->status === 'menunggu_validasi')
                @unless ($capaian->isDataLengkap())
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-700">
                        Data variabel/realisasi pada capaian ini tidak lengkap. Tombol "Setujui" dinonaktifkan — silakan Tolak agar Tim Kerja dapat melengkapi data.
                    </div>
                @endunless
                <div class="flex justify-end gap-3 rounded-2xl bg-white p-5 shadow-card">
                    <button type="button" @click="tolakOpen = true" class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Tolak</button>
                    <form method="POST" action="{{ route('validator.capaian-kinerja.setujui', $capaian->id) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit" {{ $capaian->isDataLengkap() ? '' : 'disabled' }}
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ $capaian->isDataLengkap() ? 'bg-brand-600 hover:bg-brand-700' : 'cursor-not-allowed bg-slate-200 text-slate-400' }}">
                            Setujui
                        </button>
                    </form>
                </div>
            @endif

            <div x-show="tolakOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div x-show="tolakOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="tolakOpen = false"></div>
                <div x-show="tolakOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-bold text-ink-900">Tolak Capaian Kinerja</h3>
                        <button type="button" @click="tolakOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('validator.capaian-kinerja.tolak', $capaian->id) }}" class="px-6 py-4">
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
        @endif
    </div>
@endsection