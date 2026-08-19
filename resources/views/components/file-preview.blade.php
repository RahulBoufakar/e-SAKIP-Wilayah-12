@props(['id', 'label', 'url', 'previewUrl' => null, 'downloadUrl' => null])

<div
    x-data="{
        loading: false,
        error: null,
        async openPreview() {
            this.error = null;
            this.loading = true;
            try {
                const res = await fetch('{{ $previewUrl }}', { headers: { 'Accept': 'application/json' } });
                if (! res.ok) throw new Error('Gagal memuat pratinjau.');
                const data = await res.json();
                const bytes = Uint8Array.from(atob(data.base64), c => c.charCodeAt(0));
                const blob = new Blob([bytes], { type: data.mime });
                this.$refs['iframe-{{ $id }}'].src = URL.createObjectURL(blob);
                this.$refs['preview-{{ $id }}'].showModal();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <label class="block text-sm font-medium text-ink-900">{{ $label }}</label>
    @if ($url)
        <div class="mt-1 flex items-center gap-3">
            <a href="{{ $downloadUrl }}" class="truncate text-xs font-medium text-brand-700 hover:underline">Unduh</a>
            <button type="button" @click="openPreview()" :disabled="loading" class="rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-100 disabled:opacity-50">
                <span x-show="!loading">Pratinjau</span>
                <span x-show="loading">Memuat...</span>
            </button>
        </div>
        <p x-show="error" x-text="error" x-cloak class="mt-1 text-xs font-medium text-rose-600"></p>

        <dialog x-ref="preview-{{ $id }}" @click.self="$el.close()" class="m-auto h-[85vh] w-full max-w-3xl rounded-2xl border border-slate-200 p-0 backdrop:bg-ink-950/50">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <p class="text-sm font-semibold text-ink-900">{{ $label }}</p>
                    <button type="button" @click="$refs['preview-{{ $id }}'].close()" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <iframe x-ref="iframe-{{ $id }}" class="w-full flex-1"></iframe>
            </div>
        </dialog>
    @else
        <p class="mt-1 text-xs text-slate-400">Belum ada file.</p>
    @endif
</div>