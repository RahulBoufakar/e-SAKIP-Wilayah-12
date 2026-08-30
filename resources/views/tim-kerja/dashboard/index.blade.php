@extends('tim-kerja.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan kinerja Tim Kerja Anda')

@section('content')
    {{-- (1) Statistic cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-500">Usulan Program Kerja (TA berjalan)</p>
            <p class="mt-2 font-mono text-3xl font-bold text-ink-900">{{ $jumlahUsulan }}</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Draft {{ $usulanStatusBreakdown['draft'] }}</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Menunggu {{ $usulanStatusBreakdown['menunggu_validasi'] }}</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Disetujui {{ $usulanStatusBreakdown['approved'] }}</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">Ditolak {{ $usulanStatusBreakdown['rejected'] }}</span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-500">Rata-rata Capaian @if ($triwulanAktif) ({{ $triwulanAktif->triwulan->kode }}) @endif</p>
            @if ($rataCapaian !== null)
                <p class="mt-2 font-mono text-3xl font-bold text-ink-900">{{ rtrim(rtrim(number_format($rataCapaian, 2, ',', '.'), '0'), ',') }}%</p>
            @else
                <p class="mt-2 text-sm font-medium text-slate-400">Belum ada data</p>
            @endif
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-500">Kelengkapan Realisasi</p>
            @if ($kelengkapanRealisasi)
                <p class="mt-2 font-mono text-3xl font-bold text-ink-900">{{ $kelengkapanRealisasi['terisi'] }}/{{ $kelengkapanRealisasi['total'] }}</p>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $kelengkapanRealisasi['persen'] }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">{{ $kelengkapanRealisasi['persen'] }}% terisi</p>
            @else
                <p class="mt-2 text-sm font-medium text-slate-400">Triwulan aktif belum diatur</p>
            @endif
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-500">Usulan per IKU</p>
            <div class="mt-3 max-h-32 space-y-1.5 overflow-y-auto">
                @forelse ($usulanPerIku as $row)
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="min-w-0 truncate font-medium text-ink-900">{{ $row->iku->kode ?? '—' }}</span>
                        <span class="shrink-0 font-mono font-semibold text-brand-700">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada usulan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- (2) Chart Capaian vs Target per IKU --}}
    <div class="mt-6 rounded-2xl bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-ink-900">
            Capaian vs Target per IKU @if ($triwulanAktif) ({{ $triwulanAktif->triwulan->kode }}) @endif
        </p>
        @if ($ikuCapaianChart->isNotEmpty())
            <div class="mt-4 max-w-4xl">
                <canvas id="capaianPerIkuChart" height="180" class="mx-auto w-full"></canvas>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-400">Belum ada data capaian untuk triwulan berjalan.</p>
        @endif
    </div>

    {{-- (3) Daftar item ditolak --}}
    <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-card">
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
    // Dibungkus DOMContentLoaded: <script type="module"> dari Vite (yang men-set
    // window.Chart) dijamin selesai dieksekusi sebelum event DOMContentLoaded.
    document.addEventListener('DOMContentLoaded', function () {
        @if ($ikuCapaianChart->isNotEmpty())
        new Chart(document.getElementById('capaianPerIkuChart'), {
            type: 'bar',
            data: {
                labels: @json($ikuCapaianChart->pluck('kode')),
                datasets: [
                    { label: 'Target', data: @json($ikuCapaianChart->pluck('target')), backgroundColor: '#22969c' },
                    { label: 'Realisasi', data: @json($ikuCapaianChart->pluck('realisasi')), backgroundColor: '#3fb5b8' },
                ],
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
        });
        @endif
    });
</script>
@endpush