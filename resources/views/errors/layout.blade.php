<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen w-full flex items-center justify-center bg-slate-100 bg-cover bg-center relative font-sans antialiased"
      style="background-image: linear-gradient(rgba(241,245,249,0.92), rgba(241,245,249,0.92)), url('{{ $pengaturanAplikasi->background_kontak_url ?? asset('bg-lldikti12.jpeg') }}');">

    <div class="relative z-10 mx-4 max-w-2xl text-center">
        <p class="font-serif text-8xl font-bold text-ink-900 sm:text-9xl">@yield('code')</p>
        <p class="mt-4 text-xl text-slate-500 sm:text-2xl">@yield('title')</p>

        <p class="mt-8 text-lg font-bold text-ink-900">Silakan kembali ke beranda</p>
        <p class="mt-1 text-slate-600">@yield('description', 'atau hubungi administrator untuk bantuan lebih lanjut.')</p>

        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="{{ url('/') }}" class="rounded-lg bg-amber-500 px-6 py-3 text-sm font-bold text-white shadow-md transition-colors hover:bg-amber-400">
                &lt;&lt; KEMBALI
            </a>
            <a href="{{ route('kontak.create') }}" class="rounded-lg bg-amber-500 px-6 py-3 text-sm font-bold text-white shadow-md transition-colors hover:bg-amber-400">
                HUBUNGI KAMI
            </a>
        </div>
    </div>
</body>
</html>