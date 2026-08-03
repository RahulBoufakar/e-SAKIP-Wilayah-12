@php
    $feedback = session('feedback');
@endphp

<div
    x-data="{ show: @js($feedback !== null), type: @js($feedback['type'] ?? 'success') }"
    x-init="if (show) setTimeout(() => show = false, 2500)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center px-4"
>
    <div x-show="show" x-transition.opacity class="absolute inset-0 bg-ink-950/40" @click="show = false"></div>

    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="relative flex w-full max-w-xs flex-col items-center gap-3 rounded-2xl bg-white p-7 text-center shadow-xl"
    >
        <template x-if="type === 'success'">
            <svg class="h-16 w-16 text-emerald-500" viewBox="0 0 52 52" fill="none">
                <circle cx="26" cy="26" r="24" stroke="currentColor" stroke-width="3" class="opacity-20" />
                <circle cx="26" cy="26" r="24" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                        stroke-dasharray="151" stroke-dashoffset="151" pathLength="151"
                        style="animation: fb-draw .5s ease-out forwards;" />
                <path d="M15 27l7 7 15-15" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"
                      stroke-dasharray="30" stroke-dashoffset="30" pathLength="30"
                      style="animation: fb-draw .35s .35s ease-out forwards;" />
            </svg>
        </template>
        <template x-if="type === 'error'">
            <svg class="h-16 w-16 text-rose-500" viewBox="0 0 52 52" fill="none">
                <circle cx="26" cy="26" r="24" stroke="currentColor" stroke-width="3" class="opacity-20" />
                <circle cx="26" cy="26" r="24" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                        stroke-dasharray="151" stroke-dashoffset="151" pathLength="151"
                        style="animation: fb-draw .5s ease-out forwards;" />
                <path d="M18 18l16 16M34 18L18 34" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"
                      stroke-dasharray="23" stroke-dashoffset="23" pathLength="23"
                      style="animation: fb-draw .3s .35s ease-out forwards;" />
            </svg>
        </template>

        <p class="text-sm font-semibold text-ink-900" x-text="type === 'success' ? 'Berhasil' : 'Gagal'"></p>
        <p class="text-sm text-slate-500">{{ $feedback['message'] ?? '' }}</p>
    </div>
</div>

<style>
    @keyframes fb-draw {
        to { stroke-dashoffset: 0; }
    }
</style>
