@extends('admin.layout.app')

@section('title', 'Tinjau File Master')
@section('subtitle', $fileExcel->nama_file)

@section('content')
    <a href="{{ route('admin.rab-generator.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-brand-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        Kembali ke daftar file master
    </a>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-card">
        <div>
            <p class="text-sm font-bold text-ink-900">{{ $fileExcel->nama_file }}</p>
            <p class="mt-1 text-xs text-slate-400">
                Diunggah {{ $fileExcel->tanggal_upload->format('d/m/Y H:i') }} oleh {{ $fileExcel->pengunggah->name ?? '—' }}
                · {{ $fileExcel->sheets->count() }} sheet
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if ($fileExcel->aktif)
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Aktif</span>
            @else
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Tidak aktif</span>
                <form method="POST" action="{{ route('admin.rab-generator.aktifkan', $fileExcel) }}"
                      onsubmit="return confirm('Aktifkan file master ini? File yang sedang aktif akan dinonaktifkan (tetap tersimpan).')">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Aktifkan</button>
                </form>
            @endif
        </div>
    </div>

    @if ($peringatanFile !== [])
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Peringatan hasil deteksi</p>
                <ul class="mt-1 list-disc pl-5 text-sm text-amber-700">
                    @foreach ($peringatanFile as $pesan)<li>{{ $pesan }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    @foreach ($fileExcel->sheets as $sheet)
        @php
            $bag = $errors->getBag('sheet'.$sheet->id);
            $pakaiOld = old('_sheet') == $sheet->id; // old() dipakai bersama semua form di halaman ini
            $nilai = fn ($kunci, $asal) => $pakaiOld ? old($kunci, $asal) : $asal;
        @endphp

        <form method="POST" action="{{ route('admin.rab-generator.sheet.update', $sheet) }}" class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            @csrf
            @method('PUT')
            <input type="hidden" name="_sheet" value="{{ $sheet->id }}">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="font-mono text-xs font-semibold text-brand-700">{{ $sheet->kode_sheet }}</p>
                    <h2 class="mt-0.5 text-base font-bold text-ink-900">{{ $sheet->nama_sheet }}</h2>
                    <p class="mt-1 text-xs text-slate-400">RO baris {{ $sheet->ro_baris_awal }}–{{ $sheet->ro_baris_akhir }} · kolom A–{{ $sheet->kolom_akhir }}</p>
                </div>
                <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan Koreksi</button>
            </div>

            @if ($bag->any())
                <div class="border-b border-rose-200 bg-rose-50 px-5 py-3">
                    <ul class="list-disc pl-5 text-sm font-medium text-rose-700">
                        @foreach ($bag->all() as $pesan)<li>{{ $pesan }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @if ($peringatanSheet[$sheet->id] !== [])
                <div class="border-b border-amber-200 bg-amber-50 px-5 py-3">
                    <p class="text-sm font-semibold text-amber-800">Peringatan</p>
                    <ul class="mt-1 list-disc pl-5 text-sm text-amber-700">
                        @foreach ($peringatanSheet[$sheet->id] as $pesan)<li>{{ $pesan }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 border-b border-slate-100 px-5 py-4 sm:grid-cols-2">
                @foreach (['header' => 'Header', 'footer' => 'Footer'] as $blok => $label)
                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            {{ $label }}
                            @if ($sheet->{$blok} && ! $sheet->{$blok}->otomatis)
                                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">diubah manual</span>
                            @endif
                        </p>
                        @if ($sheet->{$blok})
                            <div class="mt-1 flex items-center gap-2">
                                <input type="number" min="1" name="{{ $blok }}_awal" value="{{ $nilai($blok.'_awal', $sheet->{$blok}->baris_awal) }}"
                                       class="w-24 rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <span class="text-slate-400">s/d</span>
                                <input type="number" min="1" name="{{ $blok }}_akhir" value="{{ $nilai($blok.'_akhir', $sheet->{$blok}->baris_akhir) }}"
                                       class="w-24 rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        @else
                            <p class="mt-1 text-sm text-slate-400">Tidak terdeteksi.</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-48 px-5 py-2.5 font-semibold">Kategori › Kelompok</th>
                        <th class="px-5 py-2.5 font-semibold">Nama</th>
                        <th class="w-32 px-5 py-2.5 text-center font-semibold">Baris</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sheet->kategori as $kategori)
                        <tr class="bg-slate-50">
                            <td class="px-5 py-2.5 font-mono text-xs font-semibold text-brand-700">{{ $kategori->kode_kategori }}</td>
                            <td class="px-5 py-2.5 font-medium text-ink-900">{{ $kategori->nama_kategori }}</td>
                            <td class="px-5 py-2.5 text-center text-xs text-slate-400">{{ $kategori->baris_awal }}–{{ $kategori->baris_akhir }}</td>
                        </tr>
                        @foreach ($kategori->kelompok as $kelompok)
                            <tr class="hover:bg-brand-50/40">
                                <td class="py-2 pl-10 pr-5 font-mono text-xs text-slate-600">{{ $kategori->kode_kategori }} › {{ $kelompok->kode_kelompok }}</td>
                                <td class="px-5 py-2">
                                    <input type="text" maxlength="255" name="nama[{{ $kelompok->id }}]" placeholder="{{ $kelompok->label }}"
                                           value="{{ $nilai('nama.'.$kelompok->id, $kelompok->nama_kelompok) }}"
                                           class="w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                                </td>
                                <td class="px-5 py-2 text-center text-xs text-slate-400">{{ $kelompok->baris_awal }}–{{ $kelompok->baris_akhir }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada komponen terdeteksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    @endforeach
@endsection
