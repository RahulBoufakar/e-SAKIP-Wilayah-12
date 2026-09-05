<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanAplikasi;
use App\Models\TemplateDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
    public function updateAplikasi(Request $request)
    {
        $data = $request->validate([
            'nama_aplikasi' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:10240',
            'favicon' => 'nullable|mimes:png,ico|max:256',
        ], [
            'nama_aplikasi.required' => 'Nama Aplikasi wajib diisi.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat PNG, JPG, SVG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 10 MB.',
            'favicon.mimes' => 'Favicon harus berformat PNG atau ICO.',
            'favicon.max' => 'Ukuran favicon maksimal 256 KB.',
        ]);

        $pengaturan = PengaturanAplikasi::current();
        $update = ['nama_aplikasi' => $data['nama_aplikasi']];

        foreach (['logo', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                if ($pengaturan->$field) {
                    Storage::disk('public')->delete($pengaturan->$field);
                }
                $update[$field] = $request->file($field)->store('pengaturan', 'public');
            }
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