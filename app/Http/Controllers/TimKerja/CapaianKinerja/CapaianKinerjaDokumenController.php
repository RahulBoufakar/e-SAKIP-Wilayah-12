<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\CapaianKinerjaDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapaianKinerjaDokumenController extends Controller
{
    use ResolvesTimKerjaSession;

    // POST /tim-kerja/capaian-kinerja/{capaianKinerja}/dokumen
    public function store(Request $request, CapaianKinerja $capaianKinerja)
    {
        $this->authorizeAkses($capaianKinerja);
        abort_if($capaianKinerja->isFieldLocked(), 403, 'Data ini sedang terkunci dan tidak dapat diubah.');

        $data = $request->validate([
            'dokumen' => 'required|array|min:1',
            'dokumen.*.nama_dokumen' => 'required|string|max:255',
            'dokumen.*.file' => 'required|file|mimes:pdf|max:5120',
        ], [
            'dokumen.*.nama_dokumen.required' => 'Nama dokumen wajib diisi.',
            'dokumen.*.file.required' => 'File dokumen wajib diunggah.',
            'dokumen.*.file.mimes' => 'File dokumen harus berformat PDF.',
            'dokumen.*.file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        foreach ($data['dokumen'] as $item) {
            $capaianKinerja->dokumen()->create([
                'nama_dokumen' => $item['nama_dokumen'],
                'file_dokumen' => $item['file']->store('capaian-kinerja', 'public'),
            ]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Dokumen berhasil ditambahkan.']);
    }

    // DELETE /tim-kerja/capaian-kinerja/dokumen/{dokumen}
    public function destroy(CapaianKinerjaDokumen $dokumen)
    {
        $this->authorizeAkses($dokumen->capaianKinerja);
        abort_if($dokumen->capaianKinerja->isFieldLocked(), 403, 'Data ini sedang terkunci dan tidak dapat diubah.');

        Storage::disk('public')->delete($dokumen->file_dokumen);
        $dokumen->delete();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
    }

    // GET /tim-kerja/capaian-kinerja/dokumen/{dokumen}/preview
    public function preview(CapaianKinerjaDokumen $dokumen)
    {
        $this->authorizeAkses($dokumen->capaianKinerja);

        return response()->json([
            'mime' => 'application/pdf',
            'base64' => base64_encode(Storage::disk('public')->get($dokumen->file_dokumen)),
        ]);
    }

    // GET /tim-kerja/capaian-kinerja/dokumen/{dokumen}/unduh
    public function unduh(CapaianKinerjaDokumen $dokumen): StreamedResponse
    {
        $this->authorizeAkses($dokumen->capaianKinerja);

        return Storage::disk('public')->download($dokumen->file_dokumen, $dokumen->nama_dokumen.'.pdf');
    }

    private function authorizeAkses(CapaianKinerja $capaianKinerja): void
    {
        abort_unless($this->activeTimKerjaIds()->contains($capaianKinerja->iku->tim_kerja_id), 403);
    }
}