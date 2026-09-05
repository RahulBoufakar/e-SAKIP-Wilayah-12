<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ $pengaturanAplikasi->nama_aplikasi }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    @if ($pengaturanAplikasi->favicon_url)
        <link rel="icon" href="{{ $pengaturanAplikasi->favicon_url }}">
    @endif

    <style>
        [x-cloak] { display: none !important; }
        body { -webkit-font-smoothing: antialiased; }
    </style>
    @stack('styles')

    @stack('head')
</head>
<body class="h-full bg-slate-100 font-sans text-ink-900 antialiased">
    
    {{-- 1. Tambahkan state Alpine x-data di pembungkus utama --}}
    <div x-data="{ desktopCollapsed: false }" class="min-h-screen flex">
        
        @include('admin.layout.sidebar')

        {{-- 2. Buat margin kiri dinamis (lg:pl-72 atau lg:pl-20) dan tambahkan efek transisi --}}
        <div 
            class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out"
            :class="desktopCollapsed ? 'lg:pl-20' : 'lg:pl-72'"
        >
            @include('admin.layout.navbar')

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
        
    </div>

    @include('admin.layout.feedback-popup')

    @stack('scripts')
</body>
</html>