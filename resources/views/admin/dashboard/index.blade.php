@extends('admin.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan Target Kinerja LLDikti Wilayah XII')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Sasaran Kegiatan</p>
            </div>
            <p class="mt-4 font-mono text-3xl font-bold text-ink-900">{{ $jumlahSasaran }}</p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">IKU</p>
            </div>
            <p class="mt-4 font-mono text-3xl font-bold text-ink-900">{{ $jumlahIku }}</p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Rata-rata Capaian</p>
            </div>
            @if ($rataCapaian !== null)
                <p class="mt-4 font-mono text-3xl font-bold text-ink-900">{{ rtrim(rtrim(number_format($rataCapaian, 2, ',', '.'), '0'), ',') }}%</p>
            @else
                <p class="mt-4 text-sm font-medium text-slate-400">Belum ada data</p>
            @endif
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Triwulan Aktif</p>
            </div>
            @if ($triwulanAktif)
                <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 font-mono text-lg font-bold text-emerald-700">
                    {{ $triwulanAktif->triwulan->kode }}
                </span>
            @else
                <p class="mt-4 text-sm font-medium text-slate-400">Belum diatur</p>
            @endif
        </div>
    </div>

    {{-- Alert IKU tanpa Tim Kerja --}}
    @if ($ikuTanpaTim > 0)
        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <p class="text-sm font-medium text-amber-800">
                {{ $ikuTanpaTim }} IKU pada tahun anggaran ini belum memiliki Tim Kerja.
            </p>
        </div>
    @endif

    {{-- Progress bar kelengkapan data triwulan aktif --}}
    @if ($triwulanAktif)
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-ink-900">Kelengkapan Realisasi ({{ $triwulanAktif->triwulan->kode }})</p>
                    <p class="font-mono text-sm font-semibold text-brand-700">{{ $kelengkapanRealisasi['terisi'] }}/{{ $kelengkapanRealisasi['total'] }}</p>
                </div>
                <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $kelengkapanRealisasi['persen'] }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">{{ $kelengkapanRealisasi['persen'] }}% terisi</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-ink-900">Kelengkapan Rencana Aksi ({{ $triwulanAktif->triwulan->kode }})</p>
                    <p class="font-mono text-sm font-semibold text-brand-700">{{ $kelengkapanRencanaAksi['terisi'] }}/{{ $kelengkapanRencanaAksi['total'] }}</p>
                </div>
                <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $kelengkapanRencanaAksi['persen'] }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">{{ $kelengkapanRencanaAksi['persen'] }}% terisi</p>
            </div>
        </div>
    @endif

    {{-- Chart Target vs Realisasi & Sebaran per Tim Kerja --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Target vs Realisasi per Triwulan</p>
            <div class="mt-4">
                <canvas id="targetRealisasiChart" height="220"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Sebaran IKU per Tim Kerja</p>
            <div class="mt-4">
                <canvas id="sebaranTimChart" height="220"></canvas>
            </div>
        </div>
    </div>

    {{-- Chart tren tahunan --}}
    @if ($trenTahunLabels->isNotEmpty())
        <div class="mt-6 rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Tren Jumlah Mahasiswa & PTS Antar Tahun</p>
            <div class="mt-4">
                <canvas id="trenChart" height="220"></canvas>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    new Chart(document.getElementById('targetRealisasiChart'), {
        type: 'bar',
        data: {
            labels: @json($triwulanChartLabels),
            datasets: [
                { label: 'Target', data: @json($targetChartData), backgroundColor: '#22969c' },
                { label: 'Realisasi', data: @json($realisasiChartData), backgroundColor: '#3fb5b8' },
            ],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

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
            plugins: { legend: { display: false } },
        },
    });

    @if ($trenTahunLabels->isNotEmpty())
    new Chart(document.getElementById('trenChart'), {
        type: 'line',
        data: {
            labels: @json($trenTahunLabels),
            datasets: [
                {
                    label: 'Jumlah Mahasiswa',
                    data: @json($trenTahunLabels->map(fn ($t) => $trenMahasiswa[$t] ?? 0)),
                    borderColor: '#22969c',
                    tension: 0.3,
                },
                {
                    label: 'Jumlah PTS',
                    data: @json($trenTahunLabels->map(fn ($t) => $trenPts[$t] ?? 0)),
                    borderColor: '#155f66',
                    tension: 0.3,
                },
            ],
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
    @endif
</script>
@endpush