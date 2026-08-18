@props(['status', 'catatanRevisi' => null])

@if ($status === 'ditolak' && $catatanRevisi)
    <div {{ $attributes->merge(['class' => 'mb-4 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4']) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <div>
            <p class="text-sm font-semibold text-rose-700">Pengajuan ditolak — perlu revisi</p>
            <p class="mt-1 text-sm text-rose-600">{{ $catatanRevisi }}</p>
        </div>
    </div>
@endif