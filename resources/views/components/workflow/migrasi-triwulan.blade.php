@props(['iku', 'triwulanId', 'ringkasan'])

@php $aksi = $ringkasan['baru'] + $ringkasan['perbarui']; @endphp

@if ($aksi > 0 || $ringkasan['terkunci'] > 0 || $ringkasan['bentrok'] > 0)
    <div class="flex flex-wrap items-center gap-3">
        @if ($aksi > 0)
            <form method="POST" action="{{ route('tim-kerja.capaian-kinerja.migrasi-triwulan', $iku->id) }}"
                  onsubmit="return confirm('Migrasi data tervalidasi dari triwulan sebelumnya? {{ $ringkasan['baru'] }} data baru, {{ $ringkasan['perbarui'] }} diperbarui. Data yang sudah ada dilewati. File bukti dukung dipakai bersama, tidak diduplikasi.')">
                @csrf
                <input type="hidden" name="triwulan_id" value="{{ $triwulanId }}">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-brand-600 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">
                    Migrasi dari Triwulan Sebelumnya ({{ $ringkasan['baru'] }} baru, {{ $ringkasan['perbarui'] }} diperbarui)
                </button>
            </form>
        @endif
        @if ($ringkasan['terkunci'] > 0)
            <p class="text-xs text-amber-700">{{ $ringkasan['terkunci'] }} data sudah divalidasi di triwulan ini dan berbeda dari triwulan sebelumnya (tidak diubah).</p>
        @endif
        @if ($ringkasan['bentrok'] > 0)
            <p class="text-xs text-amber-700">{{ $ringkasan['bentrok'] }} data tidak dapat diperbarui karena kuncinya bentrok dengan data lain.</p>
        @endif
    </div>
@endif