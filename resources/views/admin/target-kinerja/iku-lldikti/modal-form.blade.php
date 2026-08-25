<div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>

        <div x-show="modalOpen" x-transition class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-bold text-ink-900">Isi Target Capaian Kinerja</h3>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.capaian-kinerja.target.update') }}" class="px-6 py-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="iku_id" :value="form.iku_id">
                <input type="hidden" name="triwulan_id" :value="form.triwulan_id">
                <input type="hidden" name="tahun_anggaran_id" value="{{ $tahunAnggaranId }}">

                <p class="mb-3 text-sm text-slate-600" x-text="form.label"></p>

                <div class="space-y-3">
                    <x-form.input label="Target" name="target" type="number" step="0.01" min="0" x-model="form.target" />

                    <div>
                        <label class="block text-sm font-medium text-ink-900">Realisasi (dari Tim Kerja)</label>
                        <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600" x-text="form.realisasi ?? '—'"></p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>