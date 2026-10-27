@extends('admin.layout.app')

@section('title', 'Sinkronisasi Data')
@section('subtitle', 'Sinkronkan data PTS dan akreditasi dari '.$namaSumber)

@section('content')
    @php
        $jenisLabel = ['pts' => 'Master PTS', 'akreditasi' => 'Akreditasi PTS'];
        $statusBadge = [
            'diproses' => ['Diproses', 'bg-amber-50 text-amber-700'],
            'berhasil' => ['Berhasil', 'bg-emerald-50 text-emerald-700'],
            'gagal' => ['Gagal', 'bg-rose-50 text-rose-700'],
        ];
        $sedangJalan = count($idsDiproses) > 0;
        $nonaktif = $sedangJalan || $masalahKonfigurasi;
        $tombolAktif = 'bg-brand-600 hover:bg-brand-700';
        $tombolMati = 'cursor-not-allowed bg-slate-200 text-slate-400';
        $fmt = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('d/m/Y H:i') : 'Belum pernah';
    @endphp

    <div
        x-data="{
            ids: @js($idsDiproses),
            timer: null,
            init() { if (this.ids.length) { this.timer = setInterval(() => this.cek(), 4000); } },
            async cek() {
                try {
                    const res = await fetch('{{ route('admin.tools.sinkronisasi.status') }}?' + this.ids.map(i => 'ids[]=' + i).join('&'), { headers: { Accept: 'application/json' } });
                    if (! res.ok) return;
                    const data = await res.json();
                    if (this.ids.some(id => data[id] !== 'diproses')) { clearInterval(this.timer); window.location.reload(); }
                } catch (e) { /* polling berikutnya akan coba lagi */ }
            },
        }"
    >
        @if ($masalahKonfigurasi)
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <p class="text-sm font-medium text-amber-800">{{ $masalahKonfigurasi }}</p>
            </div>
        @endif

        @if ($sedangJalan)
            <div class="mt-4 flex items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4">
                <svg class="h-5 w-5 shrink-0 animate-spin text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <p class="text-sm font-medium text-brand-700">Sinkronisasi sedang berjalan. Halaman diperbarui otomatis saat selesai.</p>
            </div>
        @endif

        @if ($macet->isNotEmpty())
            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
                <p class="text-sm font-semibold text-rose-700">Ada sinkronisasi yang tidak selesai lebih dari {{ $menitMacet }} menit.</p>
                <p class="mt-1 text-xs text-rose-700">Worker antrean kemungkinan tidak berjalan. Tandai gagal agar sinkronisasi baru dapat dijalankan.</p>
                <div class="mt-3 space-y-2">
                    @foreach ($macet as $log)
                        <form method="POST" action="{{ route('admin.tools.sinkronisasi.tandai-gagal', $log) }}" class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2">
                            @csrf
                            @method('PUT')
                            <span class="text-xs text-slate-600">#{{ $log->id }} — {{ $jenisLabel[$log->jenis] ?? $log->jenis }} — dimulai {{ $log->created_at->format('d/m/Y H:i') }}</span>
                            <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">Tandai Gagal</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
                <h3 class="text-sm font-bold text-ink-900">Master PTS</h3>
                <p class="mt-1 text-xs text-slate-500">PTS berstatus {{ implode(' dan ', config('pddikti.status_diambil')) }} di {{ implode(' dan ', config('pddikti.provinsi')) }}: nama, provinsi, status, dan akreditasi.</p>
                <p class="mt-3 text-xs text-slate-400">Terakhir berhasil: {{ $fmt($terakhirBerhasil['pts'] ?? null) }}</p>
                <button type="button" @click="$refs['konfirmasi-pts'].showModal()" {{ $nonaktif ? 'disabled' : '' }}
                        class="mt-4 rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ $nonaktif ? $tombolMati : $tombolAktif }}">
                    Sinkronkan PTS
                </button>
            </div>

            <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
                <h3 class="text-sm font-bold text-ink-900">Akreditasi PTS</h3>
                <p class="mt-1 text-xs text-slate-500">Data SK akreditasi terkini per PTS, dipakai tombol import di Capaian Kinerja Arsitektur PTS.</p>
                <p class="mt-3 text-xs text-slate-400">Terakhir berhasil: {{ $fmt($terakhirBerhasil['akreditasi'] ?? null) }}</p>
                <button type="button" @click="$refs['konfirmasi-akreditasi'].showModal()" {{ $nonaktif ? 'disabled' : '' }}
                        class="mt-4 rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ $nonaktif ? $tombolMati : $tombolAktif }}">
                    Sinkronkan Akreditasi
                </button>
            </div>

            <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card">
                <h3 class="text-sm font-bold text-ink-900">Sinkronkan Semua</h3>
                <p class="mt-1 text-xs text-slate-500">Master PTS dulu, lalu akreditasi, supaya PTS baru sudah ada saat akreditasi diproses.</p>
                <button type="button" @click="$refs['konfirmasi-semua'].showModal()" {{ $nonaktif ? 'disabled' : '' }}
                        class="mt-auto rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ $nonaktif ? $tombolMati : 'bg-ink-900 hover:bg-ink-800' }}">
                    Sinkronkan Semua
                </button>
            </div>
        </div>

        <x-confirm-action refName="konfirmasi-pts" :action="route('admin.tools.sinkronisasi.pts')"
            title="Sinkronkan master PTS?"
            body="Data PTS yang cocok dengan API akan diperbarui. Perubahan manual pada PTS yang masih ada di API akan ditimpa."
            confirmLabel="Ya, sinkronkan" />
        <x-confirm-action refName="konfirmasi-akreditasi" :action="route('admin.tools.sinkronisasi.akreditasi')"
            title="Sinkronkan akreditasi PTS?"
            body="Data akreditasi lokal akan diperbarui dari API. PTS yang belum ada di database dilewati."
            confirmLabel="Ya, sinkronkan" />
        <x-confirm-action refName="konfirmasi-semua" :action="route('admin.tools.sinkronisasi.semua')"
            title="Sinkronkan master PTS dan akreditasi?"
            body="Master PTS diproses lebih dulu, lalu akreditasi. Perubahan manual pada PTS yang masih ada di API akan ditimpa."
            confirmLabel="Ya, sinkronkan" />

        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-card">
            <p class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-ink-900">Riwayat Sinkronisasi</p>
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-36 px-5 py-3 font-semibold">Waktu</th>
                        <th class="w-36 px-5 py-3 font-semibold">Jenis</th>
                        <th class="w-28 px-5 py-3 font-semibold">Status</th>
                        <th class="w-36 px-5 py-3 font-semibold">Dipicu oleh</th>
                        <th class="px-5 py-3 font-semibold">Hasil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayat as $log)
                        @php
                            $r = $log->ringkasan ?? [];
                            [$labelStatus, $kelasStatus] = $statusBadge[$log->status] ?? [$log->status, 'bg-slate-100 text-slate-600'];
                        @endphp
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} align-top">
                            <td class="px-5 py-3 text-slate-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $jenisLabel[$log->jenis] ?? $log->jenis }}</td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full {{ $kelasStatus }} px-2.5 py-0.5 text-xs font-semibold">{{ $labelStatus }}</span></td>
                            <td class="px-5 py-3 text-slate-600">{{ $log->pemicu->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-600">
                                @if ($log->status === 'berhasil' && $r)
                                    <p>{{ $r['dibuat'] ?? 0 }} baru · {{ $r['diperbarui'] ?? 0 }} diperbarui · {{ $r['tidak_berubah'] ?? 0 }} tidak berubah · {{ array_sum($r['dilewati'] ?? []) }} dilewati</p>
                                    @if (($r['manual_ditimpa'] ?? 0) > 0)
                                        <p class="mt-0.5 text-amber-700">{{ $r['manual_ditimpa'] }} data manual ditimpa API</p>
                                    @endif
                                    @if (($r['tidak_muncul_total'] ?? 0) > 0)
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-amber-700">{{ $r['tidak_muncul_total'] }} PTS lokal tidak muncul lagi di API</summary>
                                            <p class="mt-1 break-words">{{ implode(', ', $r['tidak_muncul'] ?? []) }}@if ($r['tidak_muncul_total'] > count($r['tidak_muncul'] ?? [])) …dan lainnya @endif</p>
                                        </details>
                                    @endif
                                    @if (($r['peringatan_total'] ?? 0) > 0)
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-amber-700">{{ $r['peringatan_total'] }} peringatan</summary>
                                            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                                                @foreach ($r['peringatan'] ?? [] as $p)<li>{{ $p }}</li>@endforeach
                                            </ul>
                                            @if ($r['peringatan_total'] > count($r['peringatan'] ?? []))<p class="mt-1">…dan lainnya</p>@endif
                                        </details>
                                    @endif
                                @elseif ($log->status === 'gagal')
                                    <p class="text-rose-700">{{ $log->catatan }}</p>
                                @elseif ($log->status === 'diproses')
                                    <p class="text-slate-400">Menunggu atau sedang diproses…</p>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">Belum pernah ada sinkronisasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($riwayat->hasPages())
            <div class="mt-4">{{ $riwayat->links() }}</div>
        @endif

        @if (config('pddikti.driver') === 'lldikti12')
            <p class="mt-3 text-xs text-slate-400">Sumber: LLDIKTI Wilayah XII</p>
        @endif
    </div>
@endsection