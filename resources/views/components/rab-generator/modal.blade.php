{{--
    RAB Generator — modal pilihan, pratinjau, dan unduhan template Excel (04-alur-pengguna.md §B).

    Dua bagian:
    1. Pemicu: TAUTAN (bukan <button>) bergaya sama dengan tautan template lain. Blok "Unduh Template" berada
       di dalam <fieldset disabled> saat usulan terkunci; <button> di sana ikut nonaktif, tautan tidak.
    2. Modal: di-push ke stack 'scripts' (akhir <body>, di luar form/fieldset) dan berkomunikasi lewat event
       window "rab-open". Hanya dirender sekali per halaman.
--}}
@php
    $konfigRab = [
        'pohonUrl' => route('tim-kerja.rab-generator.pohon'),
        'previewUrl' => route('tim-kerja.rab-generator.preview'),
        'unduhUrl' => route('tim-kerja.rab-generator.unduh', ['token' => '__TOKEN__']),
    ];
@endphp

<a href="#" role="button" @click.prevent="$dispatch('rab-open')"
   class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" /></svg>
    Unduh Template Excel
</a>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('rabGenerator', (cfg) => ({
                    open: false,
                    memuat: false,
                    galatPohon: null,
                    fileId: null,
                    sheets: [],
                    pilih: {},          // sheet.id -> { on, header, footer, kelompok: { kelompok.id: bool } }
                    mempratinjau: false,
                    mengunduh: false,
                    galat: null,
                    previews: [],
                    tab: 0,
                    token: null,        // ada hanya selama pratinjau sesuai dengan pilihan saat ini
                    basi: false,        // pilihan berubah sesudah pratinjau dibuat

                    async buka() {
                        this.open = true;
                        if (this.sheets.length === 0 && ! this.memuat) {
                            await this.muatPohon();
                        }
                    },

                    tutup() {
                        this.open = false;
                    },

                    async muatPohon() {
                        this.memuat = true;
                        this.galatPohon = null;
                        try {
                            const res = await fetch(cfg.pohonUrl, { headers: { Accept: 'application/json' } });
                            const data = await res.json().catch(() => ({}));
                            if (! res.ok) {
                                throw new Error(data.message || 'Struktur template gagal dimuat.');
                            }

                            const pilih = {};
                            data.sheets.forEach((s) => {
                                // Header/footer default tercentang (Q1), kelompok tidak ada yang tercentang.
                                pilih[s.id] = { on: false, header: s.ada_header, footer: s.ada_footer, kelompok: {} };
                            });
                            this.pilih = pilih;
                            this.fileId = data.file_excel_id;
                            this.sheets = data.sheets;
                        } catch (e) {
                            this.galatPohon = this.pesanGalat(e);
                        } finally {
                            this.memuat = false;
                        }
                    },

                    // --- Aturan pilihan ---
                    terpilih() {
                        return this.sheets.filter((s) => this.pilih[s.id] && this.pilih[s.id].on);
                    },

                    idKelompok(s) {
                        return Object.entries(this.pilih[s.id].kelompok)
                            .filter(([, v]) => v)
                            .map(([id]) => Number(id));
                    },

                    // Sheet terpilih wajib punya minimal satu kelompok terpilih, kalau tidak Pratinjau nonaktif.
                    get bisaPratinjau() {
                        const t = this.terpilih();
                        return t.length > 0 && t.every((s) => this.idKelompok(s).length > 0);
                    },

                    semuaKelompok(s, k) {
                        return k.kelompok.length > 0 && k.kelompok.every((g) => this.pilih[s.id].kelompok[g.id]);
                    },

                    sebagianKelompok(s, k) {
                        const n = k.kelompok.filter((g) => this.pilih[s.id].kelompok[g.id]).length;
                        return n > 0 && n < k.kelompok.length;
                    },

                    // Mencentang kategori mencentang semua kelompoknya; bila sudah semua, mengosongkannya.
                    toggleKategori(s, k) {
                        const nilai = ! this.semuaKelompok(s, k);
                        k.kelompok.forEach((g) => { this.pilih[s.id].kelompok[g.id] = nilai; });
                        this.berubah();
                    },

                    // Mengubah pilihan menonaktifkan Unduh sampai Pratinjau diulang.
                    berubah() {
                        this.token = null;
                        this.basi = this.previews.length > 0;
                        this.galat = null;
                    },

                    // --- Permintaan ke server ---
                    headerJson() {
                        const csrf = document.querySelector('meta[name="csrf-token"]');
                        return {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf ? csrf.content : '',
                        };
                    },

                    async pratinjau() {
                        if (! this.bisaPratinjau || this.mempratinjau) {
                            return;
                        }
                        this.mempratinjau = true;
                        this.galat = null;
                        try {
                            const res = await fetch(cfg.previewUrl, {
                                method: 'POST',
                                headers: this.headerJson(),
                                body: JSON.stringify({
                                    file_excel_id: this.fileId,
                                    sheets: this.terpilih().map((s) => ({
                                        sheet_id: s.id,
                                        header: this.pilih[s.id].header,
                                        footer: this.pilih[s.id].footer,
                                        kelompok_ids: this.idKelompok(s),
                                    })),
                                }),
                            });
                            const data = await res.json().catch(() => ({}));
                            if (! res.ok) {
                                throw new Error(this.pesanServer(res.status, data));
                            }

                            this.previews = data.previews;
                            this.token = data.preview_token;
                            this.tab = 0;
                            this.basi = false;
                        } catch (e) {
                            this.galat = this.pesanGalat(e);
                        } finally {
                            this.mempratinjau = false;
                        }
                    },

                    async unduh() {
                        if (! this.token || this.mengunduh) {
                            return;
                        }
                        this.mengunduh = true;
                        this.galat = null;
                        try {
                            const res = await fetch(cfg.unduhUrl.replace('__TOKEN__', this.token), { headers: { Accept: 'application/json' } });
                            if (! res.ok) {
                                const data = await res.json().catch(() => ({}));
                                if (res.status === 404 || res.status === 403) {
                                    this.token = null; // kedaluwarsa: pratinjau harus diminta ulang
                                }
                                throw new Error(data.message || 'Berkas gagal diunduh. Buat pratinjau ulang.');
                            }

                            const cocok = /filename="?([^";]+)"?/.exec(res.headers.get('Content-Disposition') || '');
                            const url = URL.createObjectURL(await res.blob());
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = cocok ? cocok[1] : 'RAB.xlsx';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            setTimeout(() => URL.revokeObjectURL(url), 1000);
                        } catch (e) {
                            this.galat = this.pesanGalat(e);
                        } finally {
                            this.mengunduh = false;
                        }
                    },

                    pesanServer(status, data) {
                        if (status === 429) return 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.';
                        if (status === 419) return 'Sesi berakhir. Muat ulang halaman lalu coba lagi.';
                        if (data.errors) return Object.values(data.errors).flat()[0];
                        return data.message || 'Pratinjau gagal dibuat.';
                    },

                    pesanGalat(e) {
                        return e instanceof TypeError ? 'Tidak dapat terhubung ke server. Periksa koneksi Anda.' : e.message;
                    },
                }));
            });
        </script>

        <div x-data="rabGenerator(@js($konfigRab))" @rab-open.window="buka()" @keydown.escape.window="tutup()">
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
                <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink-950/50" @click="tutup()"></div>

                <div x-show="open" x-transition class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div>
                            <h3 class="text-base font-bold text-ink-900">Unduh Template Excel</h3>
                            <p class="text-xs text-slate-400">Pilih sheet, komponen, dan kelompok yang dibutuhkan, lihat pratinjau, lalu unduh.</p>
                        </div>
                        <button type="button" @click="tutup()" class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Tutup">&times;</button>
                    </div>

                    <div class="grid min-h-0 flex-1 grid-cols-1 overflow-hidden lg:grid-cols-2">
                        {{-- Pohon pilihan --}}
                        <div class="min-h-0 space-y-3 overflow-y-auto border-b border-slate-100 p-5 lg:border-b-0 lg:border-r">
                            <p x-show="memuat" class="py-8 text-center text-sm text-slate-400">Memuat struktur template…</p>
                            <p x-show="galatPohon" x-text="galatPohon" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"></p>

                            <template x-for="s in sheets" :key="s.id">
                                <div class="rounded-xl border border-slate-200">
                                    <label class="flex cursor-pointer items-center gap-2 px-3 py-2.5">
                                        <input type="checkbox" x-model="pilih[s.id].on" @change="berubah()" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        <span class="font-mono text-xs font-semibold text-brand-700" x-text="s.kode_sheet"></span>
                                        <span class="text-sm font-medium text-ink-900" x-text="s.nama_sheet"></span>
                                    </label>

                                    <div x-show="pilih[s.id].on" x-cloak class="space-y-2 border-t border-slate-100 px-3 py-3">
                                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600">
                                            <label class="inline-flex items-center gap-1.5" x-show="s.ada_header">
                                                <input type="checkbox" x-model="pilih[s.id].header" @change="berubah()" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Header
                                            </label>
                                            <label class="inline-flex items-center gap-1.5" x-show="s.ada_footer">
                                                <input type="checkbox" x-model="pilih[s.id].footer" @change="berubah()" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Footer
                                            </label>
                                        </div>

                                        <template x-for="k in s.kategori" :key="k.id">
                                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                                <label class="flex cursor-pointer items-center gap-2">
                                                    <input type="checkbox" :checked="semuaKelompok(s, k)" :disabled="k.kelompok.length === 0"
                                                           x-effect="$el.indeterminate = sebagianKelompok(s, k)" @change="toggleKategori(s, k)"
                                                           class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                    <span class="font-mono text-xs font-semibold text-slate-600" x-text="k.kode"></span>
                                                    <span class="text-sm font-medium text-ink-900" x-text="k.nama"></span>
                                                </label>
                                                <div class="mt-1.5 space-y-1 pl-6">
                                                    <template x-for="g in k.kelompok" :key="g.id">
                                                        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                                                            <input type="checkbox" x-model="pilih[s.id].kelompok[g.id]" @change="berubah()" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                            <span class="font-mono text-xs text-slate-500" x-text="g.kode"></span>
                                                            <span x-text="g.label"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <p x-show="idKelompok(s).length === 0" class="text-xs font-medium text-rose-600">Pilih minimal satu kelompok untuk sheet ini.</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Pratinjau --}}
                        <div class="flex min-h-[18rem] min-w-0 flex-col p-5">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Pratinjau perkiraan</p>

                            <p x-show="galat" x-text="galat" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"></p>
                            <p x-show="basi" class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-medium text-amber-800">
                                Pilihan diubah. Klik Pratinjau lagi sebelum mengunduh.
                            </p>

                            <p x-show="! mempratinjau && previews.length === 0" class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-slate-300 px-4 text-center text-sm text-slate-400">
                                Pilih minimal satu sheet beserta kelompoknya, lalu klik Pratinjau.
                            </p>
                            <p x-show="mempratinjau" class="flex flex-1 items-center justify-center text-sm text-slate-400">Membuat pratinjau…</p>

                            {{--
                                x-if (bukan x-show): iframe harus DIBUAT dan dimuat saat sudah tampil. Iframe yang srcdoc-nya dimuat
                                di dalam wadah display:none bisa tidak tergambar saat wadah dimunculkan (terlihat sebagai pratinjau
                                yang baru muncul pada klik kedua). `! mempratinjau` dibaca lebih dulu agar langganan reaktif
                                tidak bergantung pada hubungan pendek (&&).
                            --}}
                            <template x-if="! mempratinjau && previews.length > 0">
                            <div class="flex min-h-0 flex-1 flex-col" :class="basi ? 'opacity-50' : ''">
                                <div class="mb-2 flex flex-wrap gap-1">
                                    <template x-for="(p, i) in previews" :key="i">
                                        <button type="button" @click="tab = i" x-text="p.judul"
                                                :class="tab === i ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                                class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors"></button>
                                    </template>
                                </div>
                                {{-- sandbox kosong: dokumen pratinjau tidak boleh menjalankan skrip atau membocorkan CSS ke halaman --}}
                                <iframe sandbox :srcdoc="previews[tab] ? previews[tab].html : ''" class="min-h-[22rem] w-full flex-1 rounded-lg border border-slate-200 bg-white"></iframe>
                                <p class="mt-1.5 text-[11px] text-slate-400">Tampilan di Excel dapat sedikit berbeda dari pratinjau ini.</p>
                            </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="tutup()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Tutup</button>
                        <button type="button" @click="pratinjau()" :disabled="! bisaPratinjau || mempratinjau"
                                class="rounded-lg bg-ink-900 px-4 py-2 text-sm font-semibold text-white hover:bg-ink-800 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="! mempratinjau">Pratinjau</span>
                            <span x-show="mempratinjau">Memproses…</span>
                        </button>
                        <button type="button" @click="unduh()" :disabled="! token || mengunduh"
                                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                            Unduh File Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endonce
