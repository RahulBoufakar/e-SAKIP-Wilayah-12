<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailwind Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 space-y-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Tailwind CSS Aktif ✅
        </h1>

        <p class="text-gray-600">
            Kalau kotak ini punya latar putih, bayangan (shadow), sudut membulat,
            dan teks berwarna — berarti Tailwind sudah berjalan dengan baik.
        </p>

        <!-- Test warna -->
        <div class="flex gap-2">
            <div class="w-10 h-10 rounded bg-red-500"></div>
            <div class="w-10 h-10 rounded bg-yellow-500"></div>
            <div class="w-10 h-10 rounded bg-green-500"></div>
            <div class="w-10 h-10 rounded bg-blue-500"></div>
            <div class="w-10 h-10 rounded bg-purple-500"></div>
        </div>

        <!-- Test button + hover -->
        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
            Hover Saya
        </button>

        <!-- Test responsive -->
        <p class="text-sm md:text-base lg:text-lg text-gray-500">
            Ukuran teks ini berubah sesuai lebar layar (resize browser untuk cek).
        </p>

    </div>

</body>
</html>