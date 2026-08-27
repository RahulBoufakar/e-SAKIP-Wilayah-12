@extends('tim-kerja.layout.app')

@section('title', 'Detail Capaian Kinerja')
@section('subtitle', $iku->kode.' — '.$triwulan->kode)

@section('content')
    <a href="{{ route('tim-kerja.capaian-kinerja.index', ['triwulan' => $triwulan->kode]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Capaian Kinerja
    </a>

    @php $locked = $capaian->isFieldLocked(); @endphp

    {{-- Info + indikator status (poin 1: state terlihat jelas oleh Tim Kerja) --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
        <div>
            <p class="font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }} — {{ $triwulan->kode }}</p>
            <h2 class="mt-1 text-lg font-bold text-ink-900">{{ $iku->deskripsi }}</h2>
        </div>
        <x-status-badge :status="$capaian->status" />
    </div>

    <x-workflow.revision-banner :status="$capaian->status" :catatan-revisi="$capaian->catatan_revisi" class="mt-4" />

    <div class="mt-4 rounded-2xl bg-white p-6 shadow-card">
        <h3 class="text-sm font-bold text-ink-900">Nilai Capaian</h3>

        @if ($locked)
            {{-- Mode Read-Only: tabel teks rapi, aktif saat menunggu_validasi/disetujui --}}
            <table class="mt-4 w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @if ($formula)
                        <tr>
                            <td class="w-64 py-2 text-left align-top font-medium text-ink-900">Formula</td>
                            <td class="w-4 py-2 text-left align-top text-slate-400">:</td>
                            <td class="py-2 text-left align-top text-slate-600">{{ $formula->description() }}</td>
                        </tr>
                        @foreach ($formula->variables() as $var)
                            <tr>
                                <td class="py-2 text-left align-top font-medium text-ink-900">{{ $var['label'] }}</td>
                                <td class="py-2 text-left align-top text-slate-400">:</td>
                                <td class="py-2 text-left align-top text-slate-600">{{ $capaian->variabel[$var['key']] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td class="py-2 text-left align-top font-medium text-ink-900">Realisasi</td>
                        <td class="py-2 text-left align-top text-slate-400">:</td>
                        <td class="py-2 text-left align-top font-semibold text-ink-900">{{ $capaian->realisasi ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            {{-- Mode Form Input/Edit: aktif saat draft/ditolak --}}
            <form method="POST" action="{{ route('tim-kerja.capaian-kinerja.update', [$iku->id, $triwulan->id]) }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')

                @if ($formula)
                    <div class="rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-700">
                        Formula: {{ $formula->description() }}
                    </div>
                    @foreach ($formula->variables() as $var)
                        <x-form.input
                            :label="$var['label']"
                            name="variabel[{{ $var['key'] }}]"
                            type="number"
                            step="0.01"
                            min="0"
                            value="{{ old('variabel.'.$var['key'], $capaian->variabel[$var['key']] ?? '') }}"
                            required
                        />
                    @endforeach
                    <p class="text-xs text-slate-400">Realisasi dihitung otomatis dari nilai di atas.</p>
                @else
                    <x-form.input
                        label="Realisasi"
                        name="realisasi"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('realisasi', $capaian->realisasi) }}"
                        required
                    />
                @endif

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Dokumen — tetap sama seperti sebelumnya, gate upload/hapus via $locked --}}
    <div
        x-data="{
            rows: [{ nama_dokumen: '' }],
            addRow() { this.rows.push({ nama_dokumen: '' }); },
            removeRow(i) { this.rows.splice(i, 1); },
        }"
        class="mt-4 rounded-2xl bg-white p-6 shadow-card"
    >
        <h3 class="text-sm font-bold text-ink-900">Dokumen Bukti Capaian</h3>

        <div class="mt-3 divide-y divide-slate-100">
            @forelse ($capaian->dokumen as $dok)
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <span class="min-w-0 truncate text-sm text-ink-900">{{ $dok->nama_dokumen }}</span>
                    <div class="flex shrink-0 items-center gap-3">
                        <x-file-preview
                            :id="'dok-'.$dok->id"
                            :label="$dok->nama_dokumen"
                            :url="$dok->file_dokumen"
                            :preview-url="route('tim-kerja.capaian-kinerja.dokumen.preview', $dok->id)"
                            :download-url="route('tim-kerja.capaian-kinerja.dokumen.unduh', $dok->id)"
                            :hide-label="true"
                        />
                        @unless ($locked)
                            <button type="button" @click="$refs['confirm-dok-{{ $dok->id }}'].showModal()" class="text-xs font-semibold text-rose-600 hover:underline">Hapus</button>
                        @endunless
                    </div>
                </div>

                @unless ($locked)
                    @include('admin.layout.confirm-delete', [
                        'refName' => 'confirm-dok-'.$dok->id,
                        'action' => route('tim-kerja.capaian-kinerja.dokumen.destroy', $dok->id),
                        'label' => 'dokumen "'.$dok->nama_dokumen.'"',
                    ])
                @endunless
            @empty
                <p class="py-4 text-sm text-slate-400">Belum ada dokumen.</p>
            @endforelse
        </div>

        @unless ($locked)
            <form method="POST" action="{{ route('tim-kerja.capaian-kinerja.dokumen.store', $capaian->id) }}" enctype="multipart/form-data" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                @csrf
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex items-start gap-2">
                        <input type="text" :name="'dokumen[' + i + '][nama_dokumen]'" x-model="row.nama_dokumen" placeholder="Nama dokumen"
                               class="flex-1 rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <input type="file" :name="'dokumen[' + i + '][file]'" accept="application/pdf"
                               class="flex-1 rounded-lg border-slate-200 text-sm">
                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1" class="mt-2 shrink-0 text-slate-400 hover:text-rose-600">&times;</button>
                    </div>
                </template>
                <button type="button" @click="addRow()" class="text-xs font-semibold text-brand-700 hover:underline">+ Tambah Dokumen</button>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Upload</button>
                </div>
            </form>
        @endunless
    </div>

    {{-- Kirim — fallback notification kalau data belum lengkap --}}
    @unless ($locked)
        <div class="mt-4 rounded-2xl bg-white p-5 shadow-card">
            @unless ($capaian->isDataLengkap())
                <p class="mb-3 text-xs font-medium text-amber-700">Lengkapi seluruh nilai variabel/realisasi sebelum dapat mengirim untuk validasi.</p>
            @endunless
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-slate-600">Kirim untuk validasi setelah nilai capaian terisi dan minimal 1 dokumen bukti dilampirkan.</p>
                <form method="POST" action="{{ route('tim-kerja.capaian-kinerja.kirim', $capaian->id) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" {{ $capaian->can_kirim ? '' : 'disabled' }}
                            class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ $capaian->can_kirim ? 'bg-brand-600 hover:bg-brand-700' : 'cursor-not-allowed bg-slate-200 text-slate-400' }}">
                        Kirim
                    </button>
                </form>
            </div>
        </div>
    @endunless
@endsection