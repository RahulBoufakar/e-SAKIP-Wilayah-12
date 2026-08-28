@extends('validator.layout.app')

@section('title', 'Detail Pelaporan Kegiatan')
@section('subtitle', $programKerja->kode_proker ?? '—')

@section('content')
    <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => $tab]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Pelaporan Kegiatan
    </a>

    <div
        x-data="{
            modalValidasiOpen: {{ $errors->any() ? 'true' : 'false' }},
            modalPreviewOpen: false,
            dok: { id: null, nama_dokumen: '', status_validasi: 'menunggu_validasi', catatan_revisi: null },
            previewLoading: false,
            previewError: null,
            openValidasi(d) {
                this.dok = { ...d, status_validasi: d.status_validasi === 'belum_diunggah' ? 'menunggu_validasi' : d.status_validasi };
                this.modalValidasiOpen = true;
            },
            async openPreview(id) {
                this.previewError = null;
                this.previewLoading = true;
                try {
                    const res = await fetch('{{ url('validator/pelaporan-kegiatan/dokumen') }}/' + id + '/preview', { headers: { 'Accept': 'application/json' } });
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

        @if ($laporan->semua_dokumen_disetujui && ! $laporan->is_locked)
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-700">
                <p class="font-semibold">Seluruh dokumen telah disetujui</p>
                <p class="mt-1">Segera kunci laporan ini untuk mencegah perubahan status validasi yang tidak diinginkan di kemudian hari.</p>
            </div>
        @endif

        @if ($laporan->is_locked)
            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                <p class="text-sm font-medium text-slate-600">Laporan ini terkunci. Status validasi dokumen tidak dapat diubah, namun Anda tetap dapat melihat dan mengunduh dokumen.</p>
            </div>
        @endif

        <div class="mt-5 flex items-center justify-between">
            <p class="text-sm font-semibold text-ink-900">Dokumen Laporan Kegiatan</p>
            <form method="POST" action="{{ route('validator.pelaporan-kegiatan.toggle-kunci', $laporan->id) }}">
                @csrf
                @method('PUT')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition-colors
                               {{ $laporan->is_locked ? 'border-brand-200 text-brand-700 hover:bg-brand-50' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        @if ($laporan->is_locked)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        @endif
                    </svg>
                    {{ $laporan->is_locked ? 'Buka Kunci' : 'Kunci Laporan' }}
                </button>
            </form>
        </div>

        <div class="mt-3 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-14 px-4 py-3 font-semibold">No</th>
                        <th class="px-4 py-3 font-semibold">Nama Dokumen</th>
                        <th class="w-44 px-4 py-3 text-center font-semibold">Status Validasi</th>
                        <th class="w-32 px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($laporan->dokumen as $i => $dok)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }}">
                            <td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $dok->nama_dokumen }}</td>
                            <td class="px-4 py-3 text-center"><x-status-badge :status="$dok->status_validasi" /></td>
                            <td class="px-4 py-3 text-center">
                                @if ($dok->status_validasi === 'belum_diunggah')
                                    <span class="text-xs text-slate-400">Belum ada file</span>
                                @elseif ($laporan->is_locked)
                                    <button type="button" @click="openPreview({{ $dok->id }})" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Lihat/Unduh</button>
                                @else
                                    <button type="button"
                                            @click="openValidasi({ id: {{ $dok->id }}, nama_dokumen: @js($dok->nama_dokumen), status_validasi: @js($dok->status_validasi), catatan_revisi: @js($dok->catatan_revisi) })"
                                            class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                        Validasi
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-400">
                                Tim Kerja belum menambahkan dokumen untuk kegiatan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal Validasi --}}
        <div x-show="modalValidasiOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalValidasiOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalValidasiOpen = false"></div>
            <div x-show="modalValidasiOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="'Validasi — ' + dok.nama_dokumen"></h3>
                    <button type="button" @click="modalValidasiOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <div class="px-6 py-4">
                    <button type="button" @click="openPreview(dok.id)" class="mb-4 inline-flex items-center gap-1.5 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100">
                        <span x-show="!previewLoading">Pratinjau / Unduh Dokumen</span>
                        <span x-show="previewLoading">Memuat...</span>
                    </button>
                    <p x-show="previewError" x-text="previewError" x-cloak class="mb-3 text-xs font-medium text-rose-600"></p>

                    <form :action="'{{ url('validator/pelaporan-kegiatan/dokumen') }}/' + dok.id + '/validasi'" method="POST">
                        @csrf
                        @method('PUT')

                        <label class="block text-sm font-medium text-ink-900">Status Validasi</label>
                        <select name="status_validasi" x-model="dok.status_validasi" required
                                class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="menunggu_validasi">Draft</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                        </select>

                        <div class="mt-3" x-show="dok.status_validasi === 'ditolak'" x-cloak>
                            <label class="block text-sm font-medium text-ink-900">Catatan Revisi</label>
                            <textarea name="catatan_revisi" x-model="dok.catatan_revisi" rows="3" :required="dok.status_validasi === 'ditolak'"
                                      class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" @click="modalValidasiOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Preview PDF --}}
        <div x-show="modalPreviewOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4">
            <div x-show="modalPreviewOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalPreviewOpen = false"></div>
            <div x-show="modalPreviewOpen" x-transition class="relative flex h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <p class="text-sm font-semibold text-ink-900" x-text="dok.nama_dokumen"></p>
                    <button type="button" @click="modalPreviewOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <iframe x-ref="previewFrame" class="w-full flex-1"></iframe>
            </div>
        </div>
    </div>
@endsection