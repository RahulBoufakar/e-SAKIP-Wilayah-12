{{--
    Spek Capaian Kinerja Hybrid §4.3 tabel 2 & 3 — IKU 2 (Arsitektur PTS)
    SENGAJA ditangani terpisah dari generik.blade.php: satu-satunya IKU dalam
    scope hybrid yang realisasinya gabungan 2 tabel (akreditasi +
    penggabungan). Ditulis eksplisit sesuai keputusan §2 spek, bukan
    dipaksakan ke renderer kolom tunggal.
--}}
@extends('tim-kerja.layout.app')

@section('title', $config['label'] ?? $iku->deskripsi)
@section('subtitle', $iku->kode.' — Data Capaian Kinerja per Triwulan')

@section('content')
    <a href="{{ route('tim-kerja.capaian-kinerja.index') }}?triwulan={{ $triwulanDipilih->kode }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke Capaian Kinerja
    </a>

    @php
        $akreditasiList = $capaian->relasi('akreditasi')->with('pts')->latest()->get();
        $penggabunganList = $capaian->relasi('penggabungan')->with('pts')->latest()->get();
        $ptsOptions = \App\Models\Pts::opsiDropdown($akreditasiList->pluck('pts_id')->merge($penggabunganList->pluck('pts_id'))->all());
        $akr = old('komponen_form') === 'akreditasi';
        $gab = old('komponen_form') === 'penggabungan';
    @endphp

    <div
        x-data="{
            {{-- Gagal validasi: buka lagi modal yang tadi disubmit dengan mode & isian terakhir --}}
            modalAkreditasiOpen: @js($errors->any() && $akr),
            modalPenggabunganOpen: @js($errors->any() && $gab),
            modeAkreditasi: @js($akr && old('_method') === 'PUT' ? 'edit' : 'create'),
            modePenggabungan: @js($gab && old('_method') === 'PUT' ? 'edit' : 'create'),
            formAkreditasi: @js(['id' => $akr ? old('baris_id') : null, 'pts_id' => $akr ? old('pts_id', '') : '', 'akreditasi' => $akr ? old('akreditasi', '') : '', 'no_sk' => $akr ? old('no_sk', '') : '', 'masa_berlaku' => $akr ? old('masa_berlaku', '') : '']),
            formPenggabungan: @js(['id' => $gab ? old('baris_id') : null, 'pts_id' => $gab ? old('pts_id', '') : '', 'sk_penggabungan' => $gab ? old('sk_penggabungan', '') : '']),
            openCreateAkreditasi() { this.modeAkreditasi = 'create'; this.formAkreditasi = { id: null, pts_id: '', akreditasi: '', no_sk: '', masa_berlaku: '' }; this.modalAkreditasiOpen = true; },
            openEditAkreditasi(row) { this.modeAkreditasi = 'edit'; this.formAkreditasi = { id: row.id, pts_id: row.pts_id, akreditasi: row.akreditasi, no_sk: row.no_sk, masa_berlaku: row.masa_berlaku }; this.modalAkreditasiOpen = true; },
            openCreatePenggabungan() { this.modePenggabungan = 'create'; this.formPenggabungan = { id: null, pts_id: '', sk_penggabungan: '' }; this.modalPenggabunganOpen = true; },
            openEditPenggabungan(row) { this.modePenggabungan = 'edit'; this.formPenggabungan = { id: row.id, pts_id: row.pts_id, sk_penggabungan: row.sk_penggabungan }; this.modalPenggabunganOpen = true; },
        }"
        class="mt-4"
    >
        <!-- Tabs Triwulan -->
        <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
            @foreach ($triwulanList as $tw)
                <a href="{{ route('tim-kerja.capaian-kinerja.show', $iku->id) }}?triwulan={{ $tw->kode }}"
                   class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                          {{ $triwulanDipilih->id === $tw->id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                    {{ $tw->kode }}
                </a>
            @endforeach
        </div>

        @unless ($isTriwulanAktif)
            <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <p class="text-sm font-medium text-amber-800">{{ $triwulanDipilih->kode }} bukan periode Triwulan aktif. Data tidak dapat ditambah/diubah pada periode ini.</p>
            </div>
        @endunless

        <x-workflow.file-warning :masalah="$masalahFile" class="mt-4" />

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

        <!-- Tombol Import Akreditasi -->
        @if ($isTriwulanAktif)
            @php $namaSumber = config('pddikti.driver') === 'lldikti12' ? 'LLDikti 12' : 'PDDikti'; @endphp
            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl bg-white px-5 py-3 shadow-card">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-600">Salin data akreditasi PTS hasil sinkronisasi ke komponen Akreditasi sebagai draft. Isian manual Anda tidak ditimpa.</p>
                    @if ($terakhirSinkronAkreditasi)
                        <p class="mt-0.5 text-xs text-slate-400">Data sinkronisasi terakhir: {{ \Illuminate\Support\Carbon::parse($terakhirSinkronAkreditasi)->format('d/m/Y H:i') }}</p>
                    @else
                        <p class="mt-0.5 text-xs font-medium text-amber-700">Data akreditasi belum pernah disinkronkan. Hubungi Administrator.</p>
                    @endif
                </div>
                <button type="button" @click="$refs['konfirmasi-import-akreditasi'].showModal()" class="shrink-0 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">Import dari {{ $namaSumber }}</button>
            </div>
            <x-confirm-action refName="konfirmasi-import-akreditasi"
                :action="route('tim-kerja.capaian-kinerja.import-akreditasi', $iku->id).'?triwulan_id='.$triwulanDipilih->id"
                title="Import data akreditasi?"
                body="Data akreditasi PTS yang masih berlaku disalin sebagai draft. Isian manual dan data yang sedang/sudah divalidasi tidak diubah."
                confirmLabel="Ya, import" />
        @endif
        
        {{-- Tombol Kirikm Semua --}}
        @if ($isTriwulanAktif)
            <div class="mt-4 flex flex-wrap justify-end gap-3">
                <x-workflow.migrasi-triwulan :iku="$iku" :triwulan-id="$triwulanDipilih->id" :ringkasan="$ringkasanMigrasi" />
                @include('capaian-kinerja._preview-modal', [
                    'previewUrl'   => route('tim-kerja.capaian-kinerja.preview-kirim', $iku->id).'?triwulan_id='.$triwulanDipilih->id,
                    'actionUrl'    => route('tim-kerja.capaian-kinerja.kirim', $iku->id),
                    'triwulanId'   => $triwulanDipilih->id,
                    'label'        => 'Kirim Semua',
                    'submitLabel'  => 'Konfirmasi & Kirim',
                    'disabled'     => ! $capaian->can_kirim,
                    'izinOverride' => true,
                    'tone'         => 'bg-ink-900 hover:bg-ink-800',
                ])
            </div>
        @endif

        <!-- Komponen 1: Akreditasi PTS -->
        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <p class="text-sm font-bold text-ink-900">Akreditasi PTS</p>
                @if ($isTriwulanAktif)
                    <button @click="openCreateAkreditasi()" type="button" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">+ Tambah</button>
                @endif
            </div>
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
                        <tr class="hover:bg-brand-50/40">
                            <td class="px-4 py-2.5 text-slate-600">
                                    {{ $row->pts->nama_pts ?? '—' }}
                                    @if (($row->sumber ?? 'manual') !== 'manual')
                                        <span class="ml-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">{{ $row->sumber === 'lldikti12' ? 'LLDikti12' : 'PDDikti' }}</span>
                                    @endif
                            </td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->akreditasi }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->no_sk }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ \Illuminate\Support\Carbon::parse($row->masa_berlaku)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-center"><x-status-badge :status="$row->status_validasi" /></td>
                            <td class="px-4 py-2.5 text-center">
                                @if (! $row->isFieldLocked() && $isTriwulanAktif)
                                    <button @click="openEditAkreditasi(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    <button @click="$refs['confirm-akr-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                    <x-confirm-delete
                                        refName="confirm-akr-{{ $row->id }}"
                                        :action="route('tim-kerja.capaian-kinerja.baris.destroy', [$iku->id, 'akreditasi', $row->id]).'?triwulan_id='.$triwulanDipilih->id"
                                        label="data akreditasi ini"
                                    />
                                @else
                                    <span class="text-xs text-slate-300">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada data akreditasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($akreditasiList->contains('sumber', 'lldikti12'))
            <p class="mt-2 text-xs text-slate-400">Sumber data akreditasi hasil import: LLDIKTI Wilayah XII</p>
        @endif

        <!-- Komponen 2: Penggabungan PTS -->
        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <p class="text-sm font-bold text-ink-900">Penggabungan PTS</p>
                @if ($isTriwulanAktif)
                    <button @click="openCreatePenggabungan()" type="button" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">+ Tambah</button>
                @endif
            </div>
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
                        <tr class="hover:bg-brand-50/40">
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->pts->nama_pts ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $row->sk_penggabungan }}</td>
                            <td class="px-4 py-2.5 text-center">
                                @if ($row->fileTersedia())
                                    <a href="{{ route('tim-kerja.capaian-kinerja.bukti.preview', [$iku->id, 'penggabungan', $row->id]) }}?triwulan_id={{ $triwulanDipilih->id }}&field=file_bukti_dukung"
                                    target="_blank" rel="noopener" class="font-medium text-blue-600 hover:underline">SK</a>
                                @else <span class="text-xs text-slate-400">{{ filled($row->file_bukti_dukung) ? 'File tidak tersedia' : '—' }}</span> @endif
                            </td>
                            <td class="px-4 py-2.5 text-center"><x-status-badge :status="$row->status_validasi" /></td>
                            <td class="px-4 py-2.5 text-center">
                                @if (! $row->isFieldLocked() && $isTriwulanAktif)
                                    <button @click="openEditPenggabungan(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    <button @click="$refs['confirm-gab-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                    <x-confirm-delete
                                        refName="confirm-gab-{{ $row->id }}"
                                        :action="route('tim-kerja.capaian-kinerja.baris.destroy', [$iku->id, 'penggabungan', $row->id]).'?triwulan_id='.$triwulanDipilih->id"
                                        label="data penggabungan ini"
                                    />
                                @else
                                    <span class="text-xs text-slate-300">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada data penggabungan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Modal Akreditasi -->
        <div x-show="modalAkreditasiOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalAkreditasiOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalAkreditasiOpen = false"></div>
            <div x-show="modalAkreditasiOpen" x-transition class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="modeAkreditasi === 'create' ? 'Tambah Akreditasi' : 'Edit Akreditasi'"></h3>
                    <button type="button" @click="modalAkreditasiOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form method="POST"
                      :action="modeAkreditasi === 'create'
                            ? '{{ route('tim-kerja.capaian-kinerja.baris.store', [$iku->id, 'akreditasi']) }}'
                            : '{{ url('tim-kerja/capaian-kinerja/'.$iku->id.'/baris/akreditasi') }}/' + formAkreditasi.id"
                      class="px-6 py-4">
                    @csrf
                    <template x-if="modeAkreditasi === 'edit'">@method('PUT')</template>
                    <input type="hidden" name="triwulan_id" value="{{ $triwulanDipilih->id }}">
                    <input type="hidden" name="komponen_form" value="akreditasi">
                    <input type="hidden" name="baris_id" :value="formAkreditasi.id">

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-ink-900">PTS</label>
                            <select name="pts_id" x-model="formAkreditasi.pts_id" required class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="" disabled>Pilih PTS</option>
                                @foreach ($ptsOptions as $pts)<option value="{{ $pts->id }}">{{ $pts->kode_pts }} — {{ $pts->nama_pts }}</option>@endforeach
                            </select>
                            @error('pts_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <x-form.input label="Akreditasi" name="akreditasi" type="text" x-model="formAkreditasi.akreditasi" required />
                        <x-form.input label="No. SK" name="no_sk" type="text" x-model="formAkreditasi.no_sk" required />
                        <x-form.input label="Masa Berlaku" name="masa_berlaku" type="date" x-model="formAkreditasi.masa_berlaku" required />
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="modalAkreditasiOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Penggabungan -->
        <div x-show="modalPenggabunganOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalPenggabunganOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalPenggabunganOpen = false"></div>
            <div x-show="modalPenggabunganOpen" x-transition class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="modePenggabungan === 'create' ? 'Tambah Penggabungan' : 'Edit Penggabungan'"></h3>
                    <button type="button" @click="modalPenggabunganOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data"
                      :action="modePenggabungan === 'create'
                            ? '{{ route('tim-kerja.capaian-kinerja.baris.store', [$iku->id, 'penggabungan']) }}'
                            : '{{ url('tim-kerja/capaian-kinerja/'.$iku->id.'/baris/penggabungan') }}/' + formPenggabungan.id"
                      class="px-6 py-4">
                    @csrf
                    <template x-if="modePenggabungan === 'edit'">@method('PUT')</template>
                    <input type="hidden" name="triwulan_id" value="{{ $triwulanDipilih->id }}">
                    <input type="hidden" name="komponen_form" value="penggabungan">
                    <input type="hidden" name="baris_id" :value="formPenggabungan.id">

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-ink-900">PTS</label>
                            <select name="pts_id" x-model="formPenggabungan.pts_id" required class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="" disabled>Pilih PTS</option>
                                @foreach ($ptsOptions as $pts)<option value="{{ $pts->id }}">{{ $pts->kode_pts }} — {{ $pts->nama_pts }}</option>@endforeach
                            </select>
                            @error('pts_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <x-form.input label="No. SK Penggabungan" name="sk_penggabungan" type="text" x-model="formPenggabungan.sk_penggabungan" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink-900">Bukti Dokumen SK Penggabungan PTS (PDF) <span class="text-rose-500">*</span></label>
                        <input type="file" name="file_bukti_dukung" accept="application/pdf" :required="modePenggabungan === 'create'"
                            class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                        <p class="mt-1 text-xs text-slate-400" x-show="modePenggabungan === 'edit'">Kosongkan jika tidak ingin mengganti file.</p>
                        @error('file_bukti_dukung')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="modalPenggabunganOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
