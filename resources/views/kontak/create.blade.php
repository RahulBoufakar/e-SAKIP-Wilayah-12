<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami - {{ $pengaturanAplikasi->nama_aplikasi }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen w-full flex items-center justify-center bg-cover bg-center relative font-sans antialiased py-10"
      style="background-image: url('{{ $pengaturanAplikasi->background_kontak_url ?? asset('bg-lldikti12.jpeg') }}');">

    <div class="absolute inset-0 bg-black/30"></div>

    @if (session('status'))
        <div class="absolute top-6 z-20 rounded-lg bg-emerald-500/80 px-4 py-2 text-sm text-white shadow-lg backdrop-blur-md">
            {{ session('status') }}
        </div>
    @endif

    <div class="relative z-10 mx-4 w-full max-w-md rounded-2xl border border-blue-400/60 bg-slate-900/60 p-8 shadow-2xl backdrop-blur-md">
        <h1 class="text-center text-2xl font-extrabold uppercase tracking-wide text-white">Hubungi Kami</h1>

        <form method="POST" action="{{ route('kontak.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="nama" class="block text-sm font-medium text-white">Nama</label>
                <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required
                       class="mt-1.5 block w-full rounded-md border border-white/60 bg-slate-800/40 px-3 py-2 text-white placeholder-gray-400 focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                @error('nama')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-white">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1.5 block w-full rounded-md border border-white/60 bg-slate-800/40 px-3 py-2 text-white placeholder-gray-400 focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                @error('email')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tim_kerja_id" class="block text-sm font-medium text-white">Tim Kerja</label>
                <select id="tim_kerja_id" name="tim_kerja_id"
                        class="mt-1.5 block w-full rounded-md border border-white/60 bg-slate-800/40 px-3 py-2 text-white focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    <option value="" class="text-black">— Pilih Tim Kerja (opsional) —</option>
                    @foreach ($timKerjaList as $tim)
                        <option value="{{ $tim->id }}" class="text-black" @selected(old('tim_kerja_id') == $tim->id)>{{ $tim->nama_tim }}</option>
                    @endforeach
                </select>
                @error('tim_kerja_id')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="gambar" class="block text-sm font-medium text-white">Unggah Gambar</label>
                <input id="gambar" type="file" name="gambar" accept="image/png,image/jpeg,image/webp"
                       class="mt-1.5 block w-full rounded-md border border-white/60 bg-slate-800/40 px-3 py-2 text-sm text-white">
                <p class="mt-1 text-xs text-gray-300">Opsional. PNG/JPG/WEBP, maksimal 2 MB.</p>
                @error('gambar')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="pesan" class="block text-sm font-medium text-white">Pesan</label>
                <textarea id="pesan" name="pesan" rows="4" required
                          class="mt-1.5 block w-full rounded-md border border-white/60 bg-slate-800/40 px-3 py-2 text-white placeholder-gray-400 focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">{{ old('pesan') }}</textarea>
                @error('pesan')<p class="mt-1 text-xs text-red-300">{{ $message }}</p>@enderror
            </div>

            <div class="pt-2 text-center">
                <button type="submit" class="rounded-full bg-amber-500 px-8 py-2.5 text-sm font-bold text-black shadow-md transition-colors hover:bg-amber-400">
                    KIRIM
                </button>
            </div>
        </form>
    </div>
</body>
</html>