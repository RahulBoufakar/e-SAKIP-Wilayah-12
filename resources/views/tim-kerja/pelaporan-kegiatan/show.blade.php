@extends('tim-kerja.layout.app')

@section('title', 'Detail Pelaporan Kegiatan')
@section('subtitle', $programKerja->kode_proker ?? '—')

@section('content')
    <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => $tab]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Pelaporan Kegiatan
    </a>

    @php
        $dokumenByNama = $laporan->dokumen->keyBy('nama_dokumen');
        $dokumenKustomById = $dokumenKustomExisting->keyBy('id');
    @endphp

    <div
        x-data="{
            modalPilihOpen: {{ $errors->any() ? 'true' : 'false' }},
            modalUploadOpen: false,
            modalPreviewOpen: false,
            customList: [''],
            upload: { id: null, nama_dokumen: '', status_validasi: 'belum_diunggah', catatan_revisi: null },
            previewLoading: false,
            previewError: null,
            previewLabel: '',
            addCustom() { this.customList.push(''); },
            removeCustom(i) { this.customList.splice(i, 1); },
            openUpload(dok) { this.upload = dok; this.modalUploadOpen = true; },
            async openPreview(id, label) {
                this.previewError = null;
                this.previewLoading = true;
                this.previewLabel = label;
                try {
                    const res = await fetch('{{ url('tim-kerja/pelaporan-kegiatan/dokumen') }}/' + id + '/preview', { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) throw new Error('Gagal memuat pratinjau.');
                    const data = await res.json();
                    const bytes = Uint8Array.from(atob(data.base64), c => c.charCodeAt(0));
                    const blob = new Blob([bytes], { type: data.mime });
                    this.$refs.previewFrame.src = URL.createObjectURL(blob);
                    this.modalPreviewOpen = true;
                } catch (e) {
                    this.previewError = e.message;
                } finally {
                    this.previewLoading = false;
                }
            },
        }"
        class="mt-4"
    >
        {{-- Informasi ringkasan --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="w-48 px-5 py-3 font-medium text-ink-900">Judul Laporan</td>
                        <td class="px-5 py-3 text-slate-600">{{ $programKerja->usulanProgramKerja->nama_usulan ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="w-48 px-5 py-3 font-medium text-ink-900">Kode Proker</td>
                        <td class="px-5 py-3 font-mono text-xs font-semibold text-brand-700">{{ $programKerja->kode_proker ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="w-48 px-5 py-3 font-medium text-ink-900">IKU</td>
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs font-semibold text-brand-700">
                                <x-truncate-cell id="show-iku" :short="$programKerja->usulanProgramKerja->iku->kode ?? '—'" :text="$programKerja->usulanProgramKerja->iku->deskripsi ?? '—'" />
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="w-48 px-5 py-3 font-medium text-ink-900">Tim Kerja</td>
                        <td class="px-5 py-3 text-slate-600">{{ $programKerja->usulanProgramKerja->iku->timKerja->nama_tim ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($laporan->is_locked)
            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-slate-700">Laporan ini telah dikunci</p>
                    <p class="mt-1 text-sm text-slate-500">Validator telah mengunci laporan kegiatan ini. Anda tidak dapat lagi menambah, mengubah, atau mengunggah dokumen.</p>
                </div>
            </div>
        @endif

        @unless ($laporan->is_locked)
            <div class="mt-5 flex justify-end">
                <button type="button" @click="modalPilihOpen = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card hover:bg-brand-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Dokumen
                </button>
            </div>
        @endunless

        <div class="mt-3 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-14 px-4 py-3 font-semibold">No</th>
                        <th class="px-4 py-3 font-semibold">Nama Dokumen</th>
                        <th class="w-44 px-4 py-3 text-center font-semibold">Status Validasi</th>
                        <th class="w-48 px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($laporan->dokumen as $i => $dok)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }}">
                            <td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $dok->nama_dokumen }}</td>
                            <td class="px-4 py-3 text-center"><x-status-badge :status="$dok->status_validasi" /></td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if ($dok->file_dokumen)
                                        <button type="button" @click="openPreview({{ $dok->id }}, @js($dok->nama_dokumen))" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Lihat/Unduh</button>
                                    @endif

                                    @if (! $laporan->is_locked && $dok->status_validasi !== 'disetujui')
                                        <button type="button"
                                                @click="openUpload({ id: {{ $dok->id }}, nama_dokumen: @js($dok->nama_dokumen), status_validasi: @js($dok->status_validasi), catatan_revisi: @js($dok->catatan_revisi) })"
                                                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                            {{ $dok->file_dokumen ? 'Upload Ulang' : 'Upload Dokumen' }}
                                        </button>
                                    @elseif (! $dok->file_dokumen)
                                        <span class="text-xs text-slate-400">Belum ada file</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-400">
                                Belum ada dokumen. Klik "Tambah Dokumen" untuk memilih dokumen yang perlu diunggah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal: Pilih Dokumen (Step 1) --}}
        <div x-show="modalPilihOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalPilihOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalPilihOpen = false"></div>
            <div x-show="modalPilihOpen" x-transition class="relative flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900">Tambah Dokumen</h3>
                    <button type="button" @click="modalPilihOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('tim-kerja.pelaporan-kegiatan.dokumen.store', $laporan->id) }}" class="flex flex-1 flex-col overflow-hidden">
                    @csrf
                    <div class="flex-1 space-y-2 overflow-y-auto px-6 py-4">
                        <p class="text-sm font-medium text-ink-900">Dokumen Standar</p>
                        @foreach ($dokumenStandar as $nama)
                            @php
                                $existing = $dokumenByNama->get($nama);
                                $locked = $existing && $existing->status_validasi === 'disetujui';
                            @endphp
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 {{ $locked ? 'opacity-60' : '' }}">
                                <input type="checkbox" name="dokumen_standar[]" value="{{ $nama }}"
                                       @checked($existing) @disabled($locked)>
                                <span class="flex-1">{{ $nama }}</span>
                                @if ($locked)
                                    <span class="text-[10px] font-semibold text-slate-400">Terkunci</span>
                                @endif
                            </label>
                        @endforeach

                        @if ($dokumenKustomExisting->isNotEmpty())
                            <p class="pt-3 text-sm font-medium text-ink-900">Dokumen Kustom Sebelumnya</p>
                            @foreach ($dokumenKustomExisting as $dok)
                                @php $locked = $dok->status_validasi === 'disetujui'; @endphp
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 {{ $locked ? 'opacity-60' : '' }}">
                                    <input type="checkbox" name="dokumen_kustom_id[]" value="{{ $dok->id }}" checked @disabled($locked)>
                                    <span class="flex-1">{{ $dok->nama_dokumen }}</span>
                                    @if ($locked)
                                        <span class="text-[10px] font-semibold text-slate-400">Terkunci</span>
                                    @endif
                                </label>
                            @endforeach
                        @endif

                        <div class="pt-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-ink-900">Tambah Dokumen Lainnya</p>
                                <button type="button" @click="addCustom()" class="text-xs font-semibold text-brand-700 hover:underline">+ Tambah</button>
                            </div>
                            <template x-for="(item, i) in customList" :key="i">
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="text" :name="'dokumen_lainnya[' + i + ']'" x-model="customList[i]" placeholder="Nama dokumen kustom"
                                           class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                    <button type="button" @click="removeCustom(i)" x-show="customList.length > 1" class="shrink-0 text-slate-400 hover:text-rose-600">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="modalPilihOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Upload Dokumen (Step 2) --}}
        <div x-show="modalUploadOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalUploadOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalUploadOpen = false"></div>
            <div x-show="modalUploadOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="'Upload — ' + upload.nama_dokumen"></h3>
                    <button type="button" @click="modalUploadOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <div class="px-6 py-4">
                    <template x-if="upload.status_validasi === 'ditolak' && upload.catatan_revisi">
                        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                            <p class="font-semibold">Catatan Revisi:</p>
                            <p class="mt-1" x-text="upload.catatan_revisi"></p>
                        </div>
                    </template>

                    <form :action="'{{ url('tim-kerja/pelaporan-kegiatan/dokumen') }}/' + upload.id + '/upload'" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <label class="block text-sm font-medium text-ink-900">File Dokumen (PDF, maks. 5 MB)</label>
                        <input type="file" name="file_dokumen" accept="application/pdf" required
                               class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">

                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" @click="modalUploadOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Preview PDF --}}
        <div x-show="modalPreviewOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4">
            <div x-show="modalPreviewOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalPreviewOpen = false"></div>
            <div x-show="modalPreviewOpen" x-transition class="relative flex h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <p class="text-sm font-semibold text-ink-900" x-text="previewLabel"></p>
                    <button type="button" @click="modalPreviewOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <iframe x-ref="previewFrame" class="w-full flex-1"></iframe>
            </div>
        </div>
    </div>
@endsection