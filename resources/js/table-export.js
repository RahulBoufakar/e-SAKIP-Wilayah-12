// Tombol export tabel (Copy, CSV, Excel, PDF, Print) — dipakai lewat komponent <x-export-buttons>.
//
// Data diambil dari tabel yang sedang tampil di halaman:
//  - kolom yang <th>-nya bertanda data-export-ignore (mis. "Aksi") dilewati;
//  - sel yang berisi elemen [data-export-value] (mis. <x-truncate-cell>) memakai nilai itu
//    supaya teks lengkap yang ter-export, bukan versi terpotong;
//  - baris "data kosong" (satu sel colspan) dilewati.
// Library Excel/PDF baru dimuat saat tombolnya diklik, supaya bundle halaman tetap kecil.

const bersihkan = (teks) => (teks ?? '').replace(/\s+/g, ' ').trim();

function nilaiSel(sel) {
    const ditandai = sel.querySelectorAll('[data-export-value]');
    if (ditandai.length) {
        return [...ditandai].map((el) => bersihkan(el.dataset.exportValue)).join(' ');
    }

    const salinan = sel.cloneNode(true);
    salinan.querySelectorAll('dialog, template, script, [data-export-skip]').forEach((el) => el.remove());

    return bersihkan(salinan.textContent);
}

export function ambilDataTabel(tabel) {
    const barisHeader = tabel.tHead?.rows[tabel.tHead.rows.length - 1];
    const headerSel = barisHeader ? [...barisHeader.cells] : [];
    const indeksDiabaikan = new Set(
        headerSel.flatMap((th, i) => (th.hasAttribute('data-export-ignore') ? [i] : [])),
    );
    const pilih = (sel) => sel.filter((_, i) => !indeksDiabaikan.has(i));

    const header = pilih(headerSel).map(nilaiSel);
    const rows = [...tabel.tBodies]
        .flatMap((tbody) => [...tbody.rows])
        .filter((tr) => !(tr.cells.length === 1 && tr.cells[0].colSpan > 1))
        .map((tr) => pilih([...tr.cells]).map(nilaiSel));

    return { header, rows };
}

export async function salinTeks(teks) {
    // Clipboard API hanya tersedia di konteks aman (https / localhost), sisanya pakai cara lama.
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(teks);
        return;
    }

    const area = document.createElement('textarea');
    area.value = teks;
    area.setAttribute('readonly', '');
    area.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
    // Taruh di dalam <dialog> yang sedang terbuka (kalau ada): elemen di luar modal dialog tidak bisa difokus.
    (document.activeElement?.closest('dialog[open]') ?? document.body).appendChild(area);
    area.select();
    const berhasil = document.execCommand('copy');
    area.remove();

    if (! berhasil) {
        throw new Error('Gagal menyalin ke clipboard.');
    }
}

function unduh(blob, namaFile) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = namaFile;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

const escapeHtml = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function sisipkanPemisah(teks, n = 15) {
    if (typeof teks !== 'string') return teks ?? '';
    return teks.replace(/\S{15,}/g, (kata) =>
        kata.replace(new RegExp(`(.{${n}})`, 'g'), '$1\u200B')
    );
}

const ekspor = {
    async copy({ header, rows }) {
        await salinTeks([header, ...rows].map((r) => r.join('\t')).join('\n'));
        return `${rows.length} baris disalin ke clipboard.`;
    },

    csv({ header, rows }, nama) {
        const sel = (v) => (/[",\n;]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v);
        const isi = [header, ...rows].map((r) => r.map(sel).join(',')).join('\r\n');
        // BOM supaya Excel membaca UTF-8 dengan benar.
        unduh(new Blob(['﻿' + isi], { type: 'text/csv;charset=utf-8' }), `${nama}.csv`);
    },

    async excel({ header, rows }, nama, judul) {
        const { default: writeExcelFile } = await import('write-excel-file/browser');
        const columns = header.map((_, i) =>
            ({ width: Math.min(60, Math.max(8, ...[header, ...rows].map((r) => (r[i] ?? '').length))) }));
        const blob = await writeExcelFile([header.map((value) => ({ value, fontWeight: 'bold' })), ...rows], {
            columns,
            sheet: judul.slice(0, 31).replace(/[\\/?*[\]:]/g, ' '),
        }).toBlob();
        unduh(blob, `${nama}.xlsx`);
    },

    async pdf({ header, rows }, nama, judul) {
        const [{ jsPDF }, { autoTable }] = await Promise.all([import('jspdf'), import('jspdf-autotable')]);
        const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
        const marginLR = 40;
        const lebarTersedia = doc.internal.pageSize.getWidth() - marginLR * 2;

        const headerSiap = header.map((h) => sisipkanPemisah(h));
        const rowsSiap = rows.map((r) => r.map((sel) => sisipkanPemisah(sel)));

        const columnStyles = {};
        const jumlahKolom = headerSiap.length;
        if (jumlahKolom > 0) {
            const minLebar = Math.max(35, (lebarTersedia / jumlahKolom) * 0.5);
            headerSiap.forEach((_, i) => {
                columnStyles[i] = {
                    cellWidth: 'auto',
                    minCellWidth: minLebar,
                };
            });
        }

        doc.setFontSize(13);
        doc.text(judul, marginLR, 40);
        autoTable(doc, {
            head: [headerSiap],
            body: rowsSiap,
            startY: 55,
            styles: { fontSize: 8, cellPadding: 4, overflow: 'linebreak' },
            headStyles: { fillColor: [15, 23, 42] },
            margin: { left: marginLR, right: marginLR },
            columnStyles,
        });
        doc.save(`${nama}.pdf`);
    },

    print({ header, rows }, nama, judul) {
        const jendela = window.open('', '_blank');
        if (! jendela) {
            throw new Error('Jendela print diblokir browser. Izinkan pop-up untuk situs ini.');
        }
        const tr = (sel, tag) => `<tr>${sel.map((v) => `<${tag}>${escapeHtml(v)}</${tag}>`).join('')}</tr>`;
        jendela.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${escapeHtml(judul)}</title>
            <style>
                body { font-family: system-ui, sans-serif; font-size: 11px; margin: 24px; color: #0f172a; }
                h1 { font-size: 15px; margin: 0 0 12px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; vertical-align: top; word-break: break-word; overflow-wrap: anywhere; }
                th { background: #0f172a; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                @page { size: landscape; }
            </style></head><body>
            <h1>${escapeHtml(judul)}</h1>
            <table><thead>${tr(header, 'th')}</thead><tbody>${rows.map((r) => tr(r, 'td')).join('')}</tbody></table>
            </body></html>`);
        jendela.document.close();
        jendela.focus();
        jendela.print();
    },
};

// Alpine component untuk <x-export-buttons>.
export function tableExport(targetId, judul) {
    return {
        sibuk: null,
        pesan: '',
        formatDipilih: null,

        bukaKonfirmasi(format, label) {
            if (this.sibuk) return;
            if (format === 'copy') {
                this.jalankan('copy');
                return;
            }
            this.formatDipilih = { id: format, label: label };
            this.$refs.dialogKonfirmasi?.showModal();
        },

        tutupKonfirmasi() {
            this.$refs.dialogKonfirmasi?.close();
            this.formatDipilih = null;
        },

        lanjutkanEkspor() {
            if (! this.formatDipilih) return;
            const format = this.formatDipilih.id;
            this.tutupKonfirmasi();
            this.jalankan(format);
        },

        async jalankan(format) {
            const tabel = document.getElementById(targetId);
            if (! tabel || this.sibuk) {
                return;
            }

            this.sibuk = format;
            this.pesan = '';
            try {
                const tanggal = new Date().toISOString().slice(0, 10);
                const nama = `${judul} ${tanggal}`.replace(/[\\/:*?"<>|]+/g, '-').replace(/\s+/g, '_');
                const hasil = await ekspor[format](ambilDataTabel(tabel), nama, judul);
                this.pesan = hasil ?? '';
            } catch (e) {
                this.pesan = e.message || 'Export gagal.';
            } finally {
                this.sibuk = null;
                if (this.pesan) {
                    setTimeout(() => (this.pesan = ''), 2500);
                }
            }
        },
    };
}

// Alpine component untuk tombol copy di <x-truncate-cell>.
export function copyButton(teks) {
    return {
        tersalin: false,
        async salin() {
            try {
                await salinTeks(teks);
                this.tersalin = true;
                setTimeout(() => (this.tersalin = false), 1500);
            } catch (e) {
                alert(e.message);
            }
        },
    };
}
