// Menguji logika Alpine modal RAB Generator (aturan pilihan, payload, status tombol, penanganan galat)
// tanpa browser. Skrip diekstrak langsung dari file Blade-nya.
// Jalankan: node --test tests/Js
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const blade = readFileSync(join(dirname(fileURLToPath(import.meta.url)), '../../resources/views/components/rab-generator/modal.blade.php'), 'utf8');
const skrip = /<script>([\s\S]*?)<\/script>/.exec(blade)[1];

const CFG = { pohonUrl: '/pohon', previewUrl: '/preview', unduhUrl: '/unduh/__TOKEN__' };

const POHON = {
    file_excel_id: 7,
    sheets: [
        { id: 1, kode_sheet: '7735.951', nama_sheet: 'Layanan', ada_header: true, ada_footer: true, kategori: [
            { id: 10, kode: '051', nama: 'K1', kelompok: [{ id: 100, kode: 'A', label: 'Sub A' }, { id: 101, kode: 'B', label: 'Sub B' }] },
            { id: 11, kode: '052', nama: 'K2', kelompok: [{ id: 102, kode: 'A', label: 'Sub A' }] },
            { id: 12, kode: '053', nama: 'Kosong', kelompok: [] },
        ] },
        { id: 2, kode_sheet: '7733.001', nama_sheet: 'Lain', ada_header: true, ada_footer: false, kategori: [
            { id: 20, kode: '051', nama: 'K1', kelompok: [{ id: 200, kode: 'A', label: 'Sub A' }] },
        ] },
    ],
};

/** Respons fetch palsu. */
const balas = (status, body, headers = {}) => ({
    ok: status >= 200 && status < 300,
    status,
    json: async () => { if (body === undefined) throw new Error('bukan json'); return body; },
    blob: async () => 'BLOB',
    headers: { get: (k) => headers[k] ?? null },
});

/** Buat instance komponen dengan fetch/DOM palsu. `jawaban` = antrean respons fetch. */
function buat(jawaban = []) {
    const panggilan = [];
    const unduhan = [];
    let pabrik;

    globalThis.document = {
        addEventListener: (nama, fn) => { if (nama === 'alpine:init') fn(); },
        querySelector: () => ({ content: 'CSRF123' }),
        createElement: () => { const a = { click() { unduhan.push({ href: a.href, nama: a.download }); }, remove() {} }; return a; },
        body: { appendChild() {} },
    };
    globalThis.Alpine = { data: (nama, fn) => { pabrik = fn; } };
    globalThis.URL.createObjectURL = () => 'blob:x';
    globalThis.URL.revokeObjectURL = () => {};
    globalThis.fetch = async (url, opsi) => {
        panggilan.push({ url, opsi });
        const r = jawaban.shift();
        if (r instanceof Error) throw r;
        return r;
    };

    new Function(skrip)();
    return { c: pabrik(CFG), panggilan, unduhan };
}

async function siap(jawaban = []) {
    const t = buat([balas(200, POHON), ...jawaban]);
    await t.c.buka();
    return t;
}

test('buka() memuat pohon sekali, header/footer default sesuai ketersediaan, tanpa kelompok tercentang', async () => {
    const { c, panggilan } = await siap();

    assert.equal(c.open, true);
    assert.equal(c.fileId, 7);
    assert.equal(c.sheets.length, 2);
    assert.deepEqual(c.pilih[1], { on: false, header: true, footer: true, kelompok: {} });
    assert.deepEqual(c.pilih[2], { on: false, header: true, footer: false, kelompok: {} }); // sheet 2 tanpa footer

    await c.buka(); // dibuka lagi: tidak memuat ulang
    assert.equal(panggilan.length, 1);
});

test('galat memuat pohon ditampilkan dengan pesan dari server, dan bisa dicoba lagi', async () => {
    const t = buat([balas(404, { message: 'Belum ada file master RAB yang aktif.' }), balas(200, POHON)]);

    await t.c.buka();
    assert.equal(t.c.galatPohon, 'Belum ada file master RAB yang aktif.');
    assert.equal(t.c.sheets.length, 0);

    t.c.tutup();
    await t.c.buka();
    assert.equal(t.c.galatPohon, null);
    assert.equal(t.c.sheets.length, 2);
});

test('galat jaringan memberi pesan berbahasa Indonesia', async () => {
    const t = buat([new TypeError('Failed to fetch')]);
    await t.c.buka();
    assert.match(t.c.galatPohon, /Tidak dapat terhubung/);
});

test('Pratinjau nonaktif sampai ada sheet terpilih DAN tiap sheet terpilih punya minimal satu kelompok', async () => {
    const { c } = await siap();

    assert.equal(c.bisaPratinjau, false);              // belum ada sheet
    c.pilih[1].on = true;
    assert.equal(c.bisaPratinjau, false);              // sheet tanpa kelompok
    c.pilih[1].kelompok[100] = true;
    assert.equal(c.bisaPratinjau, true);
    c.pilih[2].on = true;
    assert.equal(c.bisaPratinjau, false);              // sheet kedua terpilih tapi kosong
    c.pilih[2].kelompok[200] = true;
    assert.equal(c.bisaPratinjau, true);
    c.pilih[2].kelompok[200] = false;
    assert.equal(c.bisaPratinjau, false);
});

test('mencentang kategori mencentang semua kelompoknya; mencentang lagi mengosongkan', async () => {
    const { c } = await siap();
    const s = c.sheets[0];
    const k1 = s.kategori[0];

    c.toggleKategori(s, k1);
    assert.deepEqual(c.idKelompok(s), [100, 101]);
    assert.equal(c.semuaKelompok(s, k1), true);

    c.toggleKategori(s, k1);
    assert.deepEqual(c.idKelompok(s), []);
});

test('kategori sebagian tercentang: indeterminate, dan toggle melengkapinya', async () => {
    const { c } = await siap();
    const s = c.sheets[0];
    const k1 = s.kategori[0];

    c.pilih[1].kelompok[100] = true;
    assert.equal(c.sebagianKelompok(s, k1), true);
    assert.equal(c.semuaKelompok(s, k1), false);

    c.toggleKategori(s, k1);
    assert.equal(c.semuaKelompok(s, k1), true);
});

test('kategori tanpa kelompok tidak pernah dianggap tercentang atau sebagian', async () => {
    const { c } = await siap();
    const kosong = c.sheets[0].kategori[2];

    assert.equal(c.semuaKelompok(c.sheets[0], kosong), false);
    assert.equal(c.sebagianKelompok(c.sheets[0], kosong), false);
});

test('pratinjau() mengirim payload yang benar dengan CSRF, lalu mengisi token dan tab', async () => {
    const { c, panggilan } = await siap([balas(200, { status: 'ok', preview_token: 'tok-1', previews: [{ judul: '7735.951', html: '<p>x</p>' }] })]);

    c.pilih[1].on = true;
    c.pilih[1].footer = false;
    c.toggleKategori(c.sheets[0], c.sheets[0].kategori[0]);
    c.pilih[2].on = false; // sheet tidak terpilih tidak ikut dikirim
    c.tab = 3;

    await c.pratinjau();

    const { url, opsi } = panggilan[1];
    assert.equal(url, '/preview');
    assert.equal(opsi.method, 'POST');
    assert.equal(opsi.headers['X-CSRF-TOKEN'], 'CSRF123');
    assert.equal(opsi.headers.Accept, 'application/json');
    assert.deepEqual(JSON.parse(opsi.body), {
        file_excel_id: 7,
        sheets: [{ sheet_id: 1, header: true, footer: false, kelompok_ids: [100, 101] }],
    });
    assert.equal(c.token, 'tok-1');
    assert.equal(c.previews.length, 1);
    assert.equal(c.tab, 0);
    assert.equal(c.basi, false);
    assert.equal(c.mempratinjau, false);
});

test('pratinjau() tidak mengirim apa pun bila belum memenuhi aturan pilihan', async () => {
    const { c, panggilan } = await siap();
    await c.pratinjau();
    assert.equal(panggilan.length, 1); // hanya pemuatan pohon
});

test('mengubah pilihan setelah pratinjau menonaktifkan Unduh dan menandai pratinjau basi', async () => {
    const { c } = await siap([balas(200, { status: 'ok', preview_token: 'tok-1', previews: [{ judul: 'a', html: '' }] })]);
    c.pilih[1].on = true;
    c.pilih[1].kelompok[100] = true;
    await c.pratinjau();
    assert.equal(c.token, 'tok-1');

    c.berubah();
    assert.equal(c.token, null);
    assert.equal(c.basi, true);

    await c.unduh(); // tanpa token: tidak melakukan apa pun
    assert.equal(c.mengunduh, false);
});

test('galat validasi 422 menampilkan pesan pertama dari server', async () => {
    const { c } = await siap([balas(422, { message: 'x', errors: { sheets: ['Ada sheet yang bukan milik file master aktif.'] } })]);
    c.pilih[1].on = true;
    c.pilih[1].kelompok[100] = true;

    await c.pratinjau();

    assert.equal(c.galat, 'Ada sheet yang bukan milik file master aktif.');
    assert.equal(c.token, null);
    assert.equal(c.mempratinjau, false);
});

test('galat 429 dan 419 memberi pesan yang jelas; respons bukan JSON memakai pesan cadangan', async () => {
    const { c } = await siap([balas(429, {}), balas(419, {}), balas(500, undefined)]);
    c.pilih[1].on = true;
    c.pilih[1].kelompok[100] = true;

    await c.pratinjau();
    assert.match(c.galat, /Terlalu banyak permintaan/);
    await c.pratinjau();
    assert.match(c.galat, /Sesi berakhir/);
    await c.pratinjau();
    assert.equal(c.galat, 'Pratinjau gagal dibuat.');
});

test('unduh() mengunduh berkas dengan nama dari Content-Disposition', async () => {
    const { c, panggilan, unduhan } = await siap([
        balas(200, { status: 'ok', preview_token: 'tok-9', previews: [{ judul: 'a', html: '' }] }),
        balas(200, undefined, { 'Content-Disposition': 'attachment; filename=RAB_20261004_101010.xlsx' }),
    ]);
    c.pilih[1].on = true;
    c.pilih[1].kelompok[100] = true;
    await c.pratinjau();

    await c.unduh();

    assert.equal(panggilan[2].url, '/unduh/tok-9');
    assert.deepEqual(unduhan, [{ href: 'blob:x', nama: 'RAB_20261004_101010.xlsx' }]);
    assert.equal(c.mengunduh, false);
    assert.equal(c.galat, null);
});

test('unduh() dengan token kedaluwarsa (404) menampilkan pesan server dan memaksa pratinjau ulang', async () => {
    const { c, unduhan } = await siap([
        balas(200, { status: 'ok', preview_token: 'tok-9', previews: [{ judul: 'a', html: '' }] }),
        balas(404, { message: 'Pratinjau sudah kedaluwarsa. Buat pratinjau ulang.' }),
    ]);
    c.pilih[1].on = true;
    c.pilih[1].kelompok[100] = true;
    await c.pratinjau();

    await c.unduh();

    assert.equal(c.galat, 'Pratinjau sudah kedaluwarsa. Buat pratinjau ulang.');
    assert.equal(c.token, null);          // tombol Unduh nonaktif lagi
    assert.deepEqual(unduhan, []);
});

test('regresi: iframe pratinjau dibuat di dalam <template x-if>, bukan di wadah x-show (iframe yang dimuat saat tersembunyi bisa tidak tergambar)', () => {
    const mulai = blade.indexOf('<template x-if="! mempratinjau && previews.length > 0">');
    assert.notEqual(mulai, -1, 'wadah pratinjau harus memakai x-if');

    const blok = blade.slice(mulai, blade.indexOf('</template>', blade.indexOf('<iframe', mulai)));
    const sebelumIframe = blok.slice(0, blok.indexOf('<iframe'));

    assert.ok(blok.includes('<iframe'), 'iframe harus berada di dalam blok x-if');
    assert.ok(! /x-show/.test(sebelumIframe), 'tidak boleh ada x-show di antara x-if dan iframe');
});
