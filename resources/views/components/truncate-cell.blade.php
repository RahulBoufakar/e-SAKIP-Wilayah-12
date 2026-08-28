@props(['id', 'text', 'short' => null])

<div x-data>
    <button type="button" @click="$refs['trunc-{{ $id }}'].showModal()" class="block w-full text-left">
        <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $short ?? $text }}</span>
    </button>

    <dialog x-ref="trunc-{{ $id }}" @click.self="$el.close()" class="m-auto w-full max-w-lg rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
        <div class="p-6">
            <p class="break-words whitespace-pre-line text-sm text-ink-900">{{ $text }}</p>
            <div class="mt-5 flex justify-end">
                <button type="button" @click="$refs['trunc-{{ $id }}'].close()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
            </div>
        </div>
    </dialog>
</div>