@extends('tim-kerja.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan kinerja Tim Kerja Anda')

@section('content')
    @php
        $rataCapaianDisplay = $rataCapaian !== null
            ? rtrim(rtrim(number_format($rataCapaian, 2, ',', '.'), '0'), ',').'%'
            : '—';
        $bulanIndoDashboard = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    @endphp

    {{-- ================= USULAN PROGRAM KERJA ================= --}}
    <div class="rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Usulan Program Kerja (Tahun {{ $activeTahun }})</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-100 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                <p class="font-mono text-xl font-bold text-slate-600">{{ $usulanStatusBreakdown['draft'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Draft</p>
            </a>
            <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                <p class="font-mono text-xl font-bold text-amber-600">{{ $usulanStatusBreakdown['menunggu_validasi'] }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
            </a>
            <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                <p class="font-mono text-xl font-bold text-emerald-600">{{ $usulanStatusBreakdown['approved'] }}</p>
                <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
            </a>
            <a href="{{ route('tim-kerja.usulan-program-kerja.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                <p class="font-mono text-xl font-bold text-rose-600">{{ $usulanStatusBreakdown['rejected'] }}</p>
                <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="usulanStatusChart" height="160"></canvas>
        </div>

        @if ($usulanPerIku->isNotEmpty())
            <div class="mt-4 border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold text-slate-500">Usulan per IKU</p>
                <div class="mt-2 max-h-32 space-y-1.5 overflow-y-auto">
                    @foreach ($usulanPerIku as $row)
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs">
                            <span class="min-w-0 truncate font-medium text-ink-900">{{ $row->iku->kode ?? '—' }}</span>
                            <span class="shrink-0 font-mono font-semibold text-brand-700">{{ $row->total }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ================= DATA PROKER ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Data Proker — Jenis Kegiatan (Tahun {{ $activeTahun }})</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-brand-50 p-3 text-center transition hover:ring-2 hover:ring-brand-400">
                <p class="font-mono text-xl font-bold text-brand-700">{{ $dataProker['total'] }}</p>
                <p class="mt-1 text-xs font-medium text-brand-700">Total Disetujui</p>
            </a>
            <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-cyan-50 p-3 text-center transition hover:ring-2 hover:ring-cyan-400">
                <p class="font-mono text-xl font-bold text-cyan-700">{{ $dataProker['kunjungan_lapangan'] }}</p>
                <p class="mt-1 text-xs font-medium text-cyan-700">Kunjungan Lapangan</p>
            </a>
            <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-100 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                <p class="font-mono text-xl font-bold text-slate-600">{{ $dataProker['lainnya'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Lainnya</p>
            </a>
            <a href="{{ route('tim-kerja.data-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-50 p-3 text-center transition hover:ring-2 hover:ring-slate-300">
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
            <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'berjalan']) }}"
               class="flex-1 px-3 py-2 text-center text-xs font-semibold text-slate-500 hover:bg-slate-50">
                Tahun Ini ({{ $activeTahun }}) — {{ $kalenderBerjalan['total'] }} Jadwal
            </a>
            @if ($nextYearAvailable)
                <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'h_plus_1']) }}"
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
                <a href="{{ route('tim-kerja.kalender-proker.index', ['tahun' => 'berjalan']) }}" class="rounded-lg bg-slate-50 p-2 text-center transition hover:bg-brand-50">
                    <p class="font-mono text-sm font-bold text-ink-900">{{ $jumlah }}</p>
                    <p class="text-[10px] font-semibold text-slate-400">{{ $bulanIndoDashboard[$bulan] }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-3">
            <canvas id="kalenderProkerChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= PELAPORAN KEGIATAN ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Progress Validasi Dokumen Pelaporan Kegiatan (Tahun {{ $activeTahun }})</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-slate-50 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                <p class="font-mono text-xl font-bold text-slate-500">{{ $pelaporan['belum_diunggah'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Belum Diunggah</p>
            </a>
            <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                <p class="font-mono text-xl font-bold text-amber-600">{{ $pelaporan['menunggu_validasi'] }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
            </a>
            <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                <p class="font-mono text-xl font-bold text-emerald-600">{{ $pelaporan['disetujui'] }}</p>
                <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
            </a>
            <a href="{{ route('tim-kerja.pelaporan-kegiatan.index', ['tahun' => 'berjalan']) }}" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                <p class="font-mono text-xl font-bold text-rose-600">{{ $pelaporan['ditolak'] }}</p>
                <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="pelaporanChart" height="160"></canvas>
        </div>
    </div>

    {{-- ================= TAGGING PTS (PER IKU) ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Tagging PTS per IKU</p>
        <p class="mt-1 text-xs text-slate-400">Perbandingan jumlah PTS yang sudah ditagging dari total {{ $ptsTagging['total_pts'] }} PTS di sistem, per IKU Tim Kerja Anda.</p>
        @if ($ptsTagging['per_iku']->isNotEmpty())
            <div class="mt-3">
                <canvas id="ptsTaggingChart" height="180"></canvas>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-400">Belum ada IKU untuk Tim Kerja Anda pada tahun anggaran ini.</p>
        @endif
    </div>

    {{-- ================= CAPAIAN KINERJA ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">
            Capaian Kinerja @if ($triwulanAktif) ({{ $triwulanAktif->triwulan->kode }}) @endif
        </p>
        <div class="mt-4 grid grid-cols-2 gap-4">
            <a href="{{ route('tim-kerja.capaian-kinerja.index') }}" class="rounded-xl bg-brand-50 p-3 text-center transition hover:ring-2 hover:ring-brand-400">
                <p class="font-mono text-xl font-bold text-brand-700">{{ $rataCapaianDisplay }}</p>
                <p class="mt-1 text-xs font-medium text-brand-700">Rata-rata Realisasi (thd Target PK)</p>
            </a>
            <a href="{{ route('tim-kerja.capaian-kinerja.index') }}" class="rounded-xl bg-cyan-50 p-3 text-center transition hover:ring-2 hover:ring-cyan-400">
                <p class="font-mono text-xl font-bold text-cyan-700">{{ $kelengkapanRealisasi['terisi'] ?? 0 }}/{{ $kelengkapanRealisasi['total'] ?? 0 }}</p>
                <p class="mt-1 text-xs font-medium text-cyan-700">Kelengkapan Realisasi ({{ $kelengkapanRealisasi['persen'] ?? 0 }}%)</p>
            </a>
        </div>

        @if ($ikuCapaianChart->isNotEmpty())
            <div class="mt-4 w-full min-w-0">
                <p class="text-xs font-semibold text-slate-500">Nilai Realisasi per IKU — (Realisasi ÷ Target PK) × 100%</p>
                <div class="relative mt-2 h-56 w-full max-w-full overflow-hidden">
                    <canvas id="capaianPersenChart"></canvas>
                </div>
            </div>

            <div class="mt-6 w-full min-w-0">
                <p class="text-xs font-semibold text-slate-500">Target vs Realisasi per IKU (Nilai Absolut)</p>
                <div class="relative mt-2 h-56 w-full max-w-full overflow-hidden">
                    <canvas id="capaianPerIkuChart"></canvas>
                </div>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-400">Belum ada data capaian untuk triwulan berjalan.</p>
        @endif
    </div>

    {{-- ================= ANALISIS KINERJA ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">
            Analisis Kinerja @if ($triwulanAktif) ({{ $triwulanAktif->triwulan->kode }}) @endif
        </p>
        @if ($analisaBreakdown)
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <a href="{{ route('tim-kerja.analisa-kinerja.index') }}" class="rounded-xl bg-slate-100 p-3 text-center transition hover:ring-2 hover:ring-slate-400">
                    <p class="font-mono text-xl font-bold text-slate-600">{{ $analisaBreakdown['belum_diisi'] }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-500">Belum Diisi</p>
                </a>
                <a href="{{ route('tim-kerja.analisa-kinerja.index') }}" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                    <p class="font-mono text-xl font-bold text-amber-600">{{ $analisaBreakdown['menunggu_validasi'] }}</p>
                    <p class="mt-1 text-xs font-medium text-amber-700">Menunggu Validasi</p>
                </a>
                <a href="{{ route('tim-kerja.analisa-kinerja.index') }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                    <p class="font-mono text-xl font-bold text-emerald-600">{{ $analisaBreakdown['disetujui'] }}</p>
                    <p class="mt-1 text-xs font-medium text-emerald-700">Disetujui</p>
                </a>
                <a href="{{ route('tim-kerja.analisa-kinerja.index') }}" class="rounded-xl bg-rose-50 p-3 text-center transition hover:ring-2 hover:ring-rose-400">
                    <p class="font-mono text-xl font-bold text-rose-600">{{ $analisaBreakdown['ditolak'] }}</p>
                    <p class="mt-1 text-xs font-medium text-rose-700">Ditolak</p>
                </a>
            </div>
            <div class="mt-3">
                <canvas id="analisaChart" height="160"></canvas>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-400">Triwulan aktif belum diatur.</p>
        @endif
    </div>

    {{-- ================= PERLU REVISI ================= --}}
    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <div class="border-b border-slate-100 px-5 py-4">
            <p class="text-sm font-semibold text-ink-900">Perlu Revisi</p>
            <p class="text-xs text-slate-400">Item yang ditolak validator dan menunggu perbaikan Anda.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($itemDitolak as $item)
                <div class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ $item['modul'] }}</span>
                        <span class="font-mono text-xs font-semibold text-brand-700">{{ $item['iku_kode'] }}</span>
                        <span class="text-xs text-slate-400">{{ $item['triwulan'] }}</span>
                    </div>
                    <p class="mt-1.5 min-w-0 truncate text-sm font-medium text-ink-900">{{ $item['iku_deskripsi'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $item['catatan_revisi'] }}</p>
                    @if ($item['url'])
                        <a href="{{ $item['url'] }}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline">
                            Buka halaman sumber
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" /></svg>
                        </a>
                    @else
                        <p class="mt-2 text-xs italic text-slate-400">Halaman sumber belum tersedia.</p>
                    @endif
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-slate-400">Tidak ada item yang ditolak.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new Chart(document.getElementById('usulanStatusChart'), {
            type: 'bar',
            data: {
                labels: ['Draft', 'Menunggu Validasi', 'Disetujui', 'Ditolak'],
                datasets: [{
                    label: 'Jumlah Usulan',
                    data: [
                        {{ $usulanStatusBreakdown['draft'] }},
                        {{ $usulanStatusBreakdown['menunggu_validasi'] }},
                        {{ $usulanStatusBreakdown['approved'] }},
                        {{ $usulanStatusBreakdown['rejected'] }}
                    ],
                    backgroundColor: ['#94a3b8', '#f59e0b', '#10b981', '#f43f5e'],
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
                labels: @json(array_map(fn ($b) => $bulanIndoDashboard[$b], array_keys($kalenderBerjalan['per_bulan']))),
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

        @if ($ptsTagging['per_iku']->isNotEmpty())
        new Chart(document.getElementById('ptsTaggingChart'), {
            type: 'bar',
            data: {
                labels: @json($ptsTagging['per_iku']->pluck('kode')),
                datasets: [
                    { label: 'PTS Ditagging', data: @json($ptsTagging['per_iku']->pluck('ditagging')), backgroundColor: '#0e6b63' },
                    { label: 'Belum Ditagging', data: @json($ptsTagging['per_iku']->pluck('belum')), backgroundColor: '#e2e8f0' },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { stacked: true }, y: { stacked: true } },
                plugins: { legend: { position: 'bottom' } },
            },
        });
        @endif

        @if ($ikuCapaianChart->isNotEmpty())
        // Grouped bar: Realisasi (kiri) vs Target Triwulan (kanan), keduanya % thd Target PK.
        new Chart(document.getElementById('capaianPersenChart'), {
            type: 'bar',
            data: {
                labels: @json($ikuCapaianChart->pluck('kode')),
                datasets: [
                    { label: 'Realisasi (%)', data: @json($ikuCapaianChart->pluck('nilai_realisasi')), backgroundColor: '#22969c' },
                    { label: 'Target Triwulan (%)', data: @json($ikuCapaianChart->pluck('target_persen')), backgroundColor: '#94a3b8' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { suggestedMax: 100 } },
            },
        });

        new Chart(document.getElementById('capaianPerIkuChart'), {
            type: 'bar',
            data: {
                labels: @json($ikuCapaianChart->pluck('kode')),
                datasets: [
                    { label: 'Target', data: @json($ikuCapaianChart->pluck('target')), backgroundColor: '#94a3b8' },
                    { label: 'Realisasi', data: @json($ikuCapaianChart->pluck('realisasi')), backgroundColor: '#3fb5b8' },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
        @endif

        @if ($analisaBreakdown)
        new Chart(document.getElementById('analisaChart'), {
            type: 'bar',
            data: {
                labels: ['Belum Diisi', 'Menunggu Validasi', 'Disetujui', 'Ditolak'],
                datasets: [{
                    label: 'Jumlah IKU',
                    data: [
                        {{ $analisaBreakdown['belum_diisi'] }},
                        {{ $analisaBreakdown['menunggu_validasi'] }},
                        {{ $analisaBreakdown['disetujui'] }},
                        {{ $analisaBreakdown['ditolak'] }}
                    ],
                    backgroundColor: ['#94a3b8', '#f59e0b', '#10b981', '#f43f5e'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });
        @endif
    });
</script>
@endpush