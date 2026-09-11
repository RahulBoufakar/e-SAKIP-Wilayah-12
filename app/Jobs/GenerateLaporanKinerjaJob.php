<?php

namespace App\Jobs;

use App\Events\ActivityOccurred;
use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\JumlahMahasiswa;
use App\Models\JumlahPts;
use App\Models\LaporanKinerja;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Models\User;
use App\Models\UsulanProgramKerja;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateLaporanKinerjaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $laporanKinerjaId)
    {
    }

    public function handle(): void
    {
        $laporan = LaporanKinerja::find($this->laporanKinerjaId);

        if (! $laporan) {
            return;
        }

        try {
            $data = match ($laporan->jenis) {
                'bulanan' => $this->dataBulanan($laporan),
                'triwulanan' => $this->dataTriwulanan($laporan),
                'tahunan' => $this->dataTahunan($laporan),
            };

            $pdf = Pdf::loadView("pdf.laporan-kinerja.{$laporan->jenis}", $data);
            $fileName = "{$laporan->jenis}/{$laporan->tahun_anggaran_id}-{$laporan->id}.pdf";

            Storage::disk('laporan')->put($fileName, $pdf->output());

            $laporan->update(['status' => 'berhasil', 'file_path' => $fileName]);
        } catch (Throwable $e) {
            $laporan->update(['status' => 'gagal', 'catatan' => $e->getMessage()]);

            return;
        }

        // PRD §7: hanya laporan otomatis (generated_by null) yang memicu
        // notifikasi in-app. Generate manual sudah dicatat di Audit Log oleh
        // controller pada saat request generate dikirim.
        if ($laporan->generated_by === null) {
            $recipients = User::role('pimpinan')->get()->merge(User::role('admin')->get())->unique('id');

            event(new ActivityOccurred(
                subject: $laporan,
                description: "menerbitkan Laporan Kinerja \"{$laporan->label}\" secara otomatis",
                causer: null,
                recipients: $recipients,
            ));
        }
    }

    /** §8.1 — fokus Program Kerja & Kegiatan bulan tersebut. */
    private function dataBulanan(LaporanKinerja $laporan): array
    {
        $tahunAnggaran = TahunAnggaran::findOrFail($laporan->tahun_anggaran_id);
        $bulan = $laporan->bulan;

        $usulanList = UsulanProgramKerja::with(['iku.timKerja', 'detailKegiatan', 'programKerja.laporanKegiatan.dokumen'])
            ->where('status_validasi', 'approved')
            ->where('tahun', $tahunAnggaran->tahun)
            ->get()
            ->filter(fn ($u) => in_array($bulan, $u->detailKegiatan->bulan_kegiatan ?? []))
            ->values();

        $breakdownJenis = [
            'kunjungan_lapangan' => $usulanList->filter(fn ($u) => $u->detailKegiatan?->jenis_kegiatan === 'kunjungan_lapangan')->count(),
            'lainnya' => $usulanList->filter(fn ($u) => $u->detailKegiatan?->jenis_kegiatan === 'lainnya')->count(),
            'belum_divalidasi' => $usulanList->filter(fn ($u) => blank($u->detailKegiatan?->jenis_kegiatan))->count(),
        ];

        $perTim = $usulanList
            ->groupBy(fn ($u) => $u->iku->timKerja->pluck('nama_tim')->join(', ') ?: 'Tanpa Tim Kerja')
            ->map(function ($rows) {
                $dokumen = $rows->flatMap(fn ($u) => $u->programKerja?->laporanKegiatan?->dokumen ?? collect());

                return [
                    'jumlah_proker' => $rows->count(),
                    'total_anggaran' => $rows->sum(fn ($u) => (float) ($u->detailKegiatan->anggaran ?? 0)),
                    'belum_diunggah' => $dokumen->where('status_validasi', 'belum_diunggah')->count(),
                    'menunggu_validasi' => $dokumen->where('status_validasi', 'menunggu_validasi')->count(),
                    'disetujui' => $dokumen->where('status_validasi', 'disetujui')->count(),
                    'ditolak' => $dokumen->where('status_validasi', 'ditolak')->count(),
                ];
            });

        return [
            'laporan' => $laporan,
            'tahunAnggaran' => $tahunAnggaran,
            'bulan' => $bulan,
            'usulanList' => $usulanList,
            'breakdownJenis' => $breakdownJenis,
            'perTim' => $perTim,
        ];
    }

    /** §8.2 — fokus Capaian & Realisasi IKU pada triwulan tersebut. */
    private function dataTriwulanan(LaporanKinerja $laporan): array
    {
        $tahunAnggaran = TahunAnggaran::findOrFail($laporan->tahun_anggaran_id);
        $triwulan = Triwulan::findOrFail($laporan->triwulan_id);

        $sasaranList = SasaranKegiatan::with(['iku' => function ($q) use ($triwulan, $tahunAnggaran) {
                $q->with(['timKerja', 'capaianKinerja' => fn ($c) => $c->where('triwulan_id', $triwulan->id)
                    ->where('tahun_anggaran_id', $tahunAnggaran->id)])
                ->orderBy('kode');
            }])
            ->where('tahun_anggaran_id', $tahunAnggaran->id)
            ->orderBy('kode')
            ->get();

        $baris = collect();
        foreach ($sasaranList as $sasaran) {
            foreach ($sasaran->iku as $iku) {
                $c = $iku->capaianKinerja->first();

                $baris->push([
                    'sasaran' => $sasaran->nama_sasaran,
                    'kode' => $iku->kode,
                    'deskripsi' => $iku->deskripsi,
                    'target_pk' => $iku->target_pk,
                    'target_triwulan' => $c->target ?? null,
                    'realisasi' => $c->realisasi ?? null,
                    'capaian_persen' => $c->capaian ?? null,
                    'status' => $c?->status, // null = belum diisi (§4.5)
                    'tim' => $iku->timKerja->pluck('nama_tim')->join(', ') ?: '-',
                ]);
            }
        }

        $nilaiCapaian = $baris->pluck('capaian_persen')->filter(fn ($v) => $v !== null);
        $rataCapaian = $nilaiCapaian->isNotEmpty() ? round($nilaiCapaian->avg(), 2) : null;

        $sorotan = $baris->filter(fn ($b) => $b['status'] === null
            || $b['status'] === 'ditolak'
            || ($b['capaian_persen'] !== null && $b['capaian_persen'] < 50))->values();

        $ikuIds = $sasaranList->flatMap->iku->pluck('id');
        $analisaList = AnalisaKinerja::whereIn('iku_id', $ikuIds)
            ->where('triwulan_id', $triwulan->id)
            ->where('tahun_anggaran_id', $tahunAnggaran->id)
            ->where(fn ($q) => $q->whereNotNull('kendala')->orWhereNotNull('tindak_lanjut'))
            ->with('iku')
            ->get();

        return [
            'laporan' => $laporan,
            'tahunAnggaran' => $tahunAnggaran,
            'triwulan' => $triwulan,
            'baris' => $baris,
            'rataCapaian' => $rataCapaian,
            'sorotan' => $sorotan,
            'analisaList' => $analisaList,
        ];
    }

    /** §8.3 — agregat setahun + progres antar triwulan. */
    private function dataTahunan(LaporanKinerja $laporan): array
    {
        $tahunAnggaran = TahunAnggaran::findOrFail($laporan->tahun_anggaran_id);
        $tahunSebelumnya = TahunAnggaran::where('tahun', $tahunAnggaran->tahun - 1)->first();
        $tw4 = Triwulan::where('kode', 'TW4')->first();
        $triwulanIdByUrutan = Triwulan::orderBy('urutan')->pluck('id', 'urutan');

        $sasaranList = SasaranKegiatan::with(['iku.timKerja', 'iku.capaianKinerja' => fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaran->id)])
            ->where('tahun_anggaran_id', $tahunAnggaran->id)
            ->orderBy('kode')
            ->get();

        $ikuList = $sasaranList->flatMap->iku;

        // Rekap capaian akhir (TW4, §4.4) + progres TW1-TW4 per IKU
        $progresIku = $ikuList->map(function ($iku) use ($tw4, $triwulanIdByUrutan) {
            $perTriwulan = $iku->capaianKinerja->keyBy('triwulan_id');

            $progres = [];
            foreach (range(1, 4) as $urutan) {
                $twId = $triwulanIdByUrutan[$urutan] ?? null;
                $progres["TW{$urutan}"] = $twId ? $perTriwulan->get($twId)?->capaian : null;
            }

            return [
                'kode' => $iku->kode,
                'deskripsi' => $iku->deskripsi,
                'target_pk' => $iku->target_pk,
                'progres' => $progres,
                'capaian_akhir' => $tw4 ? $perTriwulan->get($tw4->id)?->capaian : null,
            ];
        })->values();

        $capaianAkhirValues = $progresIku->pluck('capaian_akhir')->filter(fn ($v) => $v !== null);
        $rataCapaianAkhir = $capaianAkhirValues->isNotEmpty() ? round($capaianAkhirValues->avg(), 2) : null;

        // Ringkasan Program Kerja setahun (§8.3)
        $usulanSetahun = UsulanProgramKerja::with('detailKegiatan')
            ->where('status_validasi', 'approved')
            ->where('tahun', $tahunAnggaran->tahun)
            ->get();

        $ringkasanProker = [
            'total' => $usulanSetahun->count(),
            'kunjungan_lapangan' => $usulanSetahun->filter(fn ($u) => $u->detailKegiatan?->jenis_kegiatan === 'kunjungan_lapangan')->count(),
            'lainnya' => $usulanSetahun->filter(fn ($u) => $u->detailKegiatan?->jenis_kegiatan === 'lainnya')->count(),
            'belum_divalidasi' => $usulanSetahun->filter(fn ($u) => blank($u->detailKegiatan?->jenis_kegiatan))->count(),
            'total_anggaran' => $usulanSetahun->sum(fn ($u) => (float) ($u->detailKegiatan->anggaran ?? 0)),
        ];

        // Tren Jumlah Mahasiswa & PTS antar tahun
        $trenMahasiswa = JumlahMahasiswa::with('tahunAnggaran')->get()
            ->groupBy(fn ($r) => $r->tahunAnggaran->tahun)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortKeys();

        $trenPts = JumlahPts::with('tahunAnggaran')->get()
            ->groupBy(fn ($r) => $r->tahunAnggaran->tahun)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortKeys();

        // Sebaran IKU per Tim Kerja
        $sebaranTim = $ikuList
            ->flatMap(fn ($iku) => $iku->timKerja->isNotEmpty() ? $iku->timKerja->pluck('nama_tim') : collect(['Tanpa Tim Kerja']))
            ->countBy();

        // Perbandingan dengan tahun sebelumnya (rata-rata capaian akhir TW4)
        $rataCapaianTahunSebelumnya = null;
        if ($tahunSebelumnya && $tw4) {
            $ikuIdsSebelumnya = Iku::whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunSebelumnya->id))->pluck('id');

            $nilaiSebelumnya = CapaianKinerja::whereIn('iku_id', $ikuIdsSebelumnya)
                ->where('tahun_anggaran_id', $tahunSebelumnya->id)
                ->where('triwulan_id', $tw4->id)
                ->with('iku:id,target_pk')
                ->get()
                ->map(fn ($c) => $c->capaian)
                ->filter(fn ($v) => $v !== null);

            $rataCapaianTahunSebelumnya = $nilaiSebelumnya->isNotEmpty() ? round($nilaiSebelumnya->avg(), 2) : null;
        }

        return [
            'laporan' => $laporan,
            'tahunAnggaran' => $tahunAnggaran,
            'tahunSebelumnya' => $tahunSebelumnya,
            'progresIku' => $progresIku,
            'rataCapaianAkhir' => $rataCapaianAkhir,
            'rataCapaianTahunSebelumnya' => $rataCapaianTahunSebelumnya,
            'ringkasanProker' => $ringkasanProker,
            'trenMahasiswa' => $trenMahasiswa,
            'trenPts' => $trenPts,
            'sebaranTim' => $sebaranTim,
        ];
    }
}
