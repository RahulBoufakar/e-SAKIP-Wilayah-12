<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSAKIP LLDikti Wilayah XII</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink: { 950: '#0a2233', 900: '#0d3145' },
                        brand: { 50: '#eefbfb', 100: '#d4f3f3', 400: '#3fb5b8', 500: '#22969c', 600: '#17777e', 700: '#155f66' },
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-ink-950 font-sans text-white antialiased">
    <div class="relative isolate min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute -top-40 left-1/2 h-[560px] w-[900px] -translate-x-1/2 rounded-full bg-brand-500/20 blur-3xl"></div>

        <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-12">
            <header class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 font-mono text-lg font-bold">e</div>
                <div class="leading-tight">
                    <p class="text-sm font-bold">eSAKIP</p>
                    <p class="text-xs text-brand-100/60">LLDikti Wilayah XII</p>
                </div>
            </header>

            <main class="flex flex-1 flex-col items-center justify-center py-16 text-center">
                <p class="font-mono text-xs uppercase tracking-[0.3em] text-brand-300">Akuntabilitas Kinerja Instansi</p>
                <h1 class="mt-4 max-w-2xl text-3xl font-extrabold leading-tight sm:text-4xl">
                    Sistem eSAKIP LLDikti Wilayah XII
                </h1>
                <p class="mt-4 max-w-xl text-sm text-brand-100/70 sm:text-base">
                    Pilih tampilan yang ingin dibuka. Autentikasi belum diaktifkan pada tahap ini —
                    setiap tombol langsung menuju dashboard masing-masing peran.
                </p>

                <div class="mt-12 grid w-full gap-5 sm:grid-cols-3">
                    <a href="{{ route('admin.dashboard') }}" class="group flex flex-col items-center gap-4 rounded-2xl border border-white/10 bg-white/5 px-6 py-10 text-center transition-all hover:-translate-y-1 hover:border-brand-400/60 hover:bg-white/10">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500/20 text-brand-300 transition-colors group-hover:bg-brand-500 group-hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                        </div>
                        <div>
                            <p class="font-bold">Admin</p>
                            <p class="mt-1 text-xs text-brand-100/60">Kelola master data &amp; target kinerja</p>
                        </div>
                    </a>

                    <a href="{{ route('tim-kerja.placeholder') }}" class="group flex flex-col items-center gap-4 rounded-2xl border border-white/10 bg-white/5 px-6 py-10 text-center transition-all hover:-translate-y-1 hover:border-brand-400/60 hover:bg-white/10">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500/20 text-brand-300 transition-colors group-hover:bg-brand-500 group-hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </div>
                        <div>
                            <p class="font-bold">Tim Kerja</p>
                            <p class="mt-1 text-xs text-brand-100/60">Segera hadir</p>
                        </div>
                    </a>

                    <a href="{{ route('validator.placeholder') }}" class="group flex flex-col items-center gap-4 rounded-2xl border border-white/10 bg-white/5 px-6 py-10 text-center transition-all hover:-translate-y-1 hover:border-brand-400/60 hover:bg-white/10">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500/20 text-brand-300 transition-colors group-hover:bg-brand-500 group-hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        </div>
                        <div>
                            <p class="font-bold">Validator</p>
                            <p class="mt-1 text-xs text-brand-100/60">Segera hadir</p>
                        </div>
                    </a>
                </div>
            </main>

            <footer class="text-center text-xs text-brand-100/40">
                &copy; {{ date('Y') }} LLDikti Wilayah XII — Belum ada proses autentikasi pada versi ini.
            </footer>
        </div>
    </div>
</body>
</html>
