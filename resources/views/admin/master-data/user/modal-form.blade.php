<div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
    <div x-show="modalOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/50"></div>

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
            <input type="hidden" name="id" :value="form.id">

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

                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-sm font-medium text-ink-900">
                        Password
                        <span class="font-normal text-slate-400" x-show="mode === 'edit'">(kosongkan jika tidak diubah)</span>
                    </label>
                    <div class="relative mt-1.5">
                        <input :type="showPassword ? 'text' : 'password'" name="password" id="password" x-model="form.password" :required="mode === 'create'" minlength="8"
                               class="w-full rounded-lg border-slate-200 px-3 py-2 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-700 transition"
                                tabindex="-1">
                            <!-- Eye Open -->
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye Closed -->
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    @error('password')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                
                <!-- 1. Dropdown hanya tampil jika BUKAN mode edit untuk user bertipe Admin -->
                <div x-show="!(mode === 'edit' && form.role === 'admin')">
                    <x-form.select
                        label="Role"
                        name="role"
                        x-model="form.role"
                        required
                    >
                        <option value="" disabled>Pilih role</option>
                        <option value="tim_kerja">Tim Kerja</option>
                        <option value="validator">Validator</option>
                    </x-form.select>
                </div>

                <!-- 2. Saat edit user Admin, sembunyikan dropdown & kirim nilainya via input hidden -->
                <template x-if="mode === 'edit' && form.role === 'admin'">
                    <div>
                        <input type="hidden" name="role" x-model="form.role">
                        
                        <!-- Tampilan membaca saja (Read-only status) -->
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <span class="inline-flex items-center rounded-md bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 mt-1">
                                Admin (Role ini tidak dapat diubah)
                            </span>
                        </div>
                    </div>
                </template>

                {{-- FR-M5: hanya muncul untuk Role = Tim Kerja, murni x-show (toggle UI) --}}
                <div x-show="form.role === 'tim_kerja'" x-cloak>
                    <label for="tim_kerja_id" class="block text-sm font-medium text-ink-900">Tim Kerja</label>
                    <select name="tim_kerja_id" 
                    id="tim_kerja_id" 
                    x-model.number="form.tim_kerja_id"
                    class="mt-1.5 block w-full rounded-lg border border-slate-200 p-2.5 text-sm text-slate-600 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">-- Pilih Tim Kerja --</option>
                        @foreach ($timKerjaList as $sk)
                        <option value="{{ $sk->id }}">{{ $sk->nama_tim }}</option>
                        @endforeach
                    </select>
                    
                    @if ($timKerjaList->isEmpty())
                        <p class="mt-1.5 text-xs text-slate-400">Belum ada data Tim Kerja.</p>
                    @endif

                    @error('tim_kerja_id')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
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