<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — eSAKIP LLDikti Wilayah XII</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { -webkit-font-smoothing: antialiased; }
    </style>
    @stack('styles')
    @stack('head')
</head>
<body class="h-full bg-slate-100 font-sans text-ink-900 antialiased">
    <div class="min-h-screen flex">
        @include('validator.layout.sidebar')

        <div class="flex-1 flex flex-col min-w-0 lg:pl-72">
            @include('validator.layout.navbar')

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