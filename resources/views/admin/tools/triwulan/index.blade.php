@extends('admin.layout.app')

@section('title', 'Setting Triwulan')
@section('subtitle', 'Aktifkan satu Triwulan untuk tahun anggaran berjalan')

@section('content')
<div x-data="{ modalNonaktifkan: false }">
    {{-- Tombol Nonaktifkan Semua --}}
    <div class="mb-6 flex justify-end">
        <button type="button" 
                @click="modalNonaktifkan = true"
                class="inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-red-300 bg-white/50 px-6 py-3 text-sm font-semibold text-red-500 hover:bg-red-50 hover:border-red-400 hover:text-red-600 transition-all duration-200 backdrop-blur-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Nonaktifkan Semua
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($triwulanStatusList as $tw)
            @php
                $status = $tw->statuses->first()?->status ?? 'non_aktif';
                $isAktif = $status === 'aktif';
            @endphp
            <div class="flex flex-col items-center gap-4 rounded-2xl bg-white p-6 text-center shadow-card {{ $isAktif ? 'ring-2 ring-brand-500' : '' }}">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $isAktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isAktif ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    {{ $isAktif ? 'Aktif' : 'Non-aktif' }}
                </span>
                <p class="font-mono text-2xl font-bold text-ink-900">{{ $tw->kode }}</p>

                @if ($isAktif)
                    <button type="button" disabled class="w-full cursor-not-allowed rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">Sedang Aktif</button>
                @else
                    <form method="POST" action="{{ route('admin.tools.triwulan.update', $tw->id) }}" class="w-full">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="tahun_anggaran_id" value="{{ $tahunAnggaranId }}">
                        <button type="submit" class="w-full rounded-lg border border-brand-600 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">Aktifkan</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-6 text-xs text-slate-400">Mengaktifkan satu Triwulan akan otomatis menonaktifkan Triwulan lain pada tahun anggaran yang sama.</p>

    {{-- Modal Konfirmasi Nonaktifkan Semua --}}
    <div x-show="modalNonaktifkan" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div x-show="modalNonaktifkan" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalNonaktifkan = false"></div>
        
        <div x-show="modalNonaktifkan" 
            x-transition:enter="transition ease-out duration-150" 
            x-transition:enter-start="opacity-0 scale-95" 
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            
            {{-- Header Modal --}}
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-ink-900">Konfirmasi Nonaktifkan</h3>
                <button type="button" @click="modalNonaktifkan = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="mt-4 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h4 class="text-base font-semibold text-ink-900">Anda yakin ingin menonaktifkan semua triwulan?</h4>
                <p class="mt-2 text-sm text-slate-500">
                    Semua triwulan pada tahun anggaran {{ \App\Models\TahunAnggaran::find($tahunAnggaranId)->tahun }} akan dinonaktifkan.
                    Tidak akan ada triwulan yang aktif setelah ini.
                </p>
            </div>

            {{-- Footer Modal --}}
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="modalNonaktifkan = false" 
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <form method="POST" action="{{ route('admin.tools.triwulan.nonaktifkan-semua', $tahunAnggaranId) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" 
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors">
                        Ya, Nonaktifkan Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
