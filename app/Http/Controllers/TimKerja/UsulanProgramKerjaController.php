<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UsulanProgramKerjaController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('tim-kerja.layout.app', 'tim-kerja.dashboard');
        }

        $timKerjaIds = $this->activeTimKerjaIds();
        if ($timKerjaIds->isEmpty()) {
            return view('admin.layout.app-error-content', [
                'errorMessage' => 'Anda belum ditugaskan ke Tim Kerja manapun. Hubungi Administrator.',
                'layout' => 'tim-kerja.layout.app',
                'backRoute' => 'tim-kerja.dashboard',
            ]);
        }

        $activeTahunAnggaran = TahunAnggaran::find($tahunAnggaranId);
        $activeTahun = (int) $activeTahunAnggaran->tahun;
        $nextYear = $activeTahun + 1;
        $nextYearAvailable = $this->nextTahunAnggaranExists($tahunAnggaranId);

        // Blokir akses ke tahun depan jika belum tersedia (via submenu h_plus_1 atau param integer)
        if (
            ($request->get('tahun') === 'h_plus_1' || ($request->filled('tahun') && (int) $request->tahun === $nextYear))
            && ! $nextYearAvailable
        ) {
            return view('admin.layout.app-error-content', [
                'errorMessage' => 'Tahun Anggaran ' . $nextYear . ' belum tersedia. Hubungi Administrator.',
                'layout' => 'tim-kerja.layout.app',
                'backRoute' => 'tim-kerja.dashboard',
            ]);
        }

        // Data untuk view: IKUs dan pilihan tahun (dropdown)
        $ikuOptions = Iku::with('sasaranKegiatan.tahunAnggaran')
            ->whereIn('tim_kerja_id', $timKerjaIds)
            ->orderBy('kode')
            ->get(['id', 'kode', 'deskripsi', 'sasaran_kegiatan_id']);

        $tahunOptions = $ikuOptions
            ->pluck('sasaranKegiatan.tahunAnggaran.tahun')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        // Sembunyikan tahun depan dari dropdown jika belum tersedia
        if (! $nextYearAvailable) {
            $tahunOptions = $tahunOptions->reject(fn ($t) => $t === $nextYear);
        }

        // Tentukan tahun yang dipilih
        $tahun = match ($request->get('tahun')) {
            'berjalan' => $activeTahun,
            'h_plus_1' => $nextYear, // sudah lolos gate di atas
            default => $request->filled('tahun') && $tahunOptions->contains((int) $request->tahun)
                ? (int) $request->tahun
                : $tahunOptions->first(),
        };

        // Fallback jika tahun belum ter-set atau tidak ada di opsi
        if (! $tahun || ! $tahunOptions->contains($tahun)) {
            $tahun = $tahunOptions->first();
        }

        if (! $tahun) {
            return view('admin.layout.app-error-content', [
                'errorMessage' => 'Belum ada data Tahun Anggaran yang tersedia. Hubungi Administrator.',
                'layout' => 'tim-kerja.layout.app',
                'backRoute' => 'tim-kerja.dashboard',
            ]);
        }

        $usulanList = UsulanProgramKerja::with(['iku.timKerja'])
            ->where('tahun', $tahun)
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('tim-kerja.usulan-program-kerja.index', compact('usulanList', 'ikuOptions', 'tahunOptions', 'tahun'));
    }

    // POST /tim-kerja/usulan-program-kerja
    public function store(Request $request)
    {
        $timKerjaIds = $this->activeTimKerjaIds();

        if ($timKerjaIds->isEmpty()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Anda belum ditugaskan ke Tim Kerja manapun.']);
        }

        $data = $request->validate([
            'iku_id' => [
                'required',
                Rule::exists('iku', 'id')->where(fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds)),
            ],
            'nama_usulan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ], [
            'iku_id.required' => 'IKU wajib dipilih.',
            'iku_id.exists' => 'IKU tidak valid atau bukan milik Tim Kerja Anda.',
            'nama_usulan.required' => 'Nama Usulan wajib diisi.',
        ]);

        $iku = Iku::with('sasaranKegiatan.tahunAnggaran')->findOrFail($data['iku_id']);
        $data['tahun'] = $iku->sasaranKegiatan->tahunAnggaran->tahun;

        $usulan = UsulanProgramKerja::create($data);

        return redirect()
            ->route('tim-kerja.usulan-program-kerja.show', $usulan->id)
            ->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja berhasil dibuat. Silakan lengkapi file & Detail Kegiatan.']);
    }

    // GET /tim-kerja/usulan-program-kerja/{usulanProgramKerja}
    public function show(UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorizeAksesUsulan($usulanProgramKerja);

        $usulanProgramKerja->load(['iku', 'detailKegiatan']);

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return view('tim-kerja.usulan-program-kerja.show', [
            'usulan' => $usulanProgramKerja,
            'bulanIndo' => $bulanIndo,
        ]);
    }

    // PUT /tim-kerja/usulan-program-kerja/{usulanProgramKerja}
    public function update(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorizeAksesUsulan($usulanProgramKerja);

        if ($usulanProgramKerja->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Usulan ini sedang terkunci dan tidak dapat diubah.']);
        }

        $data = $request->validate([
            'nama_usulan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'file_kak_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'file_rab_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'file_rab_excel' => 'nullable|file|mimes:xls,xlsx|max:10240',
        ], [
            'nama_usulan.required' => 'Nama Usulan wajib diisi.',
            'file_kak_pdf.mimes' => 'File KAK harus berformat PDF.',
            'file_rab_pdf.mimes' => 'File RAB harus berformat PDF.',
            'file_rab_excel.mimes' => 'File RAB Excel harus berformat XLS/XLSX.',
        ]);

        foreach (['file_kak_pdf', 'file_rab_pdf', 'file_rab_excel'] as $field) {
            if ($request->hasFile($field)) {
                if ($usulanProgramKerja->$field) {
                    Storage::disk('public')->delete($usulanProgramKerja->$field);
                }
                $data[$field] = $request->file($field)->store('usulan-program-kerja', 'public');
            }
        }

        $usulanProgramKerja->simpan($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja berhasil disimpan.']);
    }

    // PUT /tim-kerja/usulan-program-kerja/{usulanProgramKerja}/kirim
    public function kirim(UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorizeAksesUsulan($usulanProgramKerja);

        if (! $usulanProgramKerja->can_kirim) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Lengkapi file KAK, RAB PDF, RAB Excel, dan Detail Kegiatan sebelum mengirim.']);
        }

        $usulanProgramKerja->kirim();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja berhasil dikirim untuk validasi.']);
    }
}