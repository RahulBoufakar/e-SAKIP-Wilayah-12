@props(['status'])

@php
    $map = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600'],
        'menunggu_validasi' => ['label' => 'Menunggu Validasi', 'class' => 'bg-amber-50 text-amber-700'],
        'disetujui' => ['label' => 'Disetujui', 'class' => 'bg-emerald-50 text-emerald-700'],
        'ditolak' => ['label' => 'Ditolak', 'class' => 'bg-rose-50 text-rose-700'],
    ];
    $badge = $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-600'];
@endphp

<span class="inline-flex items-center rounded-full {{ $badge['class'] }} px-2.5 py-0.5 text-xs font-semibold">{{ $badge['label'] }}</span>