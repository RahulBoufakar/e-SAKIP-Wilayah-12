{{--
    Partial dipakai bareng oleh:
      - resources/views/pimpinan/laporan/index.blade.php
      - resources/views/admin/tools/laporan/index.blade.php
    $routePrefix menentukan target route (mis. "pimpinan.laporan" atau "admin.tools.laporan-pimpinan").
--}}
<div
    x-data="{
        modalOpen: {{ $errors->any() ? 'true' : 'false' }},
        form: { tahun_anggaran_id: {{ $tahunAnggaranId }}, jenis: '{{ old('jenis', 'bulanan') }}', bulan: '{{ old('bulan', now()->month) }}', triwulan_id: '{{ old('triwulan_id', '') }}' },
    }"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Filter jenis --}}
        <div class="flex w-full overflow-hidden rounded-2xl bg-white shadow-card sm:w-auto">
            @foreach (['' => 'Semua', 'bulanan' => 'Bulanan', 'triwulanan' => 'Triwulanan', 'tahunan' => 'Tahunan'] as $key => $label)
                <a href="{{ route("{$routePrefix}.index", array_filter(['jenis' => $key, 'tahun_anggaran_id' => $tahunAnggaranId])) }}"
                   class="flex-1 px-4 py-2.5 text-center text-xs font-semibold transition-colors sm:flex-none sm:px-5
                          {{ ($jenis ?? '') === $key ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            {{-- Arsip tahun --}}
            <form method="GET" class="shrink-0">
                @if ($jenis)
                    <input type="hidden" name="jenis" value="{{ $jenis }}">
                @endif
                <select name="tahun_anggaran_id" onchange="this.form.submit()"
                        class="rounded-lg border-slate-200 bg-white text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($tahunOptions as $t)
                        <option value="{{ $t->id }}" @selected($tahunAnggaranId == $t->id)>TA {{ $t->tahun }}</option>
                    @endforeach
                </select>
            </form>

            <button @click="modalOpen = true" type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Generate Laporan
            </button>
        </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-ink-900 text-white">
                    <th class="w-28 px-5 py-3 font-semibold">Jenis</th>
                    <th class="px-5 py-3 font-semibold">Periode</th>
                    <th class="w-32 px-5 py-3 text-center font-semibold">Status</th>
                    <th class="w-44 px-5 py-3 font-semibold">Dibuat Oleh</th>
                    <th class="w-36 px-5 py-3 font-semibold">Tanggal</th>
                    <th class="w-28 px-5 py-3 text-center font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($laporanList as $lap)
                    <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40" data-laporan-id="{{ $lap->id }}" data-laporan-status="{{ $lap->status }}">
                        <td class="px-5 py-3 capitalize text-slate-600">{{ $lap->jenis }}</td>
                        <td class="px-5 py-3 font-medium text-ink-900">
                            {{ $lap->label_periode }}
                            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">v{{ $lap->versi }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if ($lap->status === 'berhasil')
                                <x-status-badge status="approved" />
                            @elseif ($lap->status === 'gagal')
                                <div x-data class="inline-block">
                                    <button type="button" @click="$refs['catatan-{{ $lap->id }}'].showModal()">
                                        <x-status-badge status="rejected" />
                                    </button>
                                    <dialog x-ref="catatan-{{ $lap->id }}" @click.self="$el.close()" class="m-auto w-full max-w-sm rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
                                        <div class="p-6">
                                            <p class="text-sm font-semibold text-ink-900">Laporan Gagal Dibuat</p>
                                            <p class="mt-2 text-sm text-slate-600">{{ $lap->catatan ?? 'Tidak ada catatan.' }}</p>
                                            <div class="mt-5 flex justify-end">
                                                <button type="button" @click="$refs['catatan-{{ $lap->id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                                            </div>
                                        </div>
                                    </dialog>
                                </div>
                            @else
                                <x-status-badge status="menunggu_validasi" />
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $lap->generatedBy?->name ?? 'Sistem (Otomatis)' }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $lap->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 text-center">
                            @if ($lap->status === 'berhasil')
                                <a href="{{ route("{$routePrefix}.unduh", $lap->id) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Unduh</a>
                            @else
                                <span class="text-xs text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada Laporan Kinerja untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($laporanList->hasPages())
        <div class="mt-4">{{ $laporanList->links() }}</div>
    @endif

    {{-- Modal Generate --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>

        <div x-show="modalOpen" x-transition class="relative flex max-h-[85vh] w-full max-w-sm flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-bold text-ink-900">Generate Laporan Kinerja</h3>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form method="POST" action="{{ route("{$routePrefix}.generate") }}" class="flex flex-1 flex-col overflow-hidden">
                @csrf

                <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">
                    <x-form.select label="Tahun Anggaran" name="tahun_anggaran_id" x-model.number="form.tahun_anggaran_id" required>
                        @foreach ($tahunOptions as $t)
                            <option value="{{ $t->id }}">TA {{ $t->tahun }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select label="Jenis Laporan" name="jenis" x-model="form.jenis" required>
                        <option value="bulanan">Bulanan</option>
                        <option value="triwulanan">Triwulanan</option>
                        <option value="tahunan">Tahunan</option>
                    </x-form.select>

                    <div x-show="form.jenis === 'bulanan'" x-cloak>
                        <x-form.select label="Bulan" name="bulan" x-model="form.bulan">
                            @foreach ($bulanIndo as $angka => $nama)
                                @continue($angka === 0)
                                <option value="{{ $angka }}">{{ $nama }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div x-show="form.jenis === 'triwulanan'" x-cloak>
                        <x-form.select label="Triwulan" name="triwulan_id" x-model.number="form.triwulan_id">
                            <option value="" disabled>Pilih triwulan</option>
                            @foreach ($triwulanList as $tw)
                                <option value="{{ $tw->id }}">{{ $tw->kode }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <p class="text-xs text-slate-400">Laporan diproses di latar belakang. Status akan diperbarui otomatis pada tabel di atas.</p>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Polling ringan (§7.1): selama ada baris 'diproses', cek status tiap 8 detik.
    // Begitu ada yang berubah status, reload halaman supaya tabel & badge ter-update.
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('[data-laporan-status="diproses"]');
        if (rows.length === 0) return;

        const ids = Array.from(rows).map((el) => el.dataset.laporanId);
        const statusUrl = '{{ route("{$routePrefix}.status") }}';

        const timer = setInterval(async () => {
            try {
                const res = await fetch(statusUrl + '?' + ids.map((id) => 'ids[]=' + id).join('&'), {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                const masihDiproses = ids.every((id) => data[id] === 'diproses');
                if (!masihDiproses) {
                    clearInterval(timer);
                    location.reload();
                }
            } catch (e) {
                // diamkan, coba lagi di interval berikutnya
            }
        }, 8000);
    });
</script>
@endpush
