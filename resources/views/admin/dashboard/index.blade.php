@extends('admin.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan Target Kinerja LLDikti Wilayah XII')

@section('content')
    @php
        $rataCapaianDisplay = $rataCapaian !== null
            ? rtrim(rtrim(number_format($rataCapaian, 2, ',', '.'), '0'), ',').'%'
            : '—';
    @endphp

    {{-- ================= RINGKASAN TARGET KINERJA ================= --}}
    <div class="rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Ringkasan Target Kinerja</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('admin.target-kinerja.index') }}" class="rounded-xl bg-brand-50 p-3 text-center transition hover:ring-2 hover:ring-brand-400">
                <p class="font-mono text-xl font-bold text-brand-700">{{ $jumlahSasaran }}</p>
                <p class="mt-1 text-xs font-medium text-brand-700">Sasaran Kegiatan</p>
            </a>
            <a href="{{ route('admin.iku-lldikti.index') }}" class="rounded-xl bg-cyan-50 p-3 text-center transition hover:ring-2 hover:ring-cyan-400">
                <p class="font-mono text-xl font-bold text-cyan-700">{{ $jumlahIku }}</p>
                <p class="mt-1 text-xs font-medium text-cyan-700">IKU</p>
            </a>
            <a href="{{ route('admin.iku-lldikti.index') }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                <p class="font-mono text-xl font-bold text-emerald-600">{{ $rataCapaianDisplay }}</p>
                <p class="mt-1 text-xs font-medium text-emerald-700">Rata-rata Capaian</p>
            </a>
            <a href="{{ route('admin.tools.triwulan.index') }}" class="rounded-xl bg-amber-50 p-3 text-center transition hover:ring-2 hover:ring-amber-400">
                <p class="font-mono text-xl font-bold text-amber-600">{{ $triwulanAktif->triwulan->kode ?? '—' }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Triwulan Aktif</p>
            </a>
        </div>
        <div class="mt-3">
            <canvas id="ikuTimChart" height="160"></canvas>
        </div>
    </div>

    {{-- Alert IKU tanpa Tim Kerja --}}
    @if ($ikuTanpaTim > 0)
        <div class="mt-5 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <p class="text-sm font-medium text-amber-800">
                {{ $ikuTanpaTim }} IKU pada tahun anggaran ini belum memiliki Tim Kerja.
            </p>
        </div>
    @endif

    {{-- ================= KELENGKAPAN DATA TRIWULAN AKTIF ================= --}}
    @if ($triwulanAktif)
        <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Kelengkapan Data — {{ $triwulanAktif->triwulan->kode }}</p>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-brand-50 p-3 text-center">
                    <p class="font-mono text-xl font-bold text-brand-700">{{ $kelengkapanRealisasi['terisi'] }}/{{ $kelengkapanRealisasi['total'] }}</p>
                    <p class="mt-1 text-xs font-medium text-brand-700">Realisasi Terisi ({{ $kelengkapanRealisasi['persen'] }}%)</p>
                </div>
                <div class="rounded-xl bg-cyan-50 p-3 text-center">
                    <p class="font-mono text-xl font-bold text-cyan-700">{{ $kelengkapanRencanaAksi['terisi'] }}/{{ $kelengkapanRencanaAksi['total'] }}</p>
                    <p class="mt-1 text-xs font-medium text-cyan-700">Rencana Aksi Terisi ({{ $kelengkapanRencanaAksi['persen'] }}%)</p>
                </div>
            </div>
            <div class="mt-3">
                <canvas id="kelengkapanChart" height="160"></canvas>
            </div>
        </div>
    @endif

    {{-- ================= REALISASI SASARAN KEGIATAN ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Realisasi Sasaran Kegiatan per Triwulan (%)</p>
        <p class="mt-1 text-xs text-slate-400">Rata-rata seluruh IKU, dibagi terhadap Target PK — Realisasi (kiri) dibandingkan Target Triwulan (kanan).</p>
        <div class="mt-3">
            <canvas id="realisasiSasaranChart" height="220"></canvas>
        </div>
    </div>

    {{-- ================= REALISASI & TARGET PER IKU — TRIWULAN AKTIF ================= --}}
    @if ($triwulanAktif && $ikuCapaianTriwulanChart->isNotEmpty())
        <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Realisasi & Target per IKU — {{ $triwulanAktif->triwulan->kode }}</p>
            <p class="mt-1 text-xs text-slate-400">Realisasi (kiri) dan Target Triwulan (kanan) tiap IKU, masing-masing % thd Target PK.</p>
            <div class="mt-3">
                <canvas id="ikuCapaianTriwulanChart" height="300"></canvas>
            </div>
        </div>
    @endif

    {{-- ================= SEBARAN IKU PER TIM KERJA ================= --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Sebaran IKU per Tim Kerja</p>
        <div class="mt-3">
            <canvas id="sebaranTimChart" height="220"></canvas>
        </div>
    </div>

    {{-- ================= TREN JUMLAH PTS ================= --}}
    @if ($trenPts->isNotEmpty())
        <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Tren Jumlah PTS Antar Tahun</p>
            <div class="mt-3">
                <canvas id="trenPtsChart" height="220"></canvas>
            </div>
        </div>
    @endif

    {{-- ================= TREN JUMLAH MAHASISWA ================= --}}
    @if ($trenMahasiswa->isNotEmpty())
        <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Tren Jumlah Mahasiswa Antar Tahun</p>
            <div class="mt-3">
                <canvas id="trenMahasiswaChart" height="220"></canvas>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new Chart(document.getElementById('ikuTimChart'), {
            type: 'pie',
            data: {
                labels: ['Dengan Tim Kerja', 'Tanpa Tim Kerja'],
                datasets: [{
                    data: [{{ $jumlahIku - $ikuTanpaTim }}, {{ $ikuTanpaTim }}],
                    backgroundColor: ['#0e6b63', '#f59e0b'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } },
        });

        @if ($triwulanAktif)
        new Chart(document.getElementById('kelengkapanChart'), {
            type: 'bar',
            data: {
                labels: ['Realisasi', 'Rencana Aksi'],
                datasets: [{
                    label: 'Persentase Terisi (%)',
                    data: [{{ $kelengkapanRealisasi['persen'] }}, {{ $kelengkapanRencanaAksi['persen'] }}],
                    backgroundColor: ['#22969c', '#0e6b63'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { max: 100 } },
            },
        });
        @endif

        // Grouped bar: Realisasi (kiri) vs Target Triwulan (kanan), keduanya % thd Target PK.
        new Chart(document.getElementById('realisasiSasaranChart'), {
            type: 'bar',
            data: {
                labels: @json($triwulanChartLabels),
                datasets: [
                    { label: 'Rata-rata Realisasi (%)', data: @json($rataRealisasiChart), backgroundColor: '#22969c' },
                    { label: 'Rata-rata Target Triwulan (%)', data: @json($rataTargetTriwulanChart), backgroundColor: '#94a3b8' },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { suggestedMax: 100 } },
            },
        });

        @if ($triwulanAktif && $ikuCapaianTriwulanChart->isNotEmpty())
            // Grouped bar per IKU: Realisasi (kiri) vs Target Triwulan (kanan), % thd Target PK.
            // indexAxis 'y' dipakai karena jumlah IKU bisa banyak — label kode lebih terbaca horizontal.
            new Chart(document.getElementById('ikuCapaianTriwulanChart'), {
                type: 'bar',
                data: {
                    labels: @json($ikuCapaianTriwulanChart->pluck('kode')),
                    datasets: [
                        { label: 'Realisasi (%)', data: @json($ikuCapaianTriwulanChart->pluck('realisasi_persen')), backgroundColor: '#22969c' },
                        { label: 'Target Triwulan (%)', data: @json($ikuCapaianTriwulanChart->pluck('target_persen')), backgroundColor: '#94a3b8' },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { x: { suggestedMax: 100 } },
                },
            });
        @endif

        new Chart(document.getElementById('sebaranTimChart'), {
            type: 'bar',
            data: {
                labels: @json($sebaranIkuPerTim->keys()),
                datasets: [
                    { label: 'Jumlah IKU', data: @json($sebaranIkuPerTim->values()), backgroundColor: '#17777e' },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });

        @if ($trenPts->isNotEmpty())
        new Chart(document.getElementById('trenPtsChart'), {
            type: 'line',
            data: {
                labels: @json($trenPts->keys()),
                datasets: [{
                    label: 'Jumlah PTS',
                    data: @json($trenPts->values()),
                    borderColor: '#155f66',
                    tension: 0.3,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });
        @endif

        @if ($trenMahasiswa->isNotEmpty())
        new Chart(document.getElementById('trenMahasiswaChart'), {
            type: 'line',
            data: {
                labels: @json($trenMahasiswa->keys()),
                datasets: [{
                    label: 'Jumlah Mahasiswa',
                    data: @json($trenMahasiswa->values()),
                    borderColor: '#22969c',
                    tension: 0.3,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });
        @endif
    });
</script>
@endpush