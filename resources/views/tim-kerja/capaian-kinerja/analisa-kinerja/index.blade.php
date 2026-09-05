@extends('tim-kerja.layout.app')

@section('title', 'Analisis Kinerja')
@section('subtitle', 'Analisis capaian kinerja IKU Tim Kerja Anda per Triwulan')

@section('content')
    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            form: { iku_id: @js(old('iku_id')), kode: '', deskripsi: '', progress: @js(old('progress', '')), kendala: @js(old('kendala', '')), tindak_lanjut: @js(old('tindak_lanjut', '')), catatan_revisi: null },
            openForm(iku, analisa) {
                this.form = {
                    iku_id: iku.id,
                    kode: iku.kode,
                    deskripsi: iku.deskripsi,
                    progress: analisa?.progress ?? '',
                    kendala: analisa?.kendala ?? '',
                    tindak_lanjut: analisa?.tindak_lanjut ?? '',
                    catatan_revisi: analisa?.catatan_revisi ?? null,
                };
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
                    {{ $triwulanDipilih->kode ?? 'Triwulan ini' }} bukan periode Triwulan aktif. Anda tidak dapat mengisi atau merevisi Analisis Kinerja pada periode ini.
                </p>
            </div>
        @endunless

        <div class="mt-4 overflow-x-auto rounded-b-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-1/4 px-4 py-3 font-semibold">IKU</th>
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
                                @if (! $analisa)
                                    @if ($isTriwulanAktif)
                                        <button type="button" @click="openForm(@js($iku), null)" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Isi Data</button>
                                    @else
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300">Isi Data</button>
                                    @endif
                                @elseif (in_array($analisa->status, ['draft', 'ditolak'], true))
                                    @if ($isTriwulanAktif)
                                        <button type="button" @click="openForm(@js($iku), @js($analisa))" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    @else
                                        <button type="button" disabled class="cursor-not-allowed rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-300">Edit</button>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                                Belum ada IKU untuk Tim Kerja Anda pada tahun anggaran ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Modal Isi/Edit -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>
            <div x-show="modalOpen" x-transition class="relative flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-bold text-ink-900">Analisis Kinerja — <span x-text="form.kode"></span></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form :action="'{{ url('tim-kerja/analisa-kinerja') }}/' + form.iku_id + '/{{ $triwulanDipilih->id ?? '' }}'" method="POST" class="flex flex-1 flex-col overflow-hidden">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="iku_id" :value="form.iku_id">

                    <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">
                        <p class="text-sm text-slate-600" x-text="form.deskripsi"></p>

                        <template x-if="form.catatan_revisi">
                            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                                <p class="font-semibold">Catatan Revisi:</p>
                                <p class="mt-1" x-text="form.catatan_revisi"></p>
                            </div>
                        </template>

                        <x-form.textarea label="Progress" name="progress" :rows="2" maxlength="255" x-model="form.progress" required />
                        <x-form.textarea label="Kendala" name="kendala" :rows="3" x-model="form.kendala" />
                        <x-form.textarea label="Tindak Lanjut" name="tindak_lanjut" :rows="3" x-model="form.tindak_lanjut" />
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection