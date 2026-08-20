@extends('validator.layout.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan validasi Usulan Program Kerja')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('validator.usulan-program-kerja.index', ['status' => 'menunggu_validasi']) }}" class="rounded-2xl bg-white p-5 shadow-card hover:ring-2 hover:ring-brand-500">
            <p class="text-sm font-medium text-slate-500">Menunggu Validasi</p>
            <p class="mt-2 font-mono text-3xl font-bold text-amber-600">{{ $jumlahMenunggu }}</p>
        </a>
        <a href="{{ route('validator.usulan-program-kerja.index', ['status' => 'approved']) }}" class="rounded-2xl bg-white p-5 shadow-card hover:ring-2 hover:ring-brand-500">
            <p class="text-sm font-medium text-slate-500">Disetujui</p>
            <p class="mt-2 font-mono text-3xl font-bold text-emerald-600">{{ $jumlahDisetujui }}</p>
        </a>
        <a href="{{ route('validator.usulan-program-kerja.index', ['status' => 'rejected']) }}" class="rounded-2xl bg-white p-5 shadow-card hover:ring-2 hover:ring-brand-500">
            <p class="text-sm font-medium text-slate-500">Ditolak</p>
            <p class="mt-2 font-mono text-3xl font-bold text-rose-600">{{ $jumlahDitolak }}</p>
        </a>
    </div>
@endsection