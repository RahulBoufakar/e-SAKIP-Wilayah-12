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
        .col-half { width: 49%; display: inline-block; vertical-align: top; }
        .footer-note { margin-top: 16px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Laporan Kinerja Tahunan — {{ $laporan->label_periode }}</h1>
    <p class="subtitle">eSAKIP LLDikti Wilayah XII &middot; Versi {{ $laporan->versi }} &middot; Dicetak {{ now()->format('d/m/Y H:i') }}</p>

    <table class="meta-table">
        <tr><td style="width:220px;"><strong>Rata-rata Capaian Akhir Tahun (TW4)</strong></td><td>: {{ $rataCapaianAkhir !== null ? number_format($rataCapaianAkhir, 2, ',', '.').'%' : 'Belum ada data' }}</td></tr>
        <tr>
            <td><strong>Perbandingan Tahun Sebelumnya ({{ $tahunSebelumnya->tahun ?? '-' }})</strong></td>
            <td>: {{ $rataCapaianTahunSebelumnya !== null ? number_format($rataCapaianTahunSebelumnya, 2, ',', '.').'%' : 'Data tidak tersedia' }}</td>
        </tr>
    </table>

    <h2>Progres Capaian per IKU (TW1 – TW4)</h2>
    <table>
        <thead>
            <tr>
                <th>IKU / IKK</th>
                <th class="text-center">Target PK</th>
                <th class="text-center">TW1 (%)</th>
                <th class="text-center">TW2 (%)</th>
                <th class="text-center">TW3 (%)</th>
                <th class="text-center">TW4 (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($progresIku as $p)
                <tr>
                    <td>{{ $p['kode'] }} — {{ $p['deskripsi'] }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($p['target_pk'], 2, ',', '.'), '0'), ',') }}</td>
                    @foreach (['TW1', 'TW2', 'TW3', 'TW4'] as $tw)
                        <td class="text-center">{{ $p['progres'][$tw] !== null ? number_format($p['progres'][$tw], 2, ',', '.') : '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Belum ada data IKU untuk tahun anggaran ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Ringkasan Program Kerja Setahun</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center">Total Proker</th>
                <th class="text-center">Kunjungan Lapangan</th>
                <th class="text-center">Lainnya</th>
                <th class="text-center">Belum Divalidasi</th>
                <th class="text-right">Total Anggaran Rencana (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $ringkasanProker['total'] }}</td>
                <td class="text-center">{{ $ringkasanProker['kunjungan_lapangan'] }}</td>
                <td class="text-center">{{ $ringkasanProker['lainnya'] }}</td>
                <td class="text-center">{{ $ringkasanProker['belum_divalidasi'] }}</td>
                <td class="text-right">{{ number_format($ringkasanProker['total_anggaran'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    <p class="subtitle" style="margin-top:2px;">*Total Anggaran Rencana adalah nilai yang diajukan Tim Kerja, bukan realisasi anggaran resmi.</p>

    <h2>Sebaran IKU per Tim Kerja</h2>
    <table>
        <thead>
            <tr><th>Tim Kerja</th><th class="text-center">Jumlah IKU</th></tr>
        </thead>
        <tbody>
            @forelse ($sebaranTim as $tim => $jumlah)
                <tr><td>{{ $tim }}</td><td class="text-center">{{ $jumlah }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Tren Antar Tahun</h2>
    <div>
        <div class="col-half">
            <table>
                <thead><tr><th>Tahun</th><th class="text-center">Jumlah Mahasiswa</th></tr></thead>
                <tbody>
                    @forelse ($trenMahasiswa as $tahun => $jumlah)
                        <tr><td>{{ $tahun }}</td><td class="text-center">{{ number_format($jumlah, 0, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="col-half" style="margin-left: 2%;">
            <table>
                <thead><tr><th>Tahun</th><th class="text-center">Jumlah PTS</th></tr></thead>
                <tbody>
                    @forelse ($trenPts as $tahun => $jumlah)
                        <tr><td>{{ $tahun }}</td><td class="text-center">{{ number_format($jumlah, 0, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="footer-note">Dokumen ini dihasilkan otomatis oleh sistem eSAKIP LLDikti Wilayah XII pada {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
