{{--
    Tombol export untuk satu tabel. Pemakaian:
        <x-export-buttons target="tabel-capaian" title="Capaian Kinerja TW1" />
        <table id="tabel-capaian"> ... <th data-export-ignore>Aksi</th> ...
--}}
@props(['target', 'title'])

<div x-data="tableExport(@js($target), @js($title))" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        @foreach (['copy' => 'Copy', 'csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF', 'print' => 'Print'] as $format => $label)
            <button type="button" @click="bukaKonfirmasi('{{ $format }}', '{{ $label }}')" :disabled="sibuk !== null"
                    class="border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-brand-700 disabled:cursor-wait disabled:opacity-60 {{ $loop->first ? '' : 'border-l' }}">
                <span x-text="sibuk === '{{ $format }}' ? '…' : '{{ $label }}'">{{ $label }}</span>
            </button>
        @endforeach
    </div>
    <span x-show="pesan" x-cloak x-text="pesan" class="text-xs text-slate-500"></span>

    {{-- Pop-up Warning Konfirmasi Ekspor --}}
    <dialog x-ref="dialogKonfirmasi" @click.self="tutupKonfirmasi()" @close="formatDipilih = null" class="m-auto rounded-2xl border border-slate-100 p-0 shadow-2xl backdrop:bg-ink-950/50">
        <div class="w-full max-w-sm p-6 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500 ring-8 ring-amber-50/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <p class="mt-4 text-base font-semibold text-ink-900">Konfirmasi Ekspor Data</p>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                Apakah Anda yakin ingin mengekspor data tabel ini ke format <span class="font-semibold text-ink-900" x-text="formatDipilih?.label"></span>?
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button type="button" @click="tutupKonfirmasi()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50">
                    Batal
                </button>
                <button type="button" @click="lanjutkanEkspor()" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700">
                    Ya, Ekspor
                </button>
            </div>
        </div>
    </dialog>
</div>
