@extends('admin.layout.app')

@section('title', 'Sinkronisasi Data')
@section('subtitle', 'Placeholder — belum tersedia di versi ini')

@section('content')
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center shadow-card">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
        </div>
        <h2 class="mt-5 text-lg font-bold text-ink-900">Sinkronisasi Data</h2>
        <p class="mt-2 max-w-md text-sm text-slate-500">
            Fitur sinkronisasi data belum diaktifkan pada versi ini. Tombol di bawah sengaja
            dinonaktifkan sampai proses sinkronisasi tersedia.
        </p>
        <button type="button" disabled class="mt-6 inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            Mulai Sinkronisasi
        </button>
    </div>
@endsection
