<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

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

    // GET /tim-kerja/usulan-program-kerja?tahun=berjalan|h_plus_1
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

        $activeTahun = (int) TahunAnggaran::find($tahunAnggaranId)->tahun;
        $nextYear = $activeTahun + 1;
        $nextYearAvailable = $this->nextTahunAnggaranExists($tahunAnggaranId);

        $tab = $request->get('tahun') === 'h_plus_1' && $nextYearAvailable ? 'h_plus_1' : 'berjalan';
        $tahun = $tab === 'h_plus_1' ? $nextYear : $activeTahun;

        // IKU untuk modal "Tambah" — dibatasi ke tahun yang sedang aktif di tab ini,
        // karena tahun Usulan Program Kerja mengikuti tahun IKU yang dipilih (lihat store()).
        $ikuOptions = Iku::with('sasaranKegiatan.tahunAnggaran')
            ->whereIn('tim_kerja_id', $timKerjaIds)
            ->whereHas('sasaranKegiatan.tahunAnggaran', fn ($q) => $q->where('tahun', $tahun))
            ->orderBy('kode')
            ->get(['id', 'kode', 'deskripsi', 'sasaran_kegiatan_id']);

        $usulanList = UsulanProgramKerja::with(['iku.timKerja'])
            ->where('tahun', $tahun)
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('tim-kerja.program-kerja.usulan-program-kerja.index', compact(
            'usulanList', 'ikuOptions', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable'
        ));
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
            'permasalahan' => 'nullable|string',
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
    public function show(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorize('view', $usulanProgramKerja);

        $usulanProgramKerja->load(['iku', 'detailKegiatan']);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $activeTahun = $tahunAnggaranId ? (int) TahunAnggaran::find($tahunAnggaranId)->tahun : null;
        $tab = $activeTahun !== null && $usulanProgramKerja->tahun == $activeTahun ? 'berjalan' : 'h_plus_1';

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return view('tim-kerja.program-kerja.usulan-program-kerja.show', [
            'usulan' => $usulanProgramKerja,
            'bulanIndo' => $bulanIndo,
            'tab' => $tab,
        ]);
    }

    // PUT /tim-kerja/usulan-program-kerja/{usulanProgramKerja}
    public function update(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorize('update', $usulanProgramKerja);

        if ($usulanProgramKerja->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Usulan ini sedang terkunci dan tidak dapat diubah.']);
        }

        $data = $request->validate([
            'nama_usulan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'permasalahan' => 'nullable|string',
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
        $this->authorize('update', $usulanProgramKerja);

        if (! $usulanProgramKerja->can_kirim) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Lengkapi file KAK, RAB PDF, RAB Excel, dan Detail Kegiatan sebelum mengirim.']);
        }

        $usulanProgramKerja->kirim();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja berhasil dikirim untuk validasi.']);
    }
}