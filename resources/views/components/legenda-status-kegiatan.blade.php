{{-- Legenda warna status kegiatan pada Kalender Proker. --}}
<div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 rounded-xl bg-white px-4 py-2.5 text-xs text-slate-600 shadow-card">
    <span class="font-semibold text-ink-900">Status Kegiatan:</span>
    @foreach (\App\Models\ProgramKerja::WARNA_STATUS_KEGIATAN as $status => $warna)
        <span class="inline-flex items-center gap-1.5">
            <span class="h-3 w-3 rounded-full {{ strtok($warna, ' ') }}"></span>{{ $status }}
        </span>
    @endforeach
</div>
