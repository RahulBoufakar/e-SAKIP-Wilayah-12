<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanAplikasi;
use App\Models\TemplateDokumen;
use App\Services\AppAssetImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PengaturanController extends Controller
{
    private const TABS = ['aplikasi', 'template', 'lainnya'];

    // GET /admin/pengaturan?tab=aplikasi|template|lainnya
    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), self::TABS, true) ? $request->get('tab') : 'aplikasi';

        $templateList = $tab === 'template' ? TemplateDokumen::orderBy('kode')->get() : collect();

        return view('admin.pengaturan.index', compact('tab', 'templateList'));
    }

    // PUT /admin/pengaturan/aplikasi
    public function updateAplikasi(Request $request, AppAssetImageService $imageService)
    {
        $data = $request->validate([
            'nama_aplikasi' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            // Sengaja pakai 'file', bukan 'image': rule `image` bawaan Laravel
            // tidak mengenali .ico sebagai gambar valid, jadi kombinasi
            // image+mimes:ico akan selalu gagal validasi.
            'favicon' => 'nullable|file|mimes:png,jpg,jpeg,ico,webp,svg|max:1024',
        ], [
            'nama_aplikasi.required' => 'Nama Aplikasi wajib diisi.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat PNG, JPG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'favicon.mimes' => 'Favicon harus berformat PNG, JPG, ICO, WEBP, atau SVG.',
            'favicon.max' => 'Ukuran favicon maksimal 1 MB.',
        ]);

        $pengaturan = PengaturanAplikasi::current();
        $update = ['nama_aplikasi' => $data['nama_aplikasi']];

        try {
            if ($request->hasFile('logo')) {
                $update['logo'] = $imageService->storeLogo($request->file('logo'));
            }

            if ($request->hasFile('favicon')) {
                $update['favicon'] = $imageService->storeFavicon($request->file('favicon'));
            }
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        // File lama baru dihapus SETELAH file baru berhasil diproses & disimpan,
        // supaya kalau pemrosesan gambar gagal (exception di atas), file lama
        // yang masih dipakai tidak ikut terhapus.
        if (isset($update['logo']) && $pengaturan->logo) {
            $imageService->delete($pengaturan->logo);
        }
        if (isset($update['favicon']) && $pengaturan->favicon) {
            $imageService->delete($pengaturan->favicon);
        }

        $pengaturan->update($update);

        Cache::forget(PengaturanAplikasi::CACHE_KEY);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Pengaturan Aplikasi berhasil disimpan.']);
    }

    // PUT /admin/pengaturan/template/{kode}
    public function updateTemplate(Request $request, string $kode)
    {
        $template = TemplateDokumen::where('kode', $kode)->firstOrFail();

        $data = $request->validate([
            'file' => "required|file|mimes:{$template->validationMimes()}|max:5120",
        ], [
            'file.required' => 'File template wajib diunggah.',
            'file.mimes' => "File harus berformat {$template->formatLabel()}.",
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        if ($template->file) {
            Storage::disk('public')->delete($template->file);
        }

        $template->update(['file' => $data['file']->store('template-dokumen', 'public')]);

        return back()->with('feedback', ['type' => 'success', 'message' => "{$template->nama} berhasil diperbarui."]);
    }

    // GET /admin/pengaturan/template/{kode}/preview — khusus jenis PDF
    public function previewTemplate(string $kode)
    {
        $template = TemplateDokumen::where('kode', $kode)->firstOrFail();
        abort_unless($template->isPdf(), 404);
        abort_unless($template->file && Storage::disk('public')->exists($template->file), 404);

        return response()->json([
            'mime' => 'application/pdf',
            'base64' => base64_encode(Storage::disk('public')->get($template->file)),
        ]);
    }

    // GET /admin/pengaturan/template/{kode}/unduh
    public function unduhTemplate(string $kode)
    {
        $template = TemplateDokumen::where('kode', $kode)->firstOrFail();
        abort_unless($template->file && Storage::disk('public')->exists($template->file), 404);

        return Storage::disk('public')->download($template->file, $template->nama);
    }
}