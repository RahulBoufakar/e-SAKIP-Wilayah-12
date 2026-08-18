{{-- PRD §6.5 / API_Routes §6: dipakai controller saat data prasyarat (Tahun
     Anggaran) belum ada. Layout penuh tetap @extends, hanya isi content diganti.
     $layout/$backRoute opsional supaya view ini bisa dipakai lintas-role. --}}
@extends($layout ?? 'admin.layout.app')

@section('title', 'Data Belum Tersedia')

@section('content')
    <div class="flex min-h-[60vh] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-card">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <h2 class="mt-5 text-lg font-bold text-ink-900">Data Belum Tersedia</h2>
        <p class="mt-2 max-w-sm text-sm text-slate-500">{{ $errorMessage }}</p>
        <a href="{{ route($backRoute ?? 'admin.dashboard') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
            Kembali ke Dashboard
        </a>
    </div>
@endsection