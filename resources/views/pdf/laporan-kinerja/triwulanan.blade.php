<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $laporan->label }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #0d3145; }
        h1 { font-size: 16px; margin: 0 0 2px 0; color: #0d3145; }
        h2 { font-size: 12px; margin: 18px 0 6px 0; color: #155f66; border-bottom: 1px solid #d4f3f3; padding-bottom: 3px; }
        .subtitle { font-size: 10px; color: #64748b; margin: 0 0 14px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background-color: #0d3145; color: #ffffff; font-size: 10px; }
        td { font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .meta-table td { border: none; padding: 1px 0; font-size: 10px; }
        .row-alert { background-color: #fef2f2; }
        .row-warn { background-color: #fffbeb; }
        .footer-note { margin-top: 16px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Laporan Kinerja Triwulanan — {{ $laporan->label_periode }}</h1>
    <p class="subtitle">eSAKIP LLDikti Wilayah XII &middot; Versi {{ $laporan->versi }} &middot; Dicetak {{ now()->format('d/m/Y H:i') }}</p>

    <table class="meta-table">
        <tr><td style="width:180px;"><strong>Tahun Anggaran</strong></td><td>: {{ $tahunAnggaran->tahun }}</td></tr>
        <tr><td><strong>Rata-rata Capaian Seluruh IKU</strong></td><td>: {{ $rataCapaian !== null ? number_format($rataCapaian, 2, ',', '.').'%' : 'Belum ada data' }}</td></tr>
        <tr><td><strong>Jumlah IKU Perlu Perhatian</strong></td><td>: {{ $sorotan->count() }} dari {{ $baris->count() }} IKU (belum diisi / ditolak / capaian &lt; 50%)</td></tr>
    </table>

    <h2>Target vs Realisasi per IKU</h2>
    <table>
        <thead>
            <tr>
                <th>Sasaran Kegiatan</th>
                <th>IKU / IKK</th>
                <th>Tim Kerja</th>
                <th class="text-center">Target PK</th>
                <th class="text-center">Target TW</th>
                <th class="text-center">Realisasi</th>
                <th class="text-center">Capaian (%)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $b)
                @php
                    $rowClass = $b['status'] === 'ditolak' || $b['status'] === null ? 'row-alert' : (($b['capaian_persen'] !== null && $b['capaian_persen'] < 50) ? 'row-warn' : '');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ $b['sasaran'] }}</td>
                    <td>{{ $b['kode'] }} — {{ $b['deskripsi'] }}</td>
                    <td>{{ $b['tim'] }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($b['target_pk'], 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">{{ $b['target_triwulan'] ?? '—' }}</td>
                    <td class="text-center">{{ $b['realisasi'] ?? '—' }}</td>
                    <td class="text-center">{{ $b['capaian_persen'] !== null ? number_format($b['capaian_persen'], 2, ',', '.') : '—' }}</td>
                    <td class="text-center">{{ $b['status'] ? ucfirst(str_replace('_', ' ', $b['status'])) : 'Belum Diisi' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">Belum ada Sasaran Kegiatan untuk Tahun Anggaran ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Analisis Kinerja — Kendala &amp; Tindak Lanjut</h2>
    <table>
        <thead>
            <tr>
                <th>IKU / IKK</th>
                <th>Progress</th>
                <th>Kendala</th>
                <th>Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($analisaList as $a)
                <tr>
                    <td>{{ $a->iku->kode ?? '-' }}</td>
                    <td>{{ $a->progress ?? '—' }}</td>
                    <td>{{ $a->kendala ?? '—' }}</td>
                    <td>{{ $a->tindak_lanjut ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Tidak ada kendala/tindak lanjut yang dilaporkan pada triwulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">Dokumen ini dihasilkan otomatis oleh sistem eSAKIP LLDikti Wilayah XII pada {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
