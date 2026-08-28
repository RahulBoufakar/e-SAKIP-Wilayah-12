@extends('tim-kerja.layout.app')

@section('title', 'Kalender Proker')
@section('subtitle', 'Kalender pelaksanaan Program Kerja Tim Kerja Anda')

@section('content')
    <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'berjalan', 'tampilkan_semua' => $tampilkanSemua ? 1 : null]) }}"
           class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                  {{ $tab === 'berjalan' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
            Tahun Ini ({{ $activeTahun }})
        </a>
        @if ($nextYearAvailable)
            <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'h_plus_1', 'tampilkan_semua' => $tampilkanSemua ? 1 : null]) }}"
               class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                      {{ $tab === 'h_plus_1' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
                Tahun Depan ({{ $nextYear }})
            </a>
        @else
            <span class="flex-1 cursor-not-allowed px-4 py-3 text-center text-sm font-semibold text-slate-300">
                Tahun Depan ({{ $nextYear }}) — Belum tersedia
            </span>
        @endif
    </div>

    <div class="mt-4 flex justify-end">
        <a href="{{ request()->fullUrlWithQuery(['tampilkan_semua' => $tampilkanSemua ? null : 1, 'page' => null]) }}"
           class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors {{ $tampilkanSemua ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            <span class="h-2 w-2 rounded-full {{ $tampilkanSemua ? 'bg-amber-500' : 'bg-slate-300' }}"></span>
            {{ $tampilkanSemua ? 'Menampilkan semua (termasuk belum tervalidasi)' : 'Tampilkan yang belum tervalidasi' }}
        </a>
    </div>

    <div class="mt-3 overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th rowspan="2" class="w-28 px-3 py-2.5 align-middle font-semibold">Tim Kerja</th>
                    <th rowspan="2" class="w-14 px-3 py-2.5 text-center align-middle font-semibold">IKU</th>
                    <th rowspan="2" class="w-44 px-3 py-2.5 align-middle font-semibold">Nama Kegiatan</th>
                    <th rowspan="2" class="w-36 px-3 py-2.5 align-middle font-semibold">Tempat Pelaksanaan</th>
                    <th rowspan="2" class="w-36 px-3 py-2.5 align-middle font-semibold">Bentuk Kegiatan</th>
                    <th rowspan="2" class="w-28 px-3 py-2.5 text-right align-middle font-semibold">Anggaran</th>
                    <th colspan="12" class="px-3 py-2 text-center font-semibold">Matriks Kalender</th>
                </tr>
                <tr class="bg-ink-900 text-white">
                    @for ($b = 1; $b <= 12; $b++)
                        <th class="w-9 px-1 py-2 text-center text-[10px] font-semibold">{{ $bulanIndo[$b] }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($prokerList as $row)
                    @php
                        $detail = $row->detailKegiatan;
                        $bulanAktif = $detail?->bulan_kegiatan ?? [];
                        $belumTervalidasi = $row->status_validasi !== 'approved';
                    @endphp
                                        <tr id="proker-{{ $row->id }}" class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                        <td class="px-3 py-2 text-slate-600">{{ $row->iku->timKerja->nama_tim ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="font-mono text-[11px] font-semibold text-brand-700">
                                <x-truncate-cell :id="'iku-'.$row->id" :short="$row->iku->nomor ?? '—'" :text="$row->iku->deskripsi ?? '—'" />
                            </span>
                        </td>
                        <td class="max-w-[11rem] px-3 py-2">
                            <x-truncate-cell :id="'nama-'.$row->id" :text="$row->nama_usulan" />
                            @if ($belumTervalidasi)
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                    Belum Tervalidasi
                                </span>
                            @endif
                        </td>
                        <td class="max-w-[9rem] px-3 py-2">
                            <x-truncate-cell :id="'tempat-'.$row->id" :text="$detail->tempat_pelaksanaan ?? '—'" />
                        </td>
                        <td class="max-w-[9rem] px-3 py-2">
                            <x-truncate-cell :id="'bentuk-'.$row->id" :text="$detail->bentuk_kegiatan ?? '—'" />
                        </td>
                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap text-slate-600">
                            {{ $detail ? 'Rp '.number_format($detail->anggaran, 0, ',', '.') : '—' }}
                        </td>
                        @for ($b = 1; $b <= 12; $b++)
                            @php
                                $aktifBulanIni = in_array($b, $bulanAktif);
                                $itemsBulanIku = $aktifBulanIni ? ($prokerPerIkuBulan[$row->iku_id][$b] ?? collect()) : collect();
                                $countBulanIku = $itemsBulanIku->count();
                            @endphp
                            <td class="px-1 py-2 text-center">
                                @if ($aktifBulanIni)
                                    <div x-data class="group relative mx-auto flex w-fit justify-center">
                                        <button type="button" @click="$refs['circle-{{ $row->id }}-{{ $b }}'].showModal()"
                                                class="h-4 w-4 cursor-pointer rounded-full bg-brand-500 hover:bg-brand-600">
                                        </button>

                                        <span class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-md bg-ink-900 px-2 py-1 text-[10px] font-semibold text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100">
                                            {{ $countBulanIku }} Program Aktif
                                        </span>

                                        <dialog x-ref="circle-{{ $row->id }}-{{ $b }}" @click.self="$el.close()" class="m-auto w-full max-w-sm rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                            <div class="p-6">
                                                <h3 class="text-sm font-bold text-ink-900">{{ $bulanIndo[$b] }} — IKU {{ $row->iku->nomor ?? '—' }}</h3>
                                                <p class="mt-1 text-xs text-slate-400">Program yang direncanakan pada bulan {{$bulanIndo[$b] }} {{$tahun}}.</p>
                                                <ul class="mt-4 space-y-2">
                                                    @foreach ($itemsBulanIku as $item)
                                                        <li class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                                                            <span class="min-w-0 truncate text-sm text-ink-900">{{ $item['nama'] }}</span>
                                                            <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => $tab]) }}#proker-{{ $item['id'] }}"
                                                               class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                                                Lihat Detail
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="mt-5 flex justify-end">
                                                    <button type="button" @click="$refs['circle-{{ $row->id }}-{{ $b }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                                </div>
                                            </div>
                                        </dialog>
                                    </div>
                                @else
                                    <span class="mx-auto inline-block h-2.5 w-2.5 rounded-full bg-slate-100"></span>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr>
                        <td colspan="18" class="px-4 py-12 text-center text-sm text-slate-400">
                            Belum ada Program Kerja untuk ditampilkan.
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