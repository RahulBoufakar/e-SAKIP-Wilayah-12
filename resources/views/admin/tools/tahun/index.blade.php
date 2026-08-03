@extends('admin.layout.app')

@section('title', 'Setting Tahun')
@section('subtitle', 'Kelola daftar Tahun Anggaran')

@section('content')
    <div x-data="{ modalOpen: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="flex justify-end">
            <button @click="modalOpen = true" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition-colors hover:bg-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Tahun Anggaran
            </button>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-card">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-ink-900 text-white">
                        <th class="px-5 py-3 font-semibold">Tahun Anggaran</th>
                        <th class="w-32 px-5 py-3 text-center font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tahunList as $row)
                        <tr class="{{ $loop->even ? 'bg-slate-50/60' : '' }} hover:bg-brand-50/40">
                            <td class="px-5 py-3 font-mono font-semibold text-ink-900">{{ $row->tahun }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end mr-3">
                                    <button @click="$refs['confirm-{{ $row->id }}'].showModal()" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
                                </div>
                                @include('admin.layout.confirm-delete', [
                                    'refName' => 'confirm-'.$row->id,
                                    'action' => route('admin.tools.tahun.destroy', $row->id),
                                    'label' => 'Tahun Anggaran '.$row->tahun,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada Tahun Anggaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tahunList->hasPages())
            <div class="mt-4">{{ $tahunList->links() }}</div>
        @endif

        @include('admin.tools.tahun._modal')
    </div>
@endsection

@push('styles')
<style>
#yearPicker button {
    padding: 8px 4px;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
    background: transparent;
    color: #1e293b;
    text-align: center;
}

#yearPicker button:hover {
    background-color: #f1f5f9;
}

#yearPicker button.selected {
    background-color: #3b82f6;
    color: white;
}

#yearPicker button.current {
    font-weight: 600;
    color: #3b82f6;
    border: 1px solid #3b82f6;
}
</style>
@endpush

@push('scripts')
<script>
function toggleYearPicker(inputEl) {
    const picker = document.getElementById('yearPicker');
    const isHidden = picker.classList.contains('hidden');

    if (isHidden) {
        positionPicker(inputEl);
        renderYears();
        picker.classList.remove('hidden');
    } else {
        picker.classList.add('hidden');
    }
}

function positionPicker(inputEl) {
    const picker = document.getElementById('yearPicker');
    const rect = inputEl.getBoundingClientRect();

    picker.style.top = (rect.bottom + 4) + 'px';
    picker.style.left = rect.left + 'px';
    picker.style.width = rect.width + 'px';
}

function selectYear(year) {
    document.getElementById('tahun').value = year;
    document.getElementById('yearPicker').classList.add('hidden');
}

function renderYears() {
    const currentYear = new Date().getFullYear();
    const startYear = currentYear - 5;
    const endYear = currentYear + 5;
    const selectedYear = parseInt(document.getElementById('tahun').value);

    const picker = document.getElementById('yearPicker');
    const grid = picker.querySelector('.grid');
    grid.innerHTML = '';

    for (let year = startYear; year <= endYear; year++) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = year;
        button.onclick = () => selectYear(year);

        if (year === currentYear) {
            button.classList.add('current');
        }

        if (year === selectedYear) {
            button.classList.add('selected');
        }

        grid.appendChild(button);
    }
}

document.addEventListener('click', function(event) {
    const picker = document.getElementById('yearPicker');
    const input = document.getElementById('tahun');

    if (picker && !picker.contains(event.target) && event.target !== input) {
        picker.classList.add('hidden');
    }
});

// Reposisi ulang popup kalau modal/body di-scroll (misal field lain bikin modal panjang)
window.addEventListener('scroll', function() {
    const picker = document.getElementById('yearPicker');
    if (picker && !picker.classList.contains('hidden')) {
        picker.classList.add('hidden');
    }
}, true); // capture: true supaya bisa dengar scroll dari elemen manapun, termasuk container modal
</script>
@endpush