@extends('validator.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan validasi seluruh modul Program Kerja & Kinerja')

@section('content')
    {{-- ================= USULAN PROKER ================= --}}
    <div class="rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Usulan Program Kerja @if ($activeTahun) (Tahun {{ $activeTahun }}) @endif</p>
        <div class="mt-4 grid grid-cols-3 gap-4">
            <a href="{{ route('validator.usulan-program-kerja.index', ['status' => 'menunggu_validasi', 'tahun' => 'berjalan']) }}" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                <p class="font-mono text-xl font-bold text-amber-600">{{ $usulan['menunggu_validasi'] }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
            </a>
            <a href="{{ route('validator.usulan-program-kerja.index', ['status' => 'approved', 'tahun' => 'berjalan']) }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                <p class="font-mono text-xl font-bold text-emerald-600">{{ $usulan['approved'] }}</p>
                <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
            </a>
            <a href="{{ route('validator.usulan-program-kerja.index', ['status' => 'rejected', 'tahun' => 'berjalan']) }}" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                <p class="font-mono text-xl font-bold text-rose-600">{{ $usulan['rejected'] }}</p>
                <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="usulanStatusChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= DATA PROKER ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Data Proker — Jenis Kegiatan @if ($activeTahun) (Tahun {{ $activeTahun }}) @endif</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('validator.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-brand-50 p-3 text-center transition hover:ring-2 hover:ring-brand-400">
                <p class="font-mono text-xl font-bold text-brand-700">{{ $dataProker['total'] }}</p>
                <p class="mt-1 text-xs font-medium text-brand-700">Total Disetujui</p>
            </a>
            <a href="{{ route('validator.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-cyan-50 p-3 text-center transition hover:ring-2 hover:ring-cyan-400">
                <p class="font-mono text-xl font-bold text-cyan-700">{{ $dataProker['kunjungan_lapangan'] }}</p>
                <p class="mt-1 text-xs font-medium text-cyan-700">Kunjungan Lapangan</p>
            </a>
            <a href="{{ route('validator.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-100 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                <p class="font-mono text-xl font-bold text-slate-600">{{ $dataProker['lainnya'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Lainnya</p>
            </a>
            <a href="{{ route('validator.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-50 p-3 text-center transition hover:ring-2 hover:ring-slate-300">
                <p class="font-mono text-xl font-bold text-slate-400">{{ $dataProker['draft'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-400">Draft (Belum Divalidasi)</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="dataProkerPieChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= KALENDER PROKER ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Kalender Proker</p>

        <div class="mt-3 flex w-full overflow-hidden rounded-xl border border-slate-100">
            <a href="{{ route('validator.kalender-proker.index', ['tahun' => 'berjalan']) }}"
               class="flex-1 px-3 py-2 text-center text-xs font-semibold text-slate-500 hover:bg-slate-50">
                Tahun Ini ({{ $activeTahun }}) — {{ $kalenderBerjalan['total'] }} Jadwal
            </a>
            @if ($nextYearAvailable)
                <a href="{{ route('validator.kalender-proker.index', ['tahun' => 'h_plus_1']) }}"
                   class="flex-1 border-l border-slate-100 px-3 py-2 text-center text-xs font-semibold text-slate-500 hover:bg-slate-50">
                    Tahun Depan ({{ $nextYear }}) — {{ $kalenderHPlus1['total'] }} Jadwal
                </a>
            @else
                <span class="flex-1 cursor-not-allowed border-l border-slate-100 px-3 py-2 text-center text-xs font-semibold text-slate-300">
                    Tahun Depan ({{ $nextYear }}) — Belum tersedia
                </span>
            @endif
        </div>

        <div class="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-12">
            @foreach ($kalenderBerjalan['per_bulan'] as $bulan => $jumlah)
                <a href="{{ route('validator.kalender-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-lg bg-slate-50 p-2 text-center transition hover:bg-brand-50">
                    <p class="font-mono text-sm font-bold text-ink-900">{{ $jumlah }}</p>
                    <p class="text-[10px] font-semibold text-slate-400">{{ $bulanIndo[$bulan] }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-3">
            <canvas id="kalenderProkerChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= PELAPORAN KEGIATAN ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Progress Validasi Dokumen Pelaporan Kegiatan @if ($activeTahun) (Tahun {{ $activeTahun }}) @endif</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-50 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                <p class="font-mono text-xl font-bold text-slate-500">{{ $pelaporan['belum_diunggah'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Belum Diunggah</p>
            </a>
            <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                <p class="font-mono text-xl font-bold text-amber-600">{{ $pelaporan['menunggu_validasi'] }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
            </a>
            <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                <p class="font-mono text-xl font-bold text-emerald-600">{{ $pelaporan['disetujui'] }}</p>
                <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
            </a>
            <a href="{{ route('validator.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                <p class="font-mono text-xl font-bold text-rose-600">{{ $pelaporan['ditolak'] }}</p>
                <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="pelaporanChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= TAGGING PTS ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Tagging PTS</p>
        <div class="mt-4 grid grid-cols-2 gap-4">
            <a href="{{ route('validator.pts-tagging.index') }}" class="rounded-xl bg-brand-50 p-3 text-center transition hover:ring-2 hover:ring-brand-400">
                <p class="font-mono text-xl font-bold text-brand-700">{{ $ptsTagging['total_pts_ditagging'] }}</p>
                <p class="mt-1 text-xs font-medium text-brand-700">PTS Sudah Ditagging</p>
            </a>
            <a href="{{ route('validator.pts-tagging.index') }}" class="rounded-xl bg-slate-50 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                <p class="font-mono text-xl font-bold text-slate-500">{{ $ptsTagging['total_pts_belum_ditagging'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Belum Ditagging</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="ptsTaggingPieChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= CAPAIAN & ANALISIS KINERJA (Tab Triwulan, client-side) ================= --}}
    <div x-data="{ activeTw: '{{ $triwulanAktifKode }}', triwulanData: @js($triwulanList) }" class="mt-5">

        {{-- Tab Triwulan (shared untuk kedua section di bawah) --}}
        <div class="rounded-t-2xl bg-white shadow-card">
            <div class="flex w-full overflow-hidden rounded-t-2xl border-b border-slate-100">
                @foreach ($triwulanList as $tw)
                    <button type="button" @click="activeTw = '{{ $tw['kode'] }}'"
                            :class="activeTw === '{{ $tw['kode'] }}' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600'"
                            class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors">
                        {{ $tw['kode'] }}
                        @if ($tw['is_aktif'])
                            <span class="ml-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500 align-middle"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <template x-for="tw in triwulanData" :key="tw.id">
            <div x-show="activeTw === tw.kode" x-cloak>
                {{-- Capaian Kinerja --}}
                <div class="rounded-2xl bg-white p-5 shadow-card">
                    <p class="text-sm font-semibold text-ink-900">Capaian Kinerja — <span x-text="tw.kode"></span></p>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                          <a :href="'{{ route('validator.capaian-kinerja.index') }}?triwulan=' + tw.kode" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                            <p class="font-mono text-xl font-bold text-amber-600" x-text="tw.capaian.menunggu_validasi"></p>
                            <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
                        </a>
                        <a :href="'{{ route('validator.capaian-kinerja.index') }}?triwulan=' + tw.kode" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                            <p class="font-mono text-xl font-bold text-emerald-600" x-text="tw.capaian.disetujui"></p>
                            <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
                        </a>
                        <a :href="'{{ route('validator.capaian-kinerja.index') }}?triwulan=' + tw.kode" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                            <p class="font-mono text-xl font-bold text-rose-600" x-text="tw.capaian.ditolak"></p>
                            <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
                        </a>
                    </div>
                    <div class="mt-3">
                        <canvas :id="'capaianChart-' + tw.id" height="145"></canvas>
                    </div>
                </div>

                {{-- Analisis Kinerja --}}
                <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
                    <p class="text-sm font-semibold text-ink-900">Analisis Kinerja — <span x-text="tw.kode"></span></p>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <a :href="'{{ route('validator.analisa-kinerja.index') }}?triwulan=' + tw.kode" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                            <p class="font-mono text-xl font-bold text-amber-600" x-text="tw.analisa.menunggu_validasi"></p>
                            <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
                        </a>
                        <a :href="'{{ route('validator.analisa-kinerja.index') }}?triwulan=' + tw.kode" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                            <p class="font-mono text-xl font-bold text-emerald-600" x-text="tw.analisa.disetujui"></p>
                            <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
                        </a>
                        <a :href="'{{ route('validator.analisa-kinerja.index') }}?triwulan=' + tw.kode" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                            <p class="font-mono text-xl font-bold text-rose-600" x-text="tw.analisa.ditolak"></p>
                            <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
                        </a>
                    </div>
                    <div class="mt-3">
                        <canvas :id="'analisaChart-' + tw.id" height="145"></canvas>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const barColors = ['#f59e0b', '#10b981', '#f43f5e']; // Menunggu, Disetujui, Ditolak

        new Chart(document.getElementById('usulanStatusChart'), {
            type: 'bar',
            data: {
                labels: ['Menunggu Validasi', 'Disetujui', 'Ditolak'],
                datasets: [{
                    label: 'Jumlah Usulan',
                    data: [
                        {{ $usulan['menunggu_validasi'] }},
                        {{ $usulan['approved'] }},
                        {{ $usulan['rejected'] }}
                    ],
                    backgroundColor: barColors,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        new Chart(document.getElementById('dataProkerPieChart'), {
            type: 'pie',
            data: {
                labels: ['Kunjungan Lapangan', 'Lainnya', 'Draft'],
                datasets: [{
                    data: [
                        {{ $dataProker['kunjungan_lapangan'] }},
                        {{ $dataProker['lainnya'] }},
                        {{ $dataProker['draft'] }}
                    ],
                    backgroundColor: ['#0e6b63', '#94a3b8', '#e2e8f0'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } },
        });

        new Chart(document.getElementById('kalenderProkerChart'), {
            type: 'bar',
            data: {
                labels: @json(array_map(fn ($b) => $bulanIndo[$b], array_keys($kalenderBerjalan['per_bulan']))),
                datasets: [{
                    label: 'Jumlah Proker',
                    data: @json(array_values($kalenderBerjalan['per_bulan'])),
                    backgroundColor: '#22969c',
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        new Chart(document.getElementById('pelaporanChart'), {
            type: 'bar',
            data: {
                labels: ['Belum Diunggah', 'Menunggu Validasi', 'Disetujui', 'Ditolak'],
                datasets: [{
                    label: 'Jumlah Dokumen',
                    data: [
                        {{ $pelaporan['belum_diunggah'] }},
                        {{ $pelaporan['menunggu_validasi'] }},
                        {{ $pelaporan['disetujui'] }},
                        {{ $pelaporan['ditolak'] }}
                    ],
                    backgroundColor: ['#94a3b8', '#f59e0b', '#10b981', '#f43f5e'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        new Chart(document.getElementById('ptsTaggingPieChart'), {
            type: 'pie',
            data: {
                labels: ['Sudah Ditagging', 'Belum Ditagging'],
                datasets: [{
                    data: [
                        {{ $ptsTagging['total_pts_ditagging'] }},
                        {{ $ptsTagging['total_pts_belum_ditagging'] }}
                    ],
                    backgroundColor: ['#0e6b63', '#e2e8f0'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } },
        });

        // Chart per-triwulan: dibuat sekali untuk SEMUA triwulan (tersembunyi via x-show),
        // supaya switch tab tidak perlu re-render chart / fetch ulang.
        const triwulanData = @json($triwulanList);
        triwulanData.forEach(function (tw) {
            const capaianEl = document.getElementById('capaianChart-' + tw.id);
            if (capaianEl) {
                new Chart(capaianEl, {
                    type: 'bar',
                    data: {
                        labels: ['Menunggu Validasi', 'Disetujui', 'Ditolak'],
                        datasets: [{
                            label: 'Capaian Kinerja',
                            data: [tw.capaian.menunggu_validasi, tw.capaian.disetujui, tw.capaian.ditolak],
                            backgroundColor: barColors,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } },
                });
            }

            const analisaEl = document.getElementById('analisaChart-' + tw.id);
            if (analisaEl) {
                new Chart(analisaEl, {
                    type: 'bar',
                    data: {
                        labels: ['Menunggu Validasi', 'Disetujui', 'Ditolak'],
                        datasets: [{
                            label: 'Analisis Kinerja',
                            data: [tw.analisa.menunggu_validasi, tw.analisa.disetujui, tw.analisa.ditolak],
                            backgroundColor: barColors,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } },
                });
            }
        });
    });
</script>
@endpush