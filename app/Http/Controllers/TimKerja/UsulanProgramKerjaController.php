<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
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

        $tab = $request->get('tahun') === 'h_plus_1' ? 'h_plus_1' : 'berjalan';

        if ($tab === 'h_plus_1' && ! $this->nextTahunAnggaranExists($tahunAnggaranId)) {
            return view('admin.layout.app-error-content', [
                'errorMessage' => 'Tahun belum tersedia, silahkan hubungi admin untuk ditambahkan.',
                'layout' => 'tim-kerja.layout.app',
                'backRoute' => 'tim-kerja.dashboard',
            ]);
        }

        $usulanList = UsulanProgramKerja::with(['iku.timKerja'])
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('tahun', $tab)
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $ikuOptions = Iku::whereIn('tim_kerja_id', $timKerjaIds)
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->orderBy('kode')
            ->get(['id', 'kode', 'deskripsi']);

        $formLocked = ! $this->anyTriwulanAktif($tahunAnggaranId);

        return view('tim-kerja.usulan-program-kerja.index', compact('usulanList', 'ikuOptions', 'formLocked', 'tab'));
    }

    // POST /tim-kerja/usulan-program-kerja
    public function store(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $timKerjaIds = $this->activeTimKerjaIds();

        if (! $tahunAnggaranId || $timKerjaIds->isEmpty() || ! $this->anyTriwulanAktif($tahunAnggaranId)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Form terkunci: tidak ada Triwulan aktif saat ini.']);
        }

        $data = $request->validate([
            'iku_id' => [
                'required',
                Rule::exists('iku', 'id')->where(fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds)),
            ],
            'nama_kegiatan' => 'required|string|max:255',
            'tahun' => 'required|in:berjalan,h_plus_1',
            'permasalahan' => 'nullable|string',
        ], [
            'iku_id.required' => 'IKU wajib dipilih.',
            'iku_id.exists' => 'IKU tidak valid atau bukan milik Tim Kerja Anda.',
            'nama_kegiatan.required' => 'Nama Kegiatan wajib diisi.',
            'tahun.required' => 'Tahun wajib dipilih.',
            'tahun.in' => 'Tahun tidak valid.',
        ]);

        if ($data['tahun'] === 'h_plus_1' && ! $this->nextTahunAnggaranExists($tahunAnggaranId)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Tahun belum tersedia, silahkan hubungi admin untuk ditambahkan.']);
        }

        $data['tahun_anggaran_id'] = $tahunAnggaranId;

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

        $formLocked = ! $this->anyTriwulanAktif($usulanProgramKerja->tahun_anggaran_id);
        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return view('tim-kerja.usulan-program-kerja.show', [
            'usulan' => $usulanProgramKerja,
            'formLocked' => $formLocked,
            'bulanIndo' => $bulanIndo,
        ]);
    }

    // PUT /tim-kerja/usulan-program-kerja/{usulanProgramKerja}
    public function update(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorizeAksesUsulan($usulanProgramKerja);

        if ($usulanProgramKerja->isFieldLocked() || ! $this->anyTriwulanAktif($usulanProgramKerja->tahun_anggaran_id)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat diubah.']);
        }

        $data = $request->validate([
            'nama_kegiatan' => 'required|string|max:255',
            'permasalahan' => 'nullable|string',
            'file_kak_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'file_rab_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'file_rab_excel' => 'nullable|file|mimes:xls,xlsx|max:10240',
        ], [
            'nama_kegiatan.required' => 'Nama Kegiatan wajib diisi.',
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

        if (! $this->anyTriwulanAktif($usulanProgramKerja->tahun_anggaran_id)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Form terkunci: tidak ada Triwulan aktif saat ini.']);
        }

        if (! $usulanProgramKerja->can_kirim) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Lengkapi file KAK, RAB PDF, RAB Excel, dan Detail Kegiatan sebelum mengirim.']);
        }

        $usulanProgramKerja->kirim();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja berhasil dikirim untuk validasi.']);
    }
}