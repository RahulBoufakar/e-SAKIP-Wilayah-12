@props(['id', 'text', 'short' => null])

{{-- data-export-value: teks lengkap yang dipakai <x-export-buttons>, bukan versi terpotong. --}}
<div x-data data-export-value="{{ $short !== null && $short !== $text ? $short.' - '.$text : $text }}">
    <button type="button" @click="$refs['trunc-{{ $id }}'].showModal()" class="block w-full text-left">
        <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $short ?? $text }}</span>
    </button>

    <dialog x-ref="trunc-{{ $id }}" @click.self="$el.close()" class="m-auto w-full max-w-lg rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
        <div class="p-6">
            <p class="break-words whitespace-pre-line text-sm text-ink-900">{{ $text }}</p>
            <div class="mt-5 flex items-center justify-end gap-1">
                <button type="button" x-data="copyButton(@js($text))" @click="salin()"
                        :title="tersalin ? 'Tersalin' : 'Salin teks'" :aria-label="tersalin ? 'Tersalin' : 'Salin teks'"
                        class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-700">
                    <svg x-show="! tersalin" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>
                    <svg x-show="tersalin" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </button>
                <button type="button" @click="$refs['trunc-{{ $id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
            </div>
        </div>
    </dialog>
</div>
