{{--
    Tombol export untuk satu tabel. Pemakaian:
        <x-export-buttons target="tabel-capaian" title="Capaian Kinerja TW1" />
        <table id="tabel-capaian"> ... <th data-export-ignore>Aksi</th> ...
--}}
@props(['target', 'title'])

<div x-data="tableExport(@js($target), @js($title))" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        @foreach (['copy' => 'Copy', 'csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF', 'print' => 'Print'] as $format => $label)
            <button type="button" @click="jalankan('{{ $format }}')" :disabled="sibuk !== null"
                    class="border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-brand-700 disabled:cursor-wait disabled:opacity-60 {{ $loop->first ? '' : 'border-l' }}">
                <span x-text="sibuk === '{{ $format }}' ? '…' : '{{ $label }}'">{{ $label }}</span>
            </button>
        @endforeach
    </div>
    <span x-show="pesan" x-cloak x-text="pesan" class="text-xs text-slate-500"></span>
</div>
