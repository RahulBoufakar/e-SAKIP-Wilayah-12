@extends('tim-kerja.layout.app')

@section('title', 'Data Proker')
@section('subtitle', 'Program Kerja Tim Kerja Anda yang telah disetujui')

@section('content')
    <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}"
           class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                  {{ $tab === 'berjalan' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
            Data Proker Tahun Ini ({{ $activeTahun }})
        </a>
        @if ($nextYearAvailable)
            <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'h_plus_1']) }}"
               class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                      {{ $tab === 'h_plus_1' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                Data Proker Tahun Depan ({{ $nextYear }})
            </a>
        @else
            <span class="flex-1 cursor-not-allowed px-4 py-3 text-center text-sm font-semibold text-slate-300">
                Data Proker Tahun Depan ({{ $nextYear }}) — Belum tersedia
            </span>
        @endif
    </div>

    <div class="overflow-x-auto rounded-b-2xl bg-white shadow-card">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-28 px-3 py-2.5 font-semibold">Tim Kerja</th>
                    <th class="w-48 px-3 py-2.5 font-semibold">Nama Kegiatan / Proker</th>
                    <th class="w-24 px-3 py-2.5 font-semibold">IKU / IKK</th>
                    <th class="w-48 px-3 py-2.5 font-semibold">Permasalahan</th>
                    <th class="w-28 px-3 py-2.5 text-right font-semibold">Total Anggaran</th>
                    <th class="w-24 px-3 py-2.5 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($prokerList as $row)
                    @php $detail = $row->detailKegiatan; @endphp
                    <tr id="proker-{{ $row->id }}" class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-3 py-2 text-slate-600">{{ $row->iku->timKerja->nama_tim ?? '—' }}</td>
                        <td class="max-w-[12rem] px-3 py-2">
                            <x-truncate-cell :id="'nama-'.$row->id" :text="$row->nama_usulan" />
                        </td>
                        <td class="max-w-[8rem] px-3 py-2">
                            <span class="font-mono text-[11px] font-semibold text-brand-700">
                                <x-truncate-cell :id="'iku-'.$row->id" :short="$row->iku->kode ?? '—'" :text="$row->iku->deskripsi ?? '—'" />
                            </span>
                        </td>
                        <td class="max-w-[12rem] px-3 py-2">
                            <x-truncate-cell :id="'masalah-'.$row->id" :text="$row->permasalahan ?: '—'" />
                        </td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ $detail ? 'Rp '.number_format($detail->anggaran, 0, ',', '.') : '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            <div x-data class="flex items-center justify-center gap-1.5">
                                <button type="button" @click="$refs['detail-{{ $row->id }}'].showModal()" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">Detail</button>

                                {{-- Tag PTS: khusus Tim Kerja, visibility-only, belum ada logika aksi --}}
                                @if ($detail && $detail->jenis_kegiatan === 'kunjungan_lapangan')
                                    <button type="button" @click="$refs['tag-pts-{{ $row->id }}'].showModal()" class="rounded-lg bg-brand-50 px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-100">Tag PTS</button>
                                @endif

                                <dialog x-ref="detail-{{ $row->id }}" @click.self="$el.close()" class="m-auto w-full max-w-lg rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                    <div class="p-6">
                                        <h3 class="text-sm font-bold text-ink-900">Detail Proker — {{ $row->programKerja->kode_proker ?? '—' }}</h3>

                                        <table class="mt-4 w-full text-xs">
                                            <tbody>
                                                <tr>
                                                    <td class="w-36 py-1.5 text-left align-top font-medium text-ink-900">Tempat Pelaksanaan</td>
                                                    <td class="w-3 py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top text-slate-600">{{ $detail->tempat_pelaksanaan ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-1.5 text-left align-top font-medium text-ink-900">Bentuk Kegiatan</td>
                                                    <td class="py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top text-slate-600">{{ $detail->bentuk_kegiatan ?? '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-1.5 text-left align-top font-medium text-ink-900">Bulan Kegiatan</td>
                                                    <td class="py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top text-slate-600">{{ $detail ? collect($detail->bulan_kegiatan)->map(fn ($b) => $bulanIndo[$b])->join(', ') : '—' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-1.5 text-left align-top font-medium text-ink-900">Jenis Kegiatan</td>
                                                    <td class="py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top text-slate-600">{{ $detail && $detail->jenis_kegiatan ? ucwords(str_replace('_', ' ', $detail->jenis_kegiatan)) : 'Belum divalidasi' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-1.5 text-left align-top font-medium text-ink-900">File KAK (PDF)</td>
                                                    <td class="py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top">
                                                        <x-file-preview id="kak-{{ $row->id }}" label="File KAK (PDF)" :url="$row->file_kak_pdf"
                                                            :preview-url="route('tim-kerja.usulan-program-kerja.file.preview', [$row->id, 'kak'])"
                                                            :download-url="route('tim-kerja.usulan-program-kerja.file.unduh', [$row->id, 'kak'])"
                                                            :hide-label="true" />
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="py-1.5 text-left align-top font-medium text-ink-900">File RAB (PDF)</td>
                                                    <td class="py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top">
                                                        <x-file-preview id="rab-pdf-{{ $row->id }}" label="File RAB (PDF)" :url="$row->file_rab_pdf"
                                                            :preview-url="route('tim-kerja.usulan-program-kerja.file.preview', [$row->id, 'rab-pdf'])"
                                                            :download-url="route('tim-kerja.usulan-program-kerja.file.unduh', [$row->id, 'rab-pdf'])"
                                                            :hide-label="true" />
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="py-1.5 text-left align-top font-medium text-ink-900">File RAB (Excel)</td>
                                                    <td class="py-1.5 text-left align-top text-slate-400">:</td>
                                                    <td class="py-1.5 text-left align-top">
                                                        @if ($row->file_rab_excel)
                                                            <a href="{{ route('tim-kerja.usulan-program-kerja.file.unduh', [$row->id, 'rab-excel']) }}" class="text-xs font-medium text-brand-700 hover:underline">Download</a>
                                                            <span class="ml-1 text-[11px] text-slate-400">(pratinjau tidak didukung)</span>
                                                        @else
                                                            <span class="text-slate-400">Belum ada file.</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div class="mt-5 flex justify-end">
                                            <button type="button" @click="$refs['detail-{{ $row->id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                        </div>
                                    </div>
                                </dialog>
                                @if ($detail && $detail->jenis_kegiatan === 'kunjungan_lapangan')
                                    <dialog x-ref="tag-pts-{{ $row->id }}" @click.self="$el.close()" class="m-auto w-full max-w-sm rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                        <div class="p-6">
                                            <h3 class="text-sm font-bold text-ink-900">Tagging PTS</h3>
                                            <p class="mt-1 text-xs text-slate-400">{{ $row->programKerja->kode_proker ?? '—' }} — {{ $row->nama_usulan }}</p>

                                            <form method="POST" action="{{ route('tim-kerja.data-proker.tag-pts', $row->id) }}" class="mt-4">
                                                @csrf
                                                @method('PUT')

                                                <div class="max-h-64 space-y-1.5 overflow-y-auto">
                                                    @forelse ($ptsOptions as $pts)
                                                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                                            <input type="checkbox" name="pts_id[]" value="{{ $pts->id }}" @checked($row->pts->contains('id', $pts->id))>
                                                            <span class="font-mono text-xs font-semibold text-brand-700">{{ $pts->kode_pts }}</span>
                                                            <span class="min-w-0 flex-1 truncate">{{ $pts->nama_pts }}</span>
                                                        </label>
                                                    @empty
                                                        <p class="text-xs text-slate-400">Belum ada data PTS.</p>
                                                    @endforelse
                                                </div>

                                                <div class="mt-5 flex justify-end gap-3">
                                                    <button type="button" @click="$refs['tag-pts-{{ $row->id }}'].close()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                                                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </dialog>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada Data Proker yang disetujui untuk Tim Kerja Anda pada tahun ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($prokerList->hasPages())
        <div class="mt-4">{{ $prokerList->links() }}</div>
    @endif
@endsection