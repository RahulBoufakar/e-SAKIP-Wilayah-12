<?php

namespace App\Services;

use App\Http\Controllers\Concerns\AppliesIkuTimFilter;
use App\Models\CapaianKinerja;
use App\Models\DokumenLaporanKegiatan;
use App\Models\Iku;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Menormalkan dokumen dari 3 sumber (Usulan Proker, Pelaporan Kegiatan,
 * Capaian Kinerja) ke satu bentuk baris untuk halaman Dokumentasi Admin.
 * Hanya file yang benar-benar ada di disk 'private' yang ikut dikembalikan.
 */
class DokumentasiService
{
    use AppliesIkuTimFilter;

    // field URL => [kolom, label]
    private const USULAN_FILE = [
        'kak' => ['file_kak_pdf', 'KAK / TOR'],
        'rab-pdf' => ['file_rab_pdf', 'RAB (PDF)'],
        'rab-excel' => ['file_rab_excel', 'RAB (Excel)'],
    ];

    /** @return Collection<int, array> diurutkan updated_at terbaru dulu */
    public function rows(string $kategori, TahunAnggaran $tahunAnggaran, Request $request, ?string $triwulan = null): Collection
    {
        $rows = collect();

        if (in_array($kategori, ['semua', 'usulan'], true)) {
            $rows = $rows->concat($this->usulanRows($tahunAnggaran, $request, $triwulan));
        }
        if (in_array($kategori, ['semua', 'pelaporan'], true)) {
            $rows = $rows->concat($this->pelaporanRows($tahunAnggaran, $request, $triwulan));
        }
        if (in_array($kategori, ['semua', 'capaian'], true)) {
            $rows = $rows->concat($this->capaianRows($tahunAnggaran, $request, $triwulan));
        }

        return $rows->sortByDesc(fn ($r) => $r['tanggal']?->getTimestamp() ?? 0)->values();
    }

    /**
     * Kolom file yang valid untuk satu komponen capaian: [field => label].
     * Meniru fileFields() di TimKerja\CapaianKinerjaController (yang private).
     * arsitektur_pts tidak punya 'kolom' di config: kedua komponennya
     * (akreditasi & penggabungan) hanya memakai file_bukti_dukung.
     */
    public function capaianFileFields(string $tipe, string $komponen): array
    {
        if ($tipe === 'arsitektur_pts') {
            return ['file_bukti_dukung' => 'Bukti Dukung'];
        }

        $config = config("capaian_kinerja_tipe.{$tipe}") ?? [];
        $fields = collect($config['kolom'] ?? [])->where('tipe', 'file')->pluck('label', 'field')->all();

        if (($config['bukti'] ?? true) && ! isset($fields['file_bukti_dukung'])) {
            $fields['file_bukti_dukung'] = 'Bukti Dukung';
        }

        return $fields;
    }

    private function usulanRows(TahunAnggaran $ta, Request $request, ?string $triwulan): Collection
    {
        $query = UsulanProgramKerja::with(['iku.timKerja', 'detailKegiatan'])->where('tahun', $ta->tahun);
        $this->applyIkuTimFilter($query, $request);

        $rows = collect();

        foreach ($query->get() as $usulan) {
            $tw = $this->triwulanDariBulan($usulan->detailKegiatan?->bulan_kegiatan);
            if ($triwulan && ! in_array($triwulan, $tw, true)) {
                continue;
            }

            foreach (self::USULAN_FILE as $field => [$kolom, $label]) {
                if (! $this->ada($usulan->{$kolom})) {
                    continue;
                }

                $rows->push($this->baris(
                    key: "usulan-{$usulan->id}-{$field}",
                    nama: $label,
                    konteks: $usulan->nama_usulan,
                    iku: $usulan->iku,
                    tanggal: $usulan->updated_at,
                    status: $usulan->status_validasi,
                    triwulan: $tw,
                    previewUrl: $field === 'rab-excel' ? null : route('admin.dokumentasi.file.usulan.preview', [$usulan->id, $field]),
                    downloadUrl: route('admin.dokumentasi.file.usulan.unduh', [$usulan->id, $field]),
                ));
            }
        }

        return $rows;
    }

    private function pelaporanRows(TahunAnggaran $ta, Request $request, ?string $triwulan): Collection
    {
        $relasi = 'laporan.proker.usulanProgramKerja';

        $query = DokumenLaporanKegiatan::with([
            'laporan.proker.usulanProgramKerja.iku.timKerja',
            'laporan.proker.usulanProgramKerja.detailKegiatan',
        ])
            ->whereNotNull('file_dokumen')
            ->where('file_dokumen', '!=', '')
            ->whereHas($relasi, fn ($q) => $q->where('tahun', $ta->tahun));
        $this->applyIkuTimFilter($query, $request, $relasi);

        $rows = collect();

        foreach ($query->get() as $dokumen) {
            $proker = $dokumen->laporan?->proker;
            $usulan = $proker?->usulanProgramKerja;

            $tw = $this->triwulanDariBulan($usulan?->detailKegiatan?->bulan_kegiatan);
            if ($triwulan && ! in_array($triwulan, $tw, true)) {
                continue;
            }
            if (! $this->ada($dokumen->file_dokumen)) {
                continue;
            }

            $rows->push($this->baris(
                key: "laporan-{$dokumen->id}",
                nama: $dokumen->nama_dokumen,
                konteks: trim(($proker?->kode_proker ?? '').' '.($usulan?->nama_usulan ?? '')),
                iku: $usulan?->iku,
                tanggal: $dokumen->updated_at,
                status: $dokumen->status_validasi,
                triwulan: $tw,
                previewUrl: route('admin.dokumentasi.file.laporan.preview', $dokumen->id),
                downloadUrl: route('admin.dokumentasi.file.laporan.unduh', $dokumen->id),
            ));
        }

        return $rows;
    }

    private function capaianRows(TahunAnggaran $ta, Request $request, ?string $triwulan): Collection
    {
        $query = CapaianKinerja::with(['iku.timKerja', 'triwulan'])
            ->where('tahun_anggaran_id', $ta->id)
            ->whereHas('iku', fn ($q) => $q->whereNotNull('tipe_iku'))
            ->when($triwulan, fn ($q) => $q->whereHas('triwulan', fn ($t) => $t->where('kode', $triwulan)));
        $this->applyIkuTimFilter($query, $request);

        $headers = $query->get()->keyBy('id');
        $rows = collect();

        foreach ($headers->groupBy(fn ($h) => $h->iku->tipe_iku) as $tipe => $group) {
            $labelTipe = config("capaian_kinerja_tipe.{$tipe}.label", $tipe);

            foreach (CapaianKinerja::komponenUntukTipe($tipe) as $komponen => $model) {
                $fields = $this->capaianFileFields($tipe, $komponen);

                $barisQuery = $model::query()->whereIn('capaian_kinerja_id', $group->pluck('id')->all());
                if (method_exists($model, 'pts')) {
                    $barisQuery->with('pts');
                }

                foreach ($barisQuery->get() as $baris) {
                    $header = $headers[$baris->capaian_kinerja_id];
                    $pts = method_exists($baris, 'pts') ? $baris->pts?->nama_pts : null;
                    $konteks = $labelTipe
                        .($komponen === 'utama' ? '' : ' ('.ucfirst($komponen).')')
                        .($pts ? ' · '.$pts : '');

                    foreach ($fields as $field => $labelField) {
                        if (! $baris->fileTersedia($field)) {
                            continue;
                        }

                        $params = ['tipe' => $tipe, 'komponen' => $komponen, 'barisId' => $baris->id, 'field' => $field];

                        $rows->push($this->baris(
                            key: "capaian-{$tipe}-{$komponen}-{$baris->id}-{$field}",
                            nama: $labelField,
                            konteks: $konteks,
                            iku: $header->iku,
                            tanggal: $baris->updated_at,
                            status: $baris->status_validasi,
                            triwulan: $header->triwulan ? [$header->triwulan->kode] : [],
                            previewUrl: route('admin.dokumentasi.file.capaian.preview', $params),
                            downloadUrl: route('admin.dokumentasi.file.capaian.unduh', $params),
                        ));
                    }
                }
            }
        }

        return $rows;
    }

    /** bulan kegiatan [3,4,5] (dari rentang tanggal) => ['TW1','TW2'] */
    private function triwulanDariBulan(?array $bulan): array
    {
        return collect($bulan ?? [])
            ->map(fn ($b) => (int) ceil(((int) $b) / 3))
            ->filter(fn ($n) => $n >= 1 && $n <= 4)
            ->unique()->sort()
            ->map(fn ($n) => "TW{$n}")
            ->values()->all();
    }

    private function ada(?string $path): bool
    {
        return filled($path) && Storage::disk('private')->exists($path);
    }

    private function baris(
        string $key,
        string $nama,
        string $konteks,
        ?Iku $iku,
        ?CarbonInterface $tanggal,
        ?string $status,
        array $triwulan,
        ?string $previewUrl,
        string $downloadUrl,
    ): array {
        return [
            'key' => $key,
            'nama' => $nama,
            'konteks' => $konteks,
            'iku' => $iku?->kode,
            'iku_deskripsi' => $iku?->deskripsi,
            'tim' => $iku ? $iku->timKerja->pluck('nama_tim')->all() : [],
            'triwulan' => $triwulan,
            'tanggal' => $tanggal,
            'status' => $status,
            'preview_url' => $previewUrl,
            'download_url' => $downloadUrl,
        ];
    }
}