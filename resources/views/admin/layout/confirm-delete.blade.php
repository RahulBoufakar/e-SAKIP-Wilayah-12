{{--
    Partial konfirmasi hapus (Desain Sistem §4.3): murni Alpine/native <dialog> untuk
    tampil-sembunyi, tombol "Ya, hapus" adalah submit button form DELETE biasa.
    Dipakai lewat: @include('admin.layout.confirm-delete', ['refName' => ..., 'action' => ..., 'label' => ...])
--}}
<dialog x-ref="{{ $refName }}" @click.self="$el.close()" class="m-auto rounded-2xl p-0 backdrop:bg-ink-950/50">
    <div class="w-full max-w-sm p-6 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        </div>
        <p class="mt-4 text-sm font-semibold text-ink-900">Hapus {{ $label }}?</p>
        <p class="mt-1 text-sm text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="mt-6 flex justify-center gap-3">
            <button type="button" @click="$refs['{{ $refName }}'].close()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
            <form method="POST" action="{{ $action }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Ya, hapus</button>
            </form>
        </div>
    </div>
</dialog>
