@if ($publikasi)
    <p class="mt-1 text-xs text-slate-500">
        Diperbarui oleh {{ $publikasi->diperbaruiOleh->name ?? '—' }}
        @if ($publikasi->diperbaruiOleh?->timKerja->isNotEmpty())
            ({{ $publikasi->diperbaruiOleh->timKerja->pluck('nama_tim')->join(', ') }})
        @endif
        pada {{ ($publikasi->updated_at ?? $publikasi->created_at)?->format('d/m/Y H:i') ?? '—' }}
    </p>
@endif