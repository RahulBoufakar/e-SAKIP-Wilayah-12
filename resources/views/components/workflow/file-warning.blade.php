@props(['masalah'])

@if ($masalah->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4']) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        <div>
            <p class="text-sm font-semibold text-amber-800">Bukti dukung belum lengkap</p>
            <ul class="mt-1 list-disc pl-5 text-sm text-amber-700">
                @foreach ($masalah->take(10) as $m)
                    <li>{{ $m['baris'] }} — {{ $m['field'] }}:
                        {{ $m['jenis'] === 'wajib' ? 'wajib diunggah atau file tidak tersedia (memblokir pengiriman)' : 'file tidak tersedia' }}</li>
                @endforeach
            </ul>
            @if ($masalah->count() > 10)
                <p class="mt-1 text-xs text-amber-700">dan {{ $masalah->count() - 10 }} lainnya.</p>
            @endif
            <p class="mt-1 text-xs text-amber-700">Unggah ulang lewat tombol Edit pada baris terkait. Triwulan asal tidak terpengaruh.</p>
        </div>
    </div>
@endif