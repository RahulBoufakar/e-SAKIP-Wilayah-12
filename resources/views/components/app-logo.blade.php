@props(['mobile' => false])

@if ($mobile)
    <div class="flex min-w-0 flex-1 items-center justify-center bg-white px-3 py-2">
        <div class="relative flex h-20 w-40 shrink-0 items-center justify-center">
            @if ($pengaturanAplikasi->logo_url)
                <img
                    src="{{ $pengaturanAplikasi->logo_url }}"
                    alt="Logo"
                    width="160"
                    height="80"
                    class="h-full w-full object-contain"
                    loading="eager"
                    decoding="async"
                >
            @else
                <div class="truncate text-xl font-bold text-[#002e5b]">
                    {{ $pengaturanAplikasi->nama_aplikasi ?? 'LLDIKTI 12' }}
                </div>
            @endif
        </div>
    </div>
@else
    <div class="flex h-20 shrink-0 items-center justify-center bg-white px-3 transition-all duration-300 overflow-hidden">
        <div class="relative h-16 w-36 shrink-0 flex items-center justify-center">
            @if ($pengaturanAplikasi->logo_url)
                <img
                    src="{{ $pengaturanAplikasi->logo_url }}"
                    alt="Logo"
                    width="144"
                    height="64"
                    class="h-full w-full object-contain transition-transform duration-300"
                    :class="desktopCollapsed ? 'scale-75' : 'scale-100'"
                    loading="eager"
                    decoding="async"
                >
            @else
                <div
                    class="truncate text-xl font-bold text-[#002e5b] transition-all duration-300"
                    :class="desktopCollapsed ? 'text-xs' : 'text-xl'"
                >
                    <span x-show="!desktopCollapsed">{{ $pengaturanAplikasi->nama_aplikasi ?? 'LLDIKTI 12' }}</span>
                    <span x-show="desktopCollapsed" class="text-sm">L12</span>
                </div>
            @endif
        </div>
    </div>
@endif