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
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .badge-ok { background-color: #d1fae5; color: #065f46; }
        .badge-warn { background-color: #fef3c7; color: #92400e; }
        .badge-idle { background-color: #f1f5f9; color: #475569; }
        .footer-note { margin-top: 16px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>Laporan Kinerja Bulanan — {{ $laporan->label_periode }}</h1>
    <p class="subtitle">eSAKIP LLDikti Wilayah XII &middot; Versi {{ $laporan->versi }} &middot; Dicetak {{ now()->format('d/m/Y H:i') }}</p>

    <table class="meta-table">
        <tr><td style="width:140px;"><strong>Tahun Anggaran</strong></td><td>: {{ $tahunAnggaran->tahun }}</td></tr>
        <tr><td><strong>Total Proker Aktif</strong></td><td>: {{ $usulanList->count() }} kegiatan tervalidasi berjalan pada bulan ini</td></tr>
    </table>

    <h2>Ringkasan Jenis Kegiatan</h2>
    <table>
        <thead>
            <tr>
                <th>Kunjungan Lapangan</th>
                <th>Lainnya</th>
                <th>Belum Divalidasi Jenisnya</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $breakdownJenis['kunjungan_lapangan'] }}</td>
                <td class="text-center">{{ $breakdownJenis['lainnya'] }}</td>
                <td class="text-center">{{ $breakdownJenis['belum_divalidasi'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Ringkasan per Tim Kerja</h2>
    <table>
        <thead>
            <tr>
                <th>Tim Kerja</th>
                <th class="text-center">Jumlah Proker</th>
                <th class="text-right">Total Anggaran Rencana (Rp)</th>
                <th class="text-center">Belum Diunggah</th>
                <th class="text-center">Menunggu Validasi</th>
                <th class="text-center">Disetujui</th>
                <th class="text-center">Ditolak</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perTim as $namaTim => $ringkasan)
                <tr>
                    <td>{{ $namaTim }}</td>
                    <td class="text-center">{{ $ringkasan['jumlah_proker'] }}</td>
                    <td class="text-right">{{ number_format($ringkasan['total_anggaran'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $ringkasan['belum_diunggah'] }}</td>
                    <td class="text-center">{{ $ringkasan['menunggu_validasi'] }}</td>
                    <td class="text-center">{{ $ringkasan['disetujui'] }}</td>
                    <td class="text-center">{{ $ringkasan['ditolak'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">Tidak ada kegiatan berjalan pada bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="subtitle" style="margin-top:2px;">*Total Anggaran Rencana adalah nilai yang diajukan Tim Kerja, bukan realisasi anggaran resmi.</p>

    <h2>Detail Kegiatan</h2>
    <table>
        <thead>
            <tr>
                <th>Tim Kerja</th>
                <th>Nama Kegiatan</th>
                <th>IKU/IKK</th>
                <th>Tempat Pelaksanaan</th>
                <th>Bentuk</th>
                <th class="text-right">Anggaran (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usulanList as $u)
                <tr>
                    <td>{{ $u->iku->timKerja->pluck('nama_tim')->join(', ') ?: '-' }}</td>
                    <td>{{ $u->nama_usulan }}</td>
                    <td>{{ $u->iku->kode ?? '-' }}</td>
                    <td>{{ $u->detailKegiatan->tempat_pelaksanaan ?? '-' }}</td>
                    <td>{{ $u->detailKegiatan->bentuk_kegiatan ?? '-' }}</td>
                    <td class="text-right">{{ number_format($u->detailKegiatan->anggaran ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Tidak ada detail kegiatan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">Dokumen ini dihasilkan otomatis oleh sistem eSAKIP LLDikti Wilayah XII pada {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
