@props([
    'refName',
    'action',
    'method'       => 'POST',
    'title',
    'body'         => 'Pastikan data sudah benar sebelum melanjutkan.',
    'confirmLabel' => 'Ya, lanjutkan',
    'confirmClass' => 'bg-brand-600 hover:bg-brand-700',
])

<dialog x-ref="{{ $refName }}" @click.self="$el.close()" class="m-auto rounded-2xl p-0 backdrop:bg-ink-950/50">
    <div class="w-full max-w-sm p-6 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
            </svg>
        </div>
        <p class="mt-4 text-sm font-semibold text-ink-900">{{ $title }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $body }}</p>
        <div class="mt-6 flex justify-center gap-3">
            <button type="button" @click="$refs['{{ $refName }}'].close()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Batal
            </button>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white {{ $confirmClass }}">
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</dialog>

