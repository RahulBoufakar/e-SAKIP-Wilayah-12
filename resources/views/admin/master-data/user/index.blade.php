@extends('admin.layout.app')

@section('title', 'Master Data — User')
@section('subtitle', 'Kelola akun pengguna sistem eSAKIP')

@section('content')
    <div
        x-data="{
            modalOpen: {{ $errors->any() ? 'true' : 'false' }},
            mode: {{ $errors->any() ? '\'edit\'' : '\'create\'' }},
            form: { id: null, name: '{{ old('name', '') }}', email: '{{ old('email', '') }}', password: '', role: '{{ old('role', '') }}', tim_kerja_id: @json(collect(old('tim_kerja_id', []))->map(fn($v) => (int) $v)) },
            openCreate() { 
            this.mode = 'create'; 
                this.form = { 
                    id: null, 
                    name: '', 
                    email: '', 
                    password: '', 
                    role: '', 
                    tim_kerja_id: null 
                }; 
                this.modalOpen = true; 
            },

            openEdit(row) { 
                this.mode = 'edit'; 
                this.form = { 
                    id: row.id, 
                    name: row.name, 
                    email: row.email, 
                    password: '', 
                    role: row.roles[0]?.name ?? '', 
                    tim_kerja_id: row.tim_kerja[0]?.id ?? null 
                }; 
                this.modalOpen = true; 
            },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="w-full max-w-sm">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                           class="w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-3 text-sm shadow-card focus:border-brand-500 focus:ring-brand-500">
                </div>
            </form>
            <button @click="openCreate()" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah User
            </button>
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="w-16 px-5 py-3 font-semibold">No</th>
                        <th class="px-5 py-3 font-semibold">Nama</th>
                        <th class="px-5 py-3 font-semibold">Email</th>
                        <th class="px-5 py-3 font-semibold">Role</th>
                        <th class="px-5 py-3 font-semibold">Tim Kerja</th>
                        <th class="w-40 px-5 py-3 text-center font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $i => $row)
                        @php
                            $userRole = $row->getRoleNames()->first();
                            $roleLabel = ['admin' => 'Admin', 'tim_kerja' => 'Tim Kerja', 'validator' => 'Validator'][$userRole] ?? $userRole;
                            $roleBadge = match ($userRole) {
                                'admin' => 'bg-brand-50 text-brand-700',
                                'tim_kerja' => 'bg-amber-50 text-amber-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-5 py-3 text-slate-500">{{ $users->firstItem() + $i }}</td>
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $row->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $row->email }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full {{ $roleBadge }} px-2.5 py-0.5 text-xs font-semibold">{{ $roleLabel }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $row->hasRole('tim_kerja') && $row->timKerja->isNotEmpty() ? $row->timKerja->pluck('nama_tim')->join(', ') : '-' }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit(@js($row))" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">Edit</button>
                                    @can('delete', $row)
                                        <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                    @endcan
                                </div>
                                @include('admin.layout.confirm-delete', [
                                    'refName' => 'confirm-'.$row->id,
                                    'action' => route('admin.master-data.user.destroy', $row->id),
                                    'label' => $row->name,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">
                                Belum ada data User. Klik "Tambah User" untuk menambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="mt-4">{{ $users->links() }}</div>
        @endif

        @include('admin.master-data.user.modal-form')
    </div>
@endsection
