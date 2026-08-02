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
            <h3 class="text-base font-bold text-ink-900" x-text="mode === 'create' ? 'Tambah User' : 'Edit User'"></h3>
            <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form
            method="POST"
            :action="mode === 'create' ? '{{ route('admin.master-data.user.store') }}' : '{{ url('admin/master-data/user') }}/' + form.id"
            class="flex flex-1 flex-col overflow-hidden"
        >
            @csrf
            <template x-if="mode === 'edit'">
                @method('PUT')
            </template>

            <!-- Body: scrollable -->
            <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">

                <x-form.input
                    label="Nama"
                    name="name"
                    type="text"
                    maxlength="150"
                    x-model="form.name"
                    required
                />

                <x-form.input
                    label="Email"
                    name="email"
                    type="email"
                    maxlength="150"
                    x-model="form.email"
                    required
                />

                <div>
                    <label for="password" class="block text-sm font-medium text-ink-900">
                        Password
                        <span class="font-normal text-slate-400" x-show="mode === 'edit'">(kosongkan jika tidak diubah)</span>
                    </label>
                    <input type="password" name="password" id="password" x-model="form.password" :required="mode === 'create'" minlength="8"
                           class="mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('password')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <x-form.select
                    label="Role"
                    name="role"
                    x-model="form.role"
                    required
                >
                    <option value="" disabled>Pilih role</option>
                    <option value="administrator">Administrator</option>
                    <option value="tim_kerja">Tim Kerja</option>
                    <option value="validator">Validator</option>
                </x-form.select>

                {{-- FR-M5: hanya muncul untuk Role = Tim Kerja, murni x-show (toggle UI) --}}
                <div x-show="form.role === 'tim_kerja'" x-cloak>
                    <label class="block text-sm font-medium text-ink-900">Tim Kerja</label>
                    <div class="mt-1.5 max-h-40 space-y-1.5 overflow-y-auto rounded-lg border border-slate-200 p-3">
                        @forelse ($timKerjaList as $sk)
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="tim_kerja_id[]" value="{{ $sk->id }}" x-model.number="form.tim_kerja_id"
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                {{ $sk->nama_tim }}
                            </label>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data Tim Kerja.</p>
                        @endforelse
                    </div>
                    @error('tim_kerja_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
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