<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailwind + Alpine.js Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen p-6">

    <div
        x-data="{
            showToast: false,
            showModal: false,
            toastMessage: '',
            fireToast(message) {
                this.toastMessage = message;
                this.showToast = true;
                setTimeout(() => this.showToast = false, 3000);
            }
        }"
        class="max-w-md mx-auto space-y-6"
    >

        <div class="bg-white rounded-xl shadow-lg p-8 space-y-4">
            <h1 class="text-2xl font-bold text-gray-800">Demo Alpine.js</h1>
            <p class="text-gray-600 text-sm">Klik tombol di bawah untuk menguji notifikasi (toast) dan popup (modal).</p>

            <!-- Tombol trigger toast -->
            <button
                @click="fireToast('Data berhasil disimpan!')"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition"
            >
                Tampilkan Notifikasi Sukses
            </button>

            <!-- Tombol trigger modal -->
            <button
                @click="showModal = true"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition"
            >
                Buka Popup
            </button>
        </div>

        <!-- TOAST NOTIFICATION -->
        <div
            x-show="showToast"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-6 right-6 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-2"
            style="display: none;"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span x-text="toastMessage"></span>
        </div>

        <!-- MODAL POPUP -->
        <div
            x-show="showModal"
            x-transition.opacity
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-6 z-50"
            style="display: none;"
        >
            <div
                x-show="showModal"
                @click.outside="showModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full space-y-4"
            >
                <h2 class="text-lg font-bold text-gray-800">Konfirmasi</h2>
                <p class="text-gray-600 text-sm">Ini contoh popup modal. Klik di luar kotak atau tombol tutup untuk menutupnya.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        @click="showModal = false"
                        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100 transition"
                    >
                        Batal
                    </button>
                    <button
                        @click="showModal = false; fireToast('Aksi dikonfirmasi!')"
                        class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition"
                    >
                        Konfirmasi
                    </button>
                </div>
            </div>
        </div>

    </div>

</body>
</html>