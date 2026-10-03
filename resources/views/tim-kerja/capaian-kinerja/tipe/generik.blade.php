{{--
    Spek Capaian Kinerja Hybrid §8 — satu Blade ini melayani 8 dari 9 tipe_iku
    (semuanya kecuali arsitektur_pts, yang punya 2 tabel dan ditangani di
    arsitektur-pts.blade.php). Kolom form/tabel dibaca dari
    config('capaian_kinerja_tipe.{tipe}') — lihat config file untuk daftar
    lengkap. Ini murni rendering dinamis presentasi, BUKAN logika kalkulasi
    (formula tetap eksplisit per-tipe di CapaianKinerjaHitungService).
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
        $barisList = $capaian->relasi('utama')->with($config['butuh_pts'] ?? false ? 'pts' : [])->latest()->get();
        $buktiWajib = $config['bukti_wajib'] ?? false;
    @endphp

    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            mode: 'create',
            form: { id: null, pts_id: '', @foreach ($config['kolom'] as $k) {{ $k['field'] }}: '', @endforeach },
            openCreate() {
                this.mode = 'create';
                this.form = { id: null, pts_id: '', @foreach ($config['kolom'] as $k) {{ $k['field'] }}: '', @endforeach };
                this.modalOpen = true;
            },
            openEdit(row) {
                this.mode = 'edit';
                this.form = { id: row.id, pts_id: row.pts_id ?? '', @foreach ($config['kolom'] as $k) {{ $k['field'] }}: row.{{ $k['field'] }} ?? '', @endforeach };
                this.modalOpen = true;
            },
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

        <x-workflow.revision-banner :status="$capaian->status" :catatan-revisi="$capaian->catatan_revisi" class="mt-4" />
        <x-workflow.file-warning :masalah="$masalahFile" class="mt-4" />

        <!-- Ringkasan Realisasi -->
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Realisasi / Target PK</p>
                <p class="mt-1 text-2xl font-bold text-ink-900">
                    {{ $capaian->realisasi !== null ? rtrim(rtrim(number_format($capaian->realisasi, 2, ',', '.'), '0'), ',') : '—' }}
                    <span class="text-sm font-normal text-slate-400">/ {{ rtrim(rtrim(number_format($iku->target_pk, 2, ',', '.'), '0'), ',') }} {{ $config['satuan'] ?? $iku->satuan }}</span>
                </p>
                @if ($capaian->realisasi_override)
                    <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Nilai realisasi di-override manual</span>
                @endif
            </div>
            <x-status-badge :status="$capaian->status" />
        </div>
        
        {{-- Jumlah Publikasi (pembagi realisasi) --}}
        @includeWhen($iku->tipe_iku === 'fasilitasi_penelitian', 'tim-kerja.capaian-kinerja.tipe._jumlah-publikasi')

        @if ($isTriwulanAktif)
            <div class="mt-4 flex flex-wrap justify-end gap-3">
                @php
                    $entriTunggal = $config['entri_tunggal'] ?? false;
                    $tambahDinonaktifkan = $entriTunggal && $barisList->isNotEmpty();
                @endphp

                <x-workflow.migrasi-triwulan :iku="$iku" :triwulan-id="$triwulanDipilih->id" :ringkasan="$ringkasanMigrasi" />

                <button @click="openCreate()" type="button" {{ $tambahDinonaktifkan ? 'disabled' : '' }}
                        title="{{ $tambahDinonaktifkan ? 'IKU ini hanya boleh satu data per triwulan. Edit data yang sudah ada.' : '' }}"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-card {{ $tambahDinonaktifkan ? 'cursor-not-allowed bg-slate-300' : 'bg-brand-600 hover:bg-brand-700' }}">
                    + Tambah Data
                </button>
                @if ($tambahDinonaktifkan)
                    <p class="self-center text-xs text-slate-400">Sudah ada data untuk triwulan ini.</p>
                @endif
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

        <!-- Tabel Data -->
        <div class="mt-3 overflow-x-auto rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        @if ($config['butuh_pts'] ?? false)<th class="px-4 py-3 font-semibold">PTS</th>@endif
                        @foreach ($config['kolom'] as $k)<th class="px-4 py-3 font-semibold">{{ $k['label'] }}</th>@endforeach
                        <th class="px-4 py-3 text-center font-semibold">Bukti</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($barisList as $row)
                        <tr class="hover:bg-brand-50/40">
                            @if ($config['butuh_pts'] ?? false)<td class="px-4 py-3 text-slate-600">{{ $row->pts->nama_pts ?? '—' }}</td>@endif
                            @foreach ($config['kolom'] as $k)
                                <td class="max-w-[14rem] px-4 py-3 text-slate-600">
                                    @if ($k['tipe'] === 'textarea')
                                        <x-truncate-cell :id="$k['field'].'-'.$row->id" :text="$row->{$k['field']}" />
                                    @elseif ($k['tipe'] === 'date')
                                        {{ \Illuminate\Support\Carbon::parse($row->{$k['field']})->format('d/m/Y') }}
                                    @elseif ($k['tipe'] === 'file')
                                        @if ($row->fileTersedia($k['field']))
                                            <a href="{{ route('tim-kerja.capaian-kinerja.bukti.preview', [$iku->id, 'utama', $row->id]) }}?triwulan_id={{ $triwulanDipilih->id }}&field={{ $k['field'] }}"
                                            target="_blank" rel="noopener" class="font-medium text-blue-600 hover:underline">{{ $k['link_teks'] ?? 'lihat dokumen' }}</a>
                                        @else
                                            <span class="text-xs text-slate-400">{{ filled($row->{$k['field']}) ? 'File tidak tersedia' : '—' }}</span>
                                        @endif
                                    @else
                                        {{ $row->{$k['tampil'] ?? $k['field']} }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center">
                               @if ($row->fileTersedia())
                                    <x-file-preview
                                        :id="'bukti-'.$row->id"
                                        label="Bukti Dukung"
                                        :url="$row->file_bukti_dukung"
                                        :preview-url="route('tim-kerja.capaian-kinerja.bukti.preview', [$iku->id, 'utama', $row->id]).'?triwulan_id='.$triwulanDipilih->id"
                                        :download-url="route('tim-kerja.capaian-kinerja.bukti.unduh', [$iku->id, 'utama', $row->id]).'?triwulan_id='.$triwulanDipilih->id"
                                        :hide-label="true"
                                    />
                                @else
                                    <span class="text-xs text-slate-400">{{ filled($row->file_bukti_dukung) ? 'File tidak tersedia' : '—' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center"><x-status-badge :status="$row->status_validasi" /></td>
                            <td class="px-4 py-3 text-center">
                                @if (! $row->isFieldLocked() && $isTriwulanAktif)
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="openEdit(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                        <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </div>
                                    @include('admin.layout.confirm-delete', [
                                        'refName' => 'confirm-'.$row->id,
                                        'action' => route('tim-kerja.capaian-kinerja.baris.destroy', [$iku->id, 'utama', $row->id]).'?triwulan_id='.$triwulanDipilih->id,
                                        'label' => 'data ini',
                                    ])
                                @else
                                    <span class="text-xs text-slate-300">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="99" class="px-4 py-12 text-center text-sm text-slate-400">Belum ada data. Klik "Tambah Data" untuk menambahkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Modal Tambah/Edit -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>
            <div x-show="modalOpen" x-transition class="relative flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="mode === 'create' ? 'Tambah Data' : 'Edit Data'"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form method="POST" enctype="multipart/form-data"
                      :action="mode === 'create'
                            ? '{{ route('tim-kerja.capaian-kinerja.baris.store', [$iku->id, 'utama']) }}'
                            : '{{ url('tim-kerja/capaian-kinerja/'.$iku->id.'/baris/utama') }}/' + form.id"
                      class="flex flex-1 flex-col overflow-hidden">
                    @csrf
                    <template x-if="mode === 'edit'">
                        @method('PUT')
                    </template>
                    <input type="hidden" name="triwulan_id" value="{{ $triwulanDipilih->id }}">

                    <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">
                        @if ($config['butuh_pts'] ?? false)
                            <div>
                                <label class="block text-sm font-medium text-ink-900">PTS</label>
                                <select name="pts_id" x-model="form.pts_id" required
                                        class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="" disabled>Pilih PTS</option>
                                    @foreach ($ptsOptions as $pts)
                                        <option value="{{ $pts->id }}">{{ $pts->kode_pts }} — {{ $pts->nama_pts }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @foreach ($config['kolom'] as $k)
                            <div>
                                <label class="block text-sm font-medium text-ink-900">{{ $k['label'] }}</label>
                                @if ($k['tipe'] === 'select')
                                    <select name="{{ $k['field'] }}" x-model="form.{{ $k['field'] }}" {{ $k['required'] ? 'required' : '' }}
                                            class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                        <option value="" disabled>Pilih {{ $k['label'] }}</option>
                                        @foreach ($k['opsi'] as $opsi)
                                            <option value="{{ $opsi }}">{{ ucwords(str_replace('_', ' ', $opsi)) }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($k['tipe'] === 'textarea')
                                    <textarea name="{{ $k['field'] }}" x-model="form.{{ $k['field'] }}" rows="3" {{ $k['required'] ? 'required' : '' }}
                                              class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                                @elseif ($k['tipe'] === 'date')
                                    <input type="date"
                                           name="{{ $k['field'] }}" x-model="form.{{ $k['field'] }}" {{ $k['required'] ? 'required' : '' }}
                                           class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                @elseif (in_array($k['tipe'], ['number', 'number_decimal']))
                                    <input type="number"
                                           min="0"
                                           @if ($k['tipe'] === 'number_decimal') step="0.01" @endif
                                           name="{{ $k['field'] }}" x-model="form.{{ $k['field'] }}" {{ $k['required'] ? 'required' : '' }}
                                           class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                @elseif ($k['tipe'] === 'file')
                                    <input type="file" name="{{ $k['field'] }}" accept="application/pdf"
                                        :required="mode === 'create' && {{ $k['required'] ? 'true' : 'false' }}"
                                        class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                                    <p class="mt-1 text-xs text-slate-400" x-show="mode === 'edit'">PDF, maks. 5 MB. Kosongkan jika tidak ingin mengganti file.</p>
                                @else
                                    <input type="text"
                                           name="{{ $k['field'] }}" x-model="form.{{ $k['field'] }}" {{ $k['required'] ? 'required' : '' }}
                                           class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                @endif
                            </div>
                        @endforeach

                        <div>
                            <label class="block text-sm font-medium text-ink-900">
                                File Bukti Dukung (PDF) @if ($buktiWajib)<span class="text-rose-500">*</span>@else <span class="font-normal text-slate-400">(opsional)</span>@endif
                            </label>
                            <input type="file" name="file_bukti_dukung" accept="application/pdf"
                                   :required="mode === 'create' && {{ $buktiWajib ? 'true' : 'false' }}"
                                   class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                            <p class="mt-1 text-xs text-slate-400" x-show="mode === 'edit'">Kosongkan jika tidak ingin mengganti file.</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
