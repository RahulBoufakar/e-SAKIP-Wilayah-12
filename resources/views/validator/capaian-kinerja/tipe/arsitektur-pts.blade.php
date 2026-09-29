{{-- Cermin tim-kerja/capaian-kinerja/tipe/arsitektur-pts.blade.php, sisi Validator. --}}
@extends('validator.layout.app')

@section('title', $config['label'] ?? $iku->deskripsi)
@section('subtitle', $iku->kode.' — Validasi Data Capaian Kinerja per Triwulan')

@section('content')
    <a href="{{ route('validator.capaian-kinerja.index') }}?triwulan={{ $triwulanDipilih->kode }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Capaian Kinerja
    </a>

    @php
        $akreditasiList = $capaian->relasi('akreditasi')->with('pts')->latest()->get();
        $penggabunganList = $capaian->relasi('penggabungan')->with('pts')->latest()->get();
        $adaMenungguValidasi = $akreditasiList->contains('status_validasi', 'menunggu_validasi') || $penggabunganList->contains('status_validasi', 'menunggu_validasi');
    @endphp

    <div
        x-data="{
            modalOpen: false,
            komponen: 'akreditasi',
            baris: { id: null, status_validasi: 'disetujui', catatan_revisi: null, label: '' },
            openValidasi(komponen, row, label) { this.komponen = komponen; this.baris = { id: row.id, status_validasi: 'disetujui', catatan_revisi: null, label }; this.modalOpen = true; },
        }"
        class="mt-4"
    >
        <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
            @foreach ($triwulanList as $tw)
                <a href="{{ route('validator.capaian-kinerja.show', $iku->id) }}?triwulan={{ $tw->kode }}"
                   class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                          {{ $triwulanDipilih->id === $tw->id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                    {{ $tw->kode }}
                </a>
            @endforeach
        </div>

        @unless ($isTriwulanAktif)
            <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <p class="text-sm font-medium text-amber-800">{{ $triwulanDipilih->kode }} bukan periode Triwulan aktif.</p>
            </div>
        @endunless

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Realisasi (Akreditasi + Penggabungan) / Target PK</p>
                <p class="mt-1 text-2xl font-bold text-ink-900">
                    {{ $capaian->realisasi !== null ? rtrim(rtrim(number_format($capaian->realisasi, 2, ',', '.'), '0'), ',') : '—' }}
                    <span class="text-sm font-normal text-slate-400">/ {{ rtrim(rtrim(number_format($iku->target_pk, 2, ',', '.'), '0'), ',') }} %</span>
                </p>
            </div>
            <x-status-badge :status="$capaian->status" />
        </div>

        <div class="mt-4 flex justify-end">
            @include('capaian-kinerja._preview-modal', [
                'previewUrl'   => route('validator.capaian-kinerja.preview', $iku->id).'?triwulan_id='.$triwulanDipilih->id,
                'actionUrl'    => route('validator.capaian-kinerja.setujui-semua', $iku->id),
                'triwulanId'   => $triwulanDipilih->id,
                'label'        => 'Setujui Semua',
                'submitLabel'  => 'Konfirmasi & Setujui',
                'disabled'     => ! $adaMenungguValidasi,
                'izinOverride' => false,
            ])
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <p class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-ink-900">Akreditasi PTS</p>
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="px-4 py-2.5 font-semibold">PTS</th>
                        <th class="px-4 py-2.5 font-semibold">Akreditasi</th>
                        <th class="px-4 py-2.5 font-semibold">No. SK</th>
                        <th class="px-4 py-2.5 font-semibold">Masa Berlaku</th>
                        <th class="px-4 py-2.5 text-center font-semibold">Status</th>
                        <th class="px-4 py-2.5 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($akreditasiList as $row)
                        <tr class="{{ $row->status_validasi === 'menunggu_validasi' ? 'bg-amber-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->pts->nama_pts ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->akreditasi }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->no_sk }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ \Illuminate\Support\Carbon::parse($row->masa_berlaku)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-center"><x-status-badge :status="$row->status_validasi" /></td>
                            <td class="px-4 py-2.5 text-center">
                                @if ($row->status_validasi === 'menunggu_validasi')
                                    <button @click="openValidasi('akreditasi', @js($row), @js($row->pts->nama_pts ?? '—'))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Validasi</button>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada data akreditasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <p class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-ink-900">Penggabungan PTS</p>
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="px-4 py-2.5 font-semibold">PTS</th>
                        <th class="px-4 py-2.5 font-semibold">No. SK Penggabungan</th>
                        <th class="px-4 py-2.5 font-semibold">Bukti SK Penggabungan</th>
                        <th class="px-4 py-2.5 text-center font-semibold">Status</th>
                        <th class="px-4 py-2.5 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($penggabunganList as $row)
                        <tr class="{{ $row->status_validasi === 'menunggu_validasi' ? 'bg-amber-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->pts->nama_pts ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->sk_penggabungan }}</td>
                            <td class="px-4 py-2.5 text-center">
                                @if ($row->file_bukti_dukung)
                                    <a href="{{ route('tim-kerja.capaian-kinerja.bukti.preview', [$iku->id, 'penggabungan', $row->id]) }}?triwulan_id={{ $triwulanDipilih->id }}&field=file_bukti_dukung"
                                    target="_blank" rel="noopener" class="font-medium text-blue-600 hover:underline">SK</a>
                                @else <span class="text-xs text-slate-400">—</span> @endif
                            </td>
                            <td class="px-4 py-2.5 text-center"><x-status-badge :status="$row->status_validasi" /></td>
                            <td class="px-4 py-2.5 text-center">
                                @if ($row->status_validasi === 'menunggu_validasi')
                                    <button @click="openValidasi('penggabungan', @js($row), @js($row->pts->nama_pts ?? '—'))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Validasi</button>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada data penggabungan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Modal Validasi (dipakai bergantian untuk kedua komponen via state `komponen`) -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>
            <div x-show="modalOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="'Validasi — ' + baris.label"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('validator/capaian-kinerja/'.$iku->id.'/baris') }}/' + komponen + '/' + baris.id + '/validasi'" method="POST" class="px-6 py-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="triwulan_id" value="{{ $triwulanDipilih->id }}">

                    <label class="block text-sm font-medium text-ink-900">Status</label>
                    <select name="status_validasi" x-model="baris.status_validasi" required class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>

                    <div class="mt-3" x-show="baris.status_validasi === 'ditolak'" x-cloak>
                        <label class="block text-sm font-medium text-ink-900">Catatan Revisi</label>
                        <textarea name="catatan_revisi" x-model="baris.catatan_revisi" rows="3" :required="baris.status_validasi === 'ditolak'"
                                  class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
