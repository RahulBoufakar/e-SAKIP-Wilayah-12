<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LLDIKTI Wilayah 12</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen w-full flex items-center justify-center bg-cover bg-[center_bottom_20%] bg-no-repeat relative font-sans antialiased"
      style="background-image: url('{{ asset('bg-lldikti12.jpeg') }}');">

    <!-- Overlay Gelap Tipis agar Teks & Form Lebih Kontras -->
    <div class="absolute inset-0 bg-black/30"></div>

    <!-- Session Status / Pesan Sukses -->
    @if (session('status'))
        <div class="absolute top-6 z-20 px-4 py-2 bg-green-500/80 backdrop-blur-md text-white text-sm rounded-lg shadow-lg">
            {{ session('status') }}
        </div>
    @endif

    <!-- Card Login (Glassmorphism Effect) -->
    <div class="relative z-10 w-full max-w-md mx-4 p-8 rounded-2xl bg-slate-900/60 backdrop-blur-md border border-yellow-500/80 shadow-2xl"
         x-data="{ isLoading: false }">

        <!-- Logo LLDIKTI -->
        <div class="flex justify-center mb-1">
            <img src="{{ $pengaturanAplikasi->logo_url }}" alt="Logo LLDIKTI 12"
                class="object-contain"
                style="width: 220px; height: 120px;">
        </div>

        <form method="POST" action="{{ route('login') }}" @submit="isLoading = true">
            @csrf

            <!-- Email Address -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-white mb-1">
                    Email
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="Masukkan email Anda"
                       class="block w-full px-3 py-2 bg-slate-800/40 border border-white/60 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition">
                @error('email')
                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4" x-data="{ showPassword: false }">
                <label for="password" class="block text-sm font-medium text-white mb-1">
                    Password
                </label>
                <div class="relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                           placeholder="Masukkan password Anda"
                           class="block w-full px-3 py-2 pr-10 bg-slate-800/40 border border-white/60 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-white transition"
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
                @error('password')
                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tahun Anggaran (Sejajar Kiri - Kanan) -->
            <div class="mb-4 flex items-center justify-between gap-3">
                <label for="tahun_anggaran_id" class="text-sm font-medium text-white whitespace-nowrap">
                    Tahun Anggaran
                </label>
                <select id="tahun_anggaran_id" name="tahun_anggaran_id"
                        class="block w-full px-3 py-1.5 bg-slate-800/40 border border-white/60 rounded-md text-white text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition">
                    <option value="" class="text-black bg-white">— Gunakan tahun anggaran terbaru —</option>
                    @foreach ($tahunList as $t)
                        <option value="{{ $t->id }}" @selected(old('tahun_anggaran_id') == $t->id) class="text-black bg-white">
                            TA {{ $t->tahun }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('tahun_anggaran_id')
                <p class="mb-4 text-xs text-red-300">{{ $message }}</p>
            @enderror

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mb-6 text-sm">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="rounded border-gray-300 text-yellow-500 shadow-sm focus:ring-yellow-400 bg-transparent">
                    <span class="ms-2 text-gray-200">Remember me</span>
                </label>

                {{-- @if (Route::has('password.request'))
                    <a class="text-gray-300 hover:text-white underline transition" href="{{ route('password.request') }}">
                        Forget your password?
                    </a>
                @endif --}}
            </div>

            <!-- Submit Button (Kuning/Orange khas gambar) -->
            <div>
                <button type="submit"
                        class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-black font-semibold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-yellow-400 transition-all flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed"
                        x-bind:disabled="isLoading">
                    <span x-show="!isLoading">Log In</span>
                    <span x-show="isLoading" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </div>
        </form>
    </div>

</body>
</html>