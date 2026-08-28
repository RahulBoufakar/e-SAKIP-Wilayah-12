@extends('validator.layout.app')

@section('title', 'Analisis Kinerja')
@section('subtitle', 'Analisis capaian kinerja seluruh IKU per Triwulan')

@section('content')
    <div
        x-data="{
            modalOpen: false,
            dok: { id: null, kode: '', status: 'disetujui', catatan_revisi: null },
            openValidasi(iku, analisa) {
                this.dok = { id: analisa.id, kode: iku.kode, status: 'disetujui', catatan_revisi: null };
                this.modalOpen = true;
            },
        }"
    >
        <!-- Tabs Triwulan: semua dapat diklik untuk melihat periode lain -->
        <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
            @foreach ($triwulanList as $tw)
                <a href="{{ request()->fullUrlWithQuery(['triwulan' => $tw->kode]) }}"
                   class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                          {{ $triwulanDipilih && $triwulanDipilih->id === $tw->id ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                    {{ $tw->kode }}
                </a>
            @endforeach
        </div>

        @unless ($isTriwulanAktif)
            <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <p class="text-sm font-medium text-amber-800">
                    {{ $triwulanDipilih->kode ?? 'Triwulan ini' }} bukan periode Triwulan aktif. Anda tidak dapat memvalidasi Analisis Kinerja pada periode ini.
                </p>
            </div>
        @endunless

        <div class="mt-4 overflow-x-auto rounded-b-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-1/5 px-4 py-3 font-semibold">IKU</th>
                        <th class="w-36 px-4 py-3 font-semibold">Tim Kerja</th>
                        <th class="px-4 py-3 font-semibold">Progress</th>
                        <th class="px-4 py-3 font-semibold">Kendala</th>
                        <th class="px-4 py-3 font-semibold">Tindak Lanjut</th>
                        <th class="w-36 px-4 py-3 text-center font-semibold">Status</th>
                        <th class="w-24 px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ikuList as $iku)
                        @php $analisa = $iku->analisaKinerja->first(); @endphp
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-1.5">
                                    <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</span>
                                    <span class="min-w-0 max-w-[220px] break-words text-ink-900">{{ $iku->deskripsi }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $iku->timKerja->nama_tim ?? '—' }}</td>
                            <td class="max-w-[12rem] px-4 py-3">
                                <x-truncate-cell :id="'progress-'.$iku->id" :text="$analisa->progress ?? '—'" />
                            </td>
                            <td class="max-w-[12rem] px-4 py-3">
                                <x-truncate-cell :id="'kendala-'.$iku->id" :text="$analisa->kendala ?? '—'" />
                            </td>
                            <td class="max-w-[12rem] px-4 py-3">
                                <x-truncate-cell :id="'tindak-'.$iku->id" :text="$analisa->tindak_lanjut ?? '—'" />
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($analisa)
                                    <x-status-badge :status="$analisa->status" />
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Belum Diisi</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($analisa && $analisa->status === 'menunggu_validasi')
                                    @if ($isTriwulanAktif)
                                        <button type="button" @click="openValidasi(@js($iku), @js($analisa))" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Validasi</button>
                                    @else
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300">Validasi</button>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                                Belum ada IKU untuk tahun anggaran ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Modal Validasi -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>
            <div x-show="modalOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900" x-text="'Validasi — ' + dok.kode"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('validator/analisa-kinerja') }}/' + dok.id + '/validasi'" method="POST" class="px-6 py-4">
                    @csrf
                    @method('PUT')

                    <label class="block text-sm font-medium text-ink-900">Status</label>
                    <select name="status" x-model="dok.status" required class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>

                    <div class="mt-3" x-show="dok.status === 'ditolak'" x-cloak>
                        <label class="block text-sm font-medium text-ink-900">Catatan Revisi</label>
                        <textarea name="catatan_revisi" x-model="dok.catatan_revisi" rows="3" :required="dok.status === 'ditolak'"
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