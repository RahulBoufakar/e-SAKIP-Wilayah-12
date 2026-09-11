@extends('pimpinan.layout.app')

@section('title', 'Dashboard Eksekutif')
@section('subtitle', 'Ringkasan capaian kinerja lintas seluruh Tim Kerja')

@section('content')
    @php
        $rataCapaianDisplay = $rataCapaian !== null
            ? rtrim(rtrim(number_format($rataCapaian, 2, ',', '.'), '0'), ',').'%'
            : '—';
    @endphp

    {{-- ================= RINGKASAN ================= --}}
    <div class="rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Ringkasan Capaian Kinerja</p>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('pimpinan.iku-lldikti.index') }}" class="rounded-xl bg-brand-50 p-3 text-center transition hover:ring-2 hover:ring-brand-400">
                <p class="font-mono text-xl font-bold text-brand-700">{{ $jumlahSasaran }}</p>
                <p class="mt-1 text-xs font-medium text-brand-700">Sasaran Kegiatan</p>
            </a>
            <a href="{{ route('pimpinan.iku-lldikti.index') }}" class="rounded-xl bg-cyan-50 p-3 text-center transition hover:ring-2 hover:ring-cyan-400">
                <p class="font-mono text-xl font-bold text-cyan-700">{{ $jumlahIku }}</p>
                <p class="mt-1 text-xs font-medium text-cyan-700">IKU</p>
            </a>
            <a href="{{ route('pimpinan.iku-lldikti.index') }}" class="rounded-xl bg-emerald-50 p-3 text-center transition hover:ring-2 hover:ring-emerald-400">
                <p class="font-mono text-xl font-bold text-emerald-600">{{ $rataCapaianDisplay }}</p>
                <p class="mt-1 text-xs font-medium text-emerald-700">Rata-rata Capaian (Triwulan Aktif)</p>
            </a>
            <div class="rounded-xl bg-amber-50 p-3 text-center">
                <p class="font-mono text-xl font-bold text-amber-600">{{ $triwulanAktif->triwulan->kode ?? '—' }}</p>
                <p class="mt-1 text-xs font-medium text-amber-700">Triwulan Aktif</p>
            </div>
        </div>
    </div>

    {{-- ================= IKU / TIM BERMASALAH ================= --}}
    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <div class="border-b border-slate-100 px-5 py-4">
            <p class="text-sm font-semibold text-ink-900">IKU Perlu Perhatian @if ($triwulanAktif) ({{ $triwulanAktif->triwulan->kode }}) @endif</p>
            <p class="text-xs text-slate-400">Belum diisi, ditolak validator, atau capaian di bawah 50%.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($ikuBermasalah as $item)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="shrink-0 font-mono text-xs font-semibold text-brand-700">{{ $item['kode'] }}</span>
                            <span class="min-w-0 truncate text-sm text-ink-900">{{ $item['deskripsi'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Tim Kerja: {{ $item['tim'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">{{ $item['masalah'] }}</span>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-slate-400">
                    @if ($triwulanAktif)
                        Semua IKU sudah terisi baik pada triwulan aktif. 🎉
                    @else
                        Triwulan aktif belum diatur.
                    @endif
                </p>
            @endforelse
        </div>
    </div>

    {{-- ================= TREN ANTAR TAHUN ================= --}}
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Tren Jumlah Mahasiswa Antar Tahun</p>
            <div class="mt-3">
                <canvas id="trenMahasiswaChart" height="200"></canvas>
            </div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-semibold text-ink-900">Tren Jumlah PTS Antar Tahun</p>
            <div class="mt-3">
                <canvas id="trenPtsChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">Sebaran IKU per Tim Kerja</p>
        <div class="mt-3">
            <canvas id="sebaranTimChart" height="220"></canvas>
        </div>
    </div>

    {{-- ================= LAPORAN KINERJA ================= --}}
    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <p class="text-sm font-semibold text-ink-900">Laporan Kinerja Terbaru</p>
                <p class="text-xs text-slate-400">5 laporan terakhir yang diterbitkan atau sedang diproses.</p>
            </div>
            <a href="{{ route('pimpinan.laporan.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Generate Laporan
            </a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($laporanTerbaru as $lap)
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink-900">{{ $lap->label }}</p>
                        <p class="text-xs text-slate-400">{{ ucfirst($lap->jenis) }} · {{ $lap->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <x-status-badge :status="$lap->status === 'berhasil' ? 'approved' : ($lap->status === 'gagal' ? 'rejected' : 'menunggu_validasi')" />
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-slate-400">Belum ada Laporan Kinerja yang diterbitkan.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new Chart(document.getElementById('trenMahasiswaChart'), {
            type: 'line',
            data: {
                labels: @json($trenMahasiswa->keys()),
                datasets: [{ label: 'Jumlah Mahasiswa', data: @json($trenMahasiswa->values()), borderColor: '#22969c', tension: 0.3 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        new Chart(document.getElementById('trenPtsChart'), {
            type: 'line',
            data: {
                labels: @json($trenPts->keys()),
                datasets: [{ label: 'Jumlah PTS', data: @json($trenPts->values()), borderColor: '#155f66', tension: 0.3 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        new Chart(document.getElementById('sebaranTimChart'), {
            type: 'bar',
            data: {
                labels: @json($sebaranTim->keys()),
                datasets: [{ label: 'Jumlah IKU', data: @json($sebaranTim->values()), backgroundColor: '#17777e' }],
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });
    });
</script>
@endpush
