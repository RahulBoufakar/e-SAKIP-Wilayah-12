@extends('admin.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan Target Kinerja LLDikti Wilayah XII')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Sasaran Kegiatan</p>
            </div>
            <p class="mt-4 font-mono text-3xl font-bold text-ink-900">{{ $jumlahSasaran }}</p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">IKU</p>
            </div>
            <p class="mt-4 font-mono text-3xl font-bold text-ink-900">{{ $jumlahIku }}</p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 8.25h16.5M5.25 5.25h13.5c.828 0 1.5.672 1.5 1.5v10.5c0 .828-.672 1.5-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V6.75c0-.828.672-1.5 1.5-1.5z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">IKK</p>
            </div>
            <p class="mt-4 font-mono text-3xl font-bold text-ink-900"></p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">Triwulan Aktif</p>
            </div>
            @if ($triwulanAktif)
                <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 font-mono text-lg font-bold text-emerald-700">
                    {{ $triwulanAktif->triwulan->kode }}
                </span>
            @else
                <p class="mt-4 text-sm font-medium text-slate-400">Belum diatur</p>
            @endif
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white/60 p-6 text-center">
        <p class="text-sm text-slate-500">
            Ringkasan grafik tambahan (mis. sebaran IKK per Tim Kerja) dapat ditambahkan di sini
            begitu data pendukungnya tersedia dari controller — lihat Desain Sistem §6.
        </p>
    </div>
@endsection
