<div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
    <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="modalOpen = false"></div>

    <div
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-bold text-ink-900" x-text="mode === 'create' ? 'Tambah IKU' : 'Edit IKU'"></h3>
            <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form
            method="POST"
            :action="mode === 'create' ? '{{ route('admin.iku.store') }}' : '{{ url('admin/iku') }}/' + form.id"
            class="flex flex-1 flex-col overflow-hidden"
        >
            @csrf
            <template x-if="mode === 'edit'">
                @method('PUT')
            </template>

            <input type="hidden" name="jenis" value="IKU">
            <input type="hidden" name="sasaran_kegiatan_id" value="{{ $sasaran->id }}">

            <!-- Body: scrollable -->
            <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">
                <x-form.select
                    label="Jenis"
                    name="jenis"
                    x-model="form.jenis"
                    required
                >
                    <option value="IKU">IKU</option>
                    <option value="IKK">IKK</option>
                </x-form.select>
                
                <x-form.textarea
                    label="Deskripsi IKU"
                    name="deskripsi"
                    :rows="3"
                    x-model="form.deskripsi"
                    required
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.input
                        label="Target"
                        name="target_pk"
                        type="number"
                        step="0.01"
                        min="0"
                        x-model="form.target_pk"
                        required
                    />

                    <x-form.input
                        label="Satuan"
                        name="satuan"
                        type="text"
                        maxlength="20"
                        placeholder="%"
                        default-value="%"
                        x-model="form.satuan"
                        required
                    />

                    <div class="md:col-span-2">
                        <label for="tim_kerja_id" class="mb-1 block text-sm font-medium text-ink-900">
                            Tim Kerja <span class="font-normal text-slate-400">(opsional)</span>
                        </label>
                        <select name="tim_kerja_id" id="tim_kerja_id" x-model="form.tim_kerja_id"
                                class="w-full rounded-lg border-slate-200 bg-white text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
                            <option value="">— Tidak ada —</option>
                            @foreach ($timKerjaOptions as $tim)
                                <option value="{{ $tim->id }}">{{ $tim->nama_tim }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-form.textarea
                        label="Deskripsi Target"
                        name="deskripsi_target"
                        :rows="4"
                        placeholder="Masukkan deskripsi target"
                        x-model="form.deskripsi_target"
                        class="md:col-span-2"
                    />
                </div>

            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button type="button" @click="modalOpen = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
            </div>
        </form>
    </div>
</div>