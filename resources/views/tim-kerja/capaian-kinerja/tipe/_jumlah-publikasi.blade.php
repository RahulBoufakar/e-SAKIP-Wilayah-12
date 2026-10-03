@php
    $publikasi = $capaian->jumlahPublikasi?->load('diperbaruiOleh.timKerja');
    $terkunci = in_array($capaian->status, ['menunggu_validasi', 'disetujui'], true);
    $bisaUbah = $isTriwulanAktif && ! $terkunci;
@endphp

<div class="mt-4 rounded-2xl bg-white p-5 shadow-card">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jumlah Publikasi (pembagi realisasi {{ $triwulanDipilih->kode }})</p>

    @if ($bisaUbah)
        <form method="POST" action="{{ route('tim-kerja.capaian-kinerja.jumlah-publikasi.update', $iku->id) }}" class="mt-2 flex flex-wrap items-start gap-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="triwulan_id" value="{{ $triwulanDipilih->id }}">
            <div>
                <input type="number" name="jumlah" min="0" step="1" required
                       value="{{ old('jumlah', $publikasi->jumlah ?? '') }}"
                       class="w-40 rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                @if ($errors->jumlahPublikasi->has('jumlah'))
                    <p class="mt-1 text-xs text-rose-600">{{ $errors->jumlahPublikasi->first('jumlah') }}</p>
                @endif
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
        </form>
    @else
        <p class="mt-1 text-2xl font-bold text-ink-900">{{ $publikasi->jumlah ?? '—' }}</p>
        @if ($terkunci)
            <p class="mt-1 text-xs text-amber-700">Terkunci karena data sedang menunggu validasi atau sudah disetujui.</p>
        @endif
    @endif

    @unless ($publikasi)
        <p class="mt-2 text-xs text-slate-400">Realisasi akan kosong sampai jumlah publikasi diisi.</p>
    @endunless
    @include('capaian-kinerja._jumlah-publikasi-info')
</div>