@extends('admin.layout.app')

@section('title', 'Pengaturan')
@section('subtitle', 'Konfigurasi aplikasi, template dokumen, dan pengaturan lainnya')

@section('content')
    <!-- 1. BAGIAN TAB NAVIGASI -->
    <div class="flex w-full overflow-hidden rounded-t-2xl bg-white shadow-card">
        <a href="{{ route('admin.pengaturan.index', ['tab' => 'aplikasi']) }}"
           class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                  {{ $tab === 'aplikasi' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
            Aplikasi
        </a>
        
        <a href="{{ route('admin.pengaturan.index', ['tab' => 'template']) }}"
           class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                  {{ $tab === 'template' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
            Template Dokumen
        </a>
        
        <a href="{{ route('admin.pengaturan.index', ['tab' => 'lainnya']) }}"
           class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-semibold transition-colors
                  {{ $tab === 'lainnya' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-brand-600' }}">
            Lainnya
        </a>
    </div>

    <!-- 2. BAGIAN KONTEN TAB -->
    <div class="overflow-hidden rounded-b-2xl bg-white p-6 shadow-card">
        @if ($tab === 'aplikasi')
            <form method="POST" action="{{ route('admin.pengaturan.aplikasi.update') }}" enctype="multipart/form-data" class="max-w-lg space-y-4">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Nama Aplikasi"
                    name="nama_aplikasi"
                    type="text"
                    maxlength="100"
                    value="{{ old('nama_aplikasi', $pengaturanAplikasi->nama_aplikasi) }}"
                    required
                />

                <div>
                    <label class="block text-sm font-medium text-ink-900">Logo</label>
                    @if ($pengaturanAplikasi->logo_url)
                        <img src="{{ $pengaturanAplikasi->logo_url }}" alt="Logo" class="mt-2 h-12 w-12 rounded-lg border border-slate-200 object-contain">
                    @endif
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                    <p class="mt-1 text-xs text-slate-400">PNG/JPG/SVG/WEBP, maksimal 10 MB. Dipakai di sidebar Admin, Tim Kerja, dan Validator.</p>
                    @error('logo')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-900">Favicon</label>
                    @if ($pengaturanAplikasi->favicon_url)
                        <img src="{{ $pengaturanAplikasi->favicon_url }}" alt="Favicon" class="mt-2 h-8 w-8 rounded border border-slate-200 object-contain">
                    @endif
                    <input type="file" name="favicon" accept="image/png,image/x-icon" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm">
                    <p class="mt-1 text-xs text-slate-400">PNG/ICO, maksimal 256 KB.</p>
                    @error('favicon')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </div>
            </form>
            
        @elseif ($tab === 'template')
            <div class="space-y-4">
                @foreach ($templateList as $template)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-ink-900">{{ $template->nama }}</p>
                                    @if ($template->file_url)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            File tersedia
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Belum ada file
                                        </span>
                                    @endif
                                </div>

                                @if ($template->file_url)
                                    <div class="mt-2">
                                        @if ($template->isPdf())
                                            <x-file-preview
                                                :id="'template-'.$template->id"
                                                :label="$template->nama"
                                                :url="$template->file_url"
                                                :preview-url="route('admin.pengaturan.template.preview', $template->kode)"
                                                :download-url="route('admin.pengaturan.template.unduh', $template->kode)"
                                                :hide-label="true"
                                            />
                                        @else
                                            <a href="{{ route('admin.pengaturan.template.unduh', $template->kode) }}" class="text-xs font-medium text-brand-700 hover:underline">Download file saat ini</a>
                                            <span class="ml-1 text-[11px] text-slate-400">(pratinjau tidak didukung untuk format {{ $template->formatLabel() }})</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('admin.pengaturan.template.update', $template->kode) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="file" name="file" accept="{{ $template->acceptAttribute() }}" required class="rounded-lg border-slate-200 text-sm">
                                <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Upload</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-400">Konten pengaturan Lainnya akan tampil di sini.</p>
        @endif
    </div>
@endsection