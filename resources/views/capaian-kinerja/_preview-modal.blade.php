{{-- Panel preview: Hasil Realisasi vs Target PK / Target Triwulan, tampil SEBELUM konfirmasi --}}
<div x-data="{
    open: false, loading: false, error: null, data: null, override: '',
    async buka() {
        this.open = true; this.loading = true; this.error = null; this.data = null; this.override = '';
        try {
            const res = await fetch(@js($previewUrl), { headers: { Accept: 'application/json' } });
            if (! res.ok) throw new Error();
            this.data = await res.json();
        } catch (e) { this.error = 'Gagal memuat pratinjau realisasi.'; }
        finally { this.loading = false; }
    },
    fmt(v) { return (v === null || v === undefined) ? '—' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 2 }); },
    get melebihi() { return this.data && this.override !== '' && Number(this.override) > this.data.target_pk; },
}">
    <button type="button" @click="buka()" {{ ($disabled ?? false) ? 'disabled' : '' }}
            class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition-colors {{ ($disabled ?? false) ? 'cursor-not-allowed bg-slate-200 text-slate-400' : ($tone ?? 'bg-brand-600 hover:bg-brand-700') }}">
        {{ $label }}
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-ink-950/50" @click="open = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-bold text-ink-900">Pratinjau Realisasi</h3>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ $actionUrl }}" class="px-6 py-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="triwulan_id" value="{{ $triwulanId }}">

                <p x-show="loading" class="py-6 text-center text-sm text-slate-400">Menghitung…</p>
                <p x-show="error" x-text="error" class="py-6 text-center text-sm text-rose-600"></p>

                <template x-if="data">
                    <div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-brand-50 p-3 text-center">
                                <p class="text-[11px] font-semibold uppercase text-brand-700">Hasil Realisasi</p>
                                <p class="mt-1 font-mono text-xl font-bold text-brand-700" x-text="fmt(data.realisasi)"></p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 text-center">
                                <p class="text-[11px] font-semibold uppercase text-slate-500">Target PK</p>
                                <p class="mt-1 font-mono text-xl font-bold text-ink-900" x-text="fmt(data.target_pk)"></p>
                            </div>
                        </div>
                        <p class="mt-2 text-center text-xs text-slate-500" x-show="data.target_triwulan !== null">
                            Target Triwulan ini: <span class="font-semibold" x-text="fmt(data.target_triwulan)"></span>
                            <span x-text="data.satuan"></span>
                        </p>
                        <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800" x-show="data.dibatasi">
                            Hasil perhitungan <strong x-text="fmt(data.realisasi_mentah)"></strong> melebihi Target PK,
                            sehingga disimpan maksimal <strong x-text="fmt(data.target_pk)"></strong>.
                        </p>

                        @if ($izinOverride ?? false)
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-ink-900">Override realisasi (opsional, maks. Target PK)</label>
                                <input type="number" step="0.01" min="0" :max="data.target_pk" name="realisasi_override_value" x-model="override"
                                       class="mt-1 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <p class="mt-1 text-xs text-rose-600" x-show="melebihi">Tidak boleh melebihi Target PK.</p>
                            </div>
                        @endif
                    </div>
                </template>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" :disabled="loading || ! data || melebihi"
                            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ $submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>