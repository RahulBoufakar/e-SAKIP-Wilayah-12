@extends('admin.layout.app')

@section('title', 'IKU LLDIKTI — Target & Realisasi')
@section('content')

    <!-- Tabel -->
   <div
    x-data="{
        modalOpen: {{ $errors->any() ? 'true' : 'false' }},
        form: { iku_id: null, label: '', triwulan: '{{ $triwulan }}', target: '', realisasi: '' },
        openEdit(iku) {
            this.form = { iku_id: iku.id, label: iku.kode + ' — ' + iku.deskripsi, triwulan: '{{ $triwulan }}', target: iku.target ?? '', realisasi: iku.realisasi ?? '' };
            this.modalOpen = true;
        },
    }"
    class="mt-4"
>
    <!-- Tabs Triwulan: full width, menonjol saat aktif -->
    <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        @foreach (['tw1' => 'Triwulan 1', 'tw2' => 'Triwulan 2', 'tw3' => 'Triwulan 3', 'tw4' => 'Triwulan 4'] as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['triwulan' => $key]) }}"
               class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                      {{ $triwulan === $key ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Tabel -->
    <div class="overflow-x-auto rounded-b-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th rowspan="2" class="w-1/4 px-4 py-3 text-center align-middle font-semibold">Sasaran Kegiatan</th>
                    <th rowspan="2" class="w-2/5 px-4 py-3 text-center align-middle font-semibold">Indikator Kinerja (IKU/IKK)</th>
                    <th rowspan="2" class="w-24 whitespace-nowrap px-4 py-3 text-center align-middle font-semibold">Target PK</th>
                    <th rowspan="2" class="w-20 px-4 py-3 text-center align-middle font-semibold">Satuan</th>
                    <th colspan="2" class="px-4 py-2 text-center align-middle font-semibold">{{ strtoupper($triwulan) }}</th>
                    <th rowspan="2" class="w-20 px-4 py-3 text-center align-middle font-semibold">Aksi</th>
                </tr>
                <tr class="bg-ink-900 text-white">
                    <th class="px-4 py-2 text-center align-middle text-xs font-semibold">Target</th>
                    <th class="px-4 py-2 text-center align-middle text-xs font-semibold">Realisasi</th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-slate-200">
                @forelse ($sasaranList as $sasaran)
                    @php $jumlahIku = $sasaran->iku->count(); @endphp
                    @forelse ($sasaran->iku as $iku)
                        @php $r = $iku->realisasis->first(); @endphp
                        <tr class="hover:bg-brand-50/40">
                           @if ($loop->first)
                                <td rowspan="{{ $jumlahIku }}" class="px-4 py-3 align-middle">
                                    <div class="flex items-center gap-1.5">
                                        <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $sasaran->kode }}</span>
                                        <span class="min-w-0 max-w-[220px] break-words text-ink-900">{{ $sasaran->nama_sasaran }}</span>
                                    </div>
                                </td>
                            @endif
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-1.5">
                                    <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $iku->kode }}</span>
                                    <span class="min-w-0 max-w-[320px] break-words text-ink-900">{{ $iku->deskripsi }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-slate-600">{{ rtrim(rtrim(number_format($iku->target_pk, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $iku->satuan }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $r?->target ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $r?->realisasi ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button type="button"
                                        @click="openEdit({ id: {{ $iku->id }}, kode: '{{ $iku->kode }}', deskripsi: @js($iku->deskripsi), target: {{ $r?->target ?? 'null' }}, realisasi: {{ $r?->realisasi ?? 'null' }} })"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada Sasaran Kegiatan untuk Tahun Anggaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Form -->
    @include('admin.target-kinerja.iku-lldikti.modal-form')
</div>
@endsection