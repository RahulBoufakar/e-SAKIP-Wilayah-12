<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DetailKegiatanController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    // PUT /tim-kerja/usulan-program-kerja/{usulanProgramKerja}/detail
    public function storeOrUpdate(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorize('update', $usulanProgramKerja);

        if ($usulanProgramKerja->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Usulan ini sedang terkunci dan tidak dapat diubah.']);
        }

        // Rentang tanggal harus di dalam tahun usulan, supaya bulan kegiatannya (1-12) jelas.
        $awalTahun = "{$usulanProgramKerja->tahun}-01-01";
        $akhirTahun = "{$usulanProgramKerja->tahun}-12-31";

        $data = $request->validate([
            'nama_detail' => 'required|string|max:255',
            'tempat_pelaksanaan' => 'required|string|max:255',
            'bentuk_kegiatan' => 'required|in:Luring,Daring,Hybrid',
            'tanggal_mulai' => "required|date_format:Y-m-d|after_or_equal:{$awalTahun}|before_or_equal:{$akhirTahun}",
            'tanggal_selesai' => "required|date_format:Y-m-d|after_or_equal:tanggal_mulai|before_or_equal:{$akhirTahun}",
            'anggaran' => 'required|numeric|min:0',
        ], [
            'nama_detail.required' => 'Nama Detail Kegiatan wajib diisi.',
            'tempat_pelaksanaan.required' => 'Tempat Pelaksanaan wajib diisi.',
            'bentuk_kegiatan.required' => 'Bentuk Kegiatan wajib diisi.',
            'bentuk_kegiatan.in' => 'Bentuk Kegiatan harus Luring, Daring, atau Hybrid.',
            'tanggal_mulai.required' => 'Tanggal Mulai wajib diisi.',
            'tanggal_mulai.after_or_equal' => "Tanggal Mulai harus di tahun {$usulanProgramKerja->tahun}.",
            'tanggal_mulai.before_or_equal' => "Tanggal Mulai harus di tahun {$usulanProgramKerja->tahun}.",
            'tanggal_selesai.required' => 'Tanggal Selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal Selesai tidak boleh sebelum Tanggal Mulai.',
            'tanggal_selesai.before_or_equal' => "Tanggal Selesai harus di tahun {$usulanProgramKerja->tahun}.",
            'tanggal_mulai.date_format' => 'Format Tanggal Mulai tidak valid.',
            'tanggal_selesai.date_format' => 'Format Tanggal Selesai tidak valid.',
            'anggaran.required' => 'Anggaran wajib diisi.',
            'anggaran.numeric' => 'Anggaran harus berupa angka.',
            'anggaran.min' => 'Anggaran tidak boleh negatif.',
        ]);

        $detail = $usulanProgramKerja->detailKegiatan()->updateOrCreate([], $data);

        event(new ActivityOccurred(
            subject: $detail,
            description: "menyimpan Detail Kegiatan untuk Usulan Program Kerja \"{$usulanProgramKerja->nama_usulan}\"",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Detail Kegiatan berhasil disimpan.']);
    }
}