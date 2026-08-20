@extends('tim-kerja.layout.app')

@section('title', 'Usulan Program Kerja')
@section('subtitle', $usulan->nama_usulan)

@section('content')
    <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => $usulan->tahun]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Usulan Program Kerja
    </a>

    @php
        $locked = $usulan->isFieldLocked();
        $detail = $usulan->detailKegiatan;
        $detailEditDisabled = $locked;
    @endphp

    <div class="mt-4 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <p class="font-mono text-xs font-semibold text-brand-700">{{ $usulan->iku->kode }} — Tahun {{ $usulan->tahun }}</p>
                <h2 class="mt-1 text-lg font-bold text-ink-900">{{ $usulan->nama_usulan }}</h2>
            </div>
            <x-status-badge :status="$usulan->status_validasi" />
        </div>

        @if ($usulan->isFieldLocked())
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm font-medium text-slate-600">
                Usulan ini berstatus "{{ $usulan->status_validasi }}" dan sedang terkunci.
            </div>
        @endif

        @if ($usulan->status_validasi === 'rejected' && $usulan->catatan_revisi)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                <p class="font-semibold">Catatan Revisi:</p>
                <p class="mt-1">{{ $usulan->catatan_revisi }}</p>
            </div>
        @endif

        <div
            x-data="{ form: { nama_usulan: @js(old('nama_usulan', $usulan->nama_usulan)), deskripsi: @js(old('deskripsi', $usulan->deskripsi)) } }"
            class="rounded-2xl bg-white p-6 shadow-card"
        >
            <form method="POST" action="{{ route('tim-kerja.usulan-program-kerja.update', $usulan->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <fieldset {{ $locked ? 'disabled' : '' }} class="space-y-3">
                    <x-form.input label="Nama Usulan" name="nama_usulan" type="text" maxlength="255" x-model="form.nama_usulan" required />
                    <x-form.textarea label="Deskripsi" name="deskripsi" :rows="3" x-model="form.deskripsi" />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-file-preview id="kak-{{ $usulan->id }}" label="File KAK (PDF)" :url="$usulan->file_kak_pdf"
                            :preview-url="route('tim-kerja.usulan-program-kerja.file.preview', [$usulan->id, 'kak'])"
                            :download-url="route('tim-kerja.usulan-program-kerja.file.unduh', [$usulan->id, 'kak'])" />

                        <x-file-preview id="rab-pdf-{{ $usulan->id }}" label="File RAB (PDF)" :url="$usulan->file_rab_pdf"
                            :preview-url="route('tim-kerja.usulan-program-kerja.file.preview', [$usulan->id, 'rab-pdf'])"
                            :download-url="route('tim-kerja.usulan-program-kerja.file.unduh', [$usulan->id, 'rab-pdf'])" />

                        <div>
                            <label class="block text-sm font-medium text-ink-900">File RAB (Excel)</label>
                            @if ($usulan->file_rab_excel)
                                <a href="{{ route('tim-kerja.usulan-program-kerja.file.unduh', [$usulan->id, 'rab-excel']) }}" class="mt-1 block truncate text-xs font-medium text-brand-700 hover:underline">Unduh (pratinjau tidak didukung untuk Excel)</a>
                            @else
                                <p class="mt-1 text-xs text-slate-400">Belum ada file.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-ink-900">Ganti File KAK</label>
                            <input type="file" name="file_kak_pdf" accept="application/pdf" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                            @error('file_kak_pdf')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-900">Ganti File RAB (PDF)</label>
                            <input type="file" name="file_rab_pdf" accept="application/pdf" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                            @error('file_rab_pdf')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink-900">Ganti File RAB (Excel)</label>
                            <input type="file" name="file_rab_excel" accept=".xls,.xlsx" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                            @error('file_rab_excel')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </fieldset>

                @unless ($locked)
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan Perubahan</button>
                    </div>
                @endunless
            </form>
        </div>

        @unless ($locked)
            <div class="flex items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-card">
                <p class="text-sm text-slate-600">Kirim untuk validasi setelah 3 file lengkap dan Detail Kegiatan sudah diisi.</p>
                <form method="POST" action="{{ route('tim-kerja.usulan-program-kerja.kirim', $usulan->id) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" {{ $usulan->can_kirim ? '' : 'disabled' }}
                            class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ $usulan->can_kirim ? 'bg-brand-600 hover:bg-brand-700' : 'cursor-not-allowed bg-slate-200 text-slate-400' }}">
                        Kirim
                    </button>
                </form>
            </div>
        @endunless

        <div
            x-data="{
                editing: {{ (! $detail && ! $detailEditDisabled) ? 'true' : 'false' }},
                raw: {{ $detail->anggaran ?? 0 }},
                display: '',
                init() { this.display = this.format(this.raw); },
                format(v) { v = String(v).replace(/\D/g, ''); return v.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); },
                onInput(e) { this.raw = e.target.value.replace(/\./g, ''); this.display = this.format(this.raw); },
            }"
            class="rounded-2xl bg-white p-6 shadow-card"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-ink-900">Detail Kegiatan</h3>
                <button type="button" @click="editing = true" x-show="!editing" {{ $detailEditDisabled ? 'disabled' : '' }}
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $detailEditDisabled ? 'cursor-not-allowed text-slate-300' : 'text-brand-700 hover:bg-brand-50' }}">
                    Edit
                </button>
            </div>

            <template x-if="!editing">
                <div class="mt-4 space-y-2 text-sm">
                    @if ($detail)
                        <p><span class="font-medium text-ink-900">Nama Detail:</span> <span class="text-slate-600">{{ $detail->nama_detail }}</span></p>
                        <p><span class="font-medium text-ink-900">Tempat Pelaksanaan:</span> <span class="text-slate-600">{{ $detail->tempat_pelaksanaan }}</span></p>
                        <p><span class="font-medium text-ink-900">Bentuk Kegiatan:</span> <span class="text-slate-600">{{ $detail->bentuk_kegiatan }}</span></p>
                        <p><span class="font-medium text-ink-900">Bulan Kegiatan:</span> <span class="text-slate-600">{{ collect($detail->bulan_kegiatan)->map(fn ($b) => $bulanIndo[$b])->join(', ') }}</span></p>
                        <p><span class="font-medium text-ink-900">Anggaran:</span> <span class="text-slate-600">Rp {{ number_format($detail->anggaran, 0, ',', '.') }}</span></p>
                    @else
                        <p class="text-slate-400">Belum ada Detail Kegiatan.</p>
                    @endif
                </div>
            </template>

            <template x-if="editing">
                <form method="POST" action="{{ route('tim-kerja.usulan-program-kerja.detail.store-or-update', $usulan->id) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')

                    <x-form.input label="Nama Detail" name="nama_detail" type="text" maxlength="255" value="{{ old('nama_detail', $detail->nama_detail ?? '') }}" required />
                    <x-form.input label="Tempat Pelaksanaan" name="tempat_pelaksanaan" type="text" maxlength="255" value="{{ old('tempat_pelaksanaan', $detail->tempat_pelaksanaan ?? '') }}" required />
                    <x-form.input label="Bentuk Kegiatan" name="bentuk_kegiatan" type="text" maxlength="255" value="{{ old('bentuk_kegiatan', $detail->bentuk_kegiatan ?? '') }}" required />

                    <div>
                        <label class="block text-sm font-medium text-ink-900">Bulan Kegiatan</label>
                        <div class="mt-1.5 grid grid-cols-4 gap-2 sm:grid-cols-6">
                            @foreach ($bulanIndo as $angka => $nama)
                                @continue($angka === 0)
                                <label class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-600">
                                    <input type="checkbox" name="bulan_kegiatan[]" value="{{ $angka }}" @checked(in_array($angka, old('bulan_kegiatan', $detail->bulan_kegiatan ?? [])))>
                                    {{ $nama }}
                                </label>
                            @endforeach
                        </div>
                        @error('bulan_kegiatan')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink-900">Anggaran</label>
                        <div class="relative mt-1.5">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
                            <input type="text" x-model="display" @input="onInput" inputmode="numeric"
                                   class="w-full rounded-lg border-slate-200 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <input type="hidden" name="anggaran" :value="raw">
                        @error('anggaran')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        @if ($detail)
                            <button type="button" @click="editing = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        @endif
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
@endsection