<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard e-SAKIP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; }
        .font-mono-num { font-family: 'JetBrains Mono', 'Courier New', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <div
        x-data="dashboard()"
        x-init="initChart()"
        class="min-h-screen"
    >

        <!-- TOAST -->
        <div
            x-show="toast.show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-end="opacity-0"
            class="fixed top-5 right-5 z-50 bg-emerald-700 text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-2 text-sm"
            style="display: none;"
        >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span x-text="toast.message"></span>
        </div>

        <!-- SIDEBAR + MAIN -->
        <div class="flex">

            <!-- SIDEBAR -->
            <aside class="hidden lg:flex flex-col w-60 shrink-0 min-h-screen bg-slate-900 text-slate-300 px-4 py-6">
                <div class="flex items-center gap-2 px-2 mb-8">
                    <div class="w-8 h-8 rounded bg-amber-500 flex items-center justify-center text-slate-900 font-bold text-sm">S</div>
                    <span class="text-white font-semibold tracking-tight">e-SAKIP</span>
                </div>

                <nav class="flex flex-col gap-1 text-sm">
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-800 text-white font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Ringkasan
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span> Target Kinerja
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span> Rencana Aksi
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span> IKU LLDIKTI
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span> Tim Kerja
                    </a>
                </nav>

                <div class="mt-auto pt-6 border-t border-slate-800 text-xs text-slate-500 px-2">
                    Triwulan Aktif: <span class="text-emerald-400 font-semibold">TW III</span>
                </div>
            </aside>

            <!-- MAIN -->
            <main class="flex-1 px-5 lg:px-8 py-6 max-w-7xl">

                <!-- HEADER -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">Ringkasan Kinerja</h1>
                        <p class="text-sm text-slate-500 mt-0.5">LLDIKTI Wilayah XII &middot; Tahun Anggaran 2026</p>
                    </div>
                    <button
                        @click="showModal = true"
                        class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Input Capaian
                    </button>
                </div>

                <!-- STAT CARDS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <template x-for="stat in stats" :key="stat.label">
                        <div class="bg-white rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500 mb-2" x-text="stat.label"></p>
                            <p class="text-2xl font-bold text-slate-900 font-mono-num" x-text="stat.value"></p>
                            <p class="text-xs mt-1 flex items-center gap-1"
                               :class="stat.trend >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                <span x-text="(stat.trend >= 0 ? '↑ ' : '↓ ') + Math.abs(stat.trend) + '%'"></span>
                                <span class="text-slate-400">vs TW lalu</span>
                            </p>
                        </div>
                    </template>
                </div>

                <!-- CHART + ACTIVITY -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

                    <!-- CHART -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-slate-800">Capaian IKU per Triwulan</h2>
                            <span class="text-xs text-slate-400">% terhadap target</span>
                        </div>
                        <canvas x-ref="chartCanvas" height="220"></canvas>
                    </div>

                    <!-- RECENT ACTIVITY -->
                    <div class="bg-white rounded-xl border border-slate-200 p-5">
                        <h2 class="text-sm font-semibold text-slate-800 mb-4">Aktivitas Terbaru</h2>
                        <ul class="space-y-4">
                            <template x-for="act in activities" :key="act.id">
                                <li class="flex gap-3 text-sm">
                                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0"
                                         :class="act.type === 'success' ? 'bg-emerald-500' : act.type === 'warning' ? 'bg-amber-500' : 'bg-slate-400'"></div>
                                    <div>
                                        <p class="text-slate-700" x-text="act.text"></p>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="act.time"></p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <template x-for="action in quickActions" :key="action.label">
                        <button
                            @click="fireToast(action.label + ' dibuka')"
                            class="bg-white border border-slate-200 rounded-xl p-4 text-left hover:border-slate-300 hover:shadow-sm transition group"
                        >
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-3 text-lg"
                                 :class="action.bg">
                                <span x-text="action.icon"></span>
                            </div>
                            <p class="text-sm font-medium text-slate-800 group-hover:text-slate-900" x-text="action.label"></p>
                            <p class="text-xs text-slate-400 mt-0.5" x-text="action.desc"></p>
                        </button>
                    </template>
                </div>

            </main>
        </div>

        <!-- MODAL: INPUT CAPAIAN -->
        <div
            x-show="showModal"
            x-transition.opacity
            class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-6 z-50"
            style="display: none;"
        >
            <div
                @click.outside="showModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full space-y-4"
            >
                <h2 class="text-base font-bold text-slate-900">Input Capaian Kinerja</h2>
                <p class="text-sm text-slate-500">Form contoh — sambungkan ke endpoint Laravel Anda sendiri.</p>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600">Pilih IKU</label>
                        <select class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            <option>IKU 1.1 — Kepuasan Pengguna Layanan</option>
                            <option>IKU 2.1 — Pembelajaran di Luar Prodi</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600">Nilai Capaian (%)</label>
                        <input type="number" placeholder="Contoh: 87" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button @click="showModal = false" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition">Batal</button>
                    <button
                        @click="showModal = false; fireToast('Capaian berhasil disimpan')"
                        class="px-4 py-2 rounded-lg text-sm bg-slate-900 hover:bg-slate-800 text-white transition"
                    >
                        Simpan
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function dashboard() {
            return {
                showModal: false,
                toast: { show: false, message: '' },

                stats: [
                    { label: 'IKU Tercapai', value: '9/11', trend: 4 },
                    { label: 'Rata-rata Capaian', value: '84%', trend: 6 },
                    { label: 'Rencana Aksi Selesai', value: '27/32', trend: -2 },
                    { label: 'Tim Kerja Aktif', value: '6', trend: 0 },
                ],

                activities: [
                    { id: 1, type: 'success', text: 'Tim Akademik menginput capaian IKU 2.1 (91%)', time: '12 menit lalu' },
                    { id: 2, type: 'warning', text: 'Rencana Aksi TW III Tim Kelembagaan belum lengkap', time: '1 jam lalu' },
                    { id: 3, type: 'default', text: 'Admin menutup pengisian Triwulan II', time: '3 jam lalu' },
                    { id: 4, type: 'success', text: 'Tim Kerjasama menyelesaikan Rencana Aksi Q3', time: 'Kemarin' },
                ],

                quickActions: [
                    { label: 'Tambah Target', desc: 'Sasaran kinerja baru', icon: '＋', bg: 'bg-slate-900 text-white' },
                    { label: 'Setting Triwulan', desc: 'Buka/tutup periode', icon: '⏱', bg: 'bg-amber-100 text-amber-700' },
                    { label: 'Kelola Tim Kerja', desc: 'Assign IKU ke tim', icon: '☰', bg: 'bg-blue-100 text-blue-700' },
                    { label: 'Ekspor Laporan', desc: 'Unduh rekap PDF', icon: '↓', bg: 'bg-emerald-100 text-emerald-700' },
                ],

                fireToast(message) {
                    this.toast.message = message;
                    this.toast.show = true;
                    setTimeout(() => this.toast.show = false, 3000);
                },

                initChart() {
                    new Chart(this.$refs.chartCanvas, {
                        type: 'line',
                        data: {
                            labels: ['TW I', 'TW II', 'TW III', 'TW IV'],
                            datasets: [
                                {
                                    label: 'Target',
                                    data: [70, 75, 80, 85],
                                    borderColor: '#cbd5e1',
                                    backgroundColor: 'transparent',
                                    borderWidth: 2,
                                    borderDash: [5, 5],
                                    tension: 0.3,
                                    pointRadius: 3,
                                    pointBackgroundColor: '#cbd5e1',
                                },
                                {
                                    label: 'Capaian',
                                    data: [62, 71, 84, 79],
                                    borderColor: '#0f172a',
                                    backgroundColor: 'rgba(15, 23, 42, 0.06)',
                                    borderWidth: 2,
                                    tension: 0.35,
                                    fill: true,
                                    pointBackgroundColor: '#f59e0b',
                                    pointRadius: 4,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    align: 'end',
                                    labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 11 } }
                                }
                            },
                            scales: {
                                y: { min: 0, max: 100, grid: { color: '#f1f5f9' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
            }
        }
    </script>

</body>
</html>