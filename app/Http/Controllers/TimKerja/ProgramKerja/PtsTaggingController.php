<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PtsTaggingController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    // PUT /tim-kerja/data-proker/{usulanProgramKerja}/tag-pts
    public function storeOrUpdate(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorize('update', $usulanProgramKerja);

        abort_unless($usulanProgramKerja->detailKegiatan?->jenis_kegiatan === 'kunjungan_lapangan', 403);

        $data = $request->validate([
            'pts_id' => 'nullable|array',
            'pts_id.*' => 'integer|exists:pts,id',
        ]);

        $usulanProgramKerja->pts()->sync($data['pts_id'] ?? []);

        $jumlahPts = count($data['pts_id'] ?? []);

        if ($jumlahPts > 0) {
            event(new ActivityOccurred(
                subject: $usulanProgramKerja,
                description: "menagging {$jumlahPts} PTS untuk proker \"{$usulanProgramKerja->nama_usulan}\"",
                causer: Auth::user(),
                recipients: User::role('validator')->get(),
                url: route('validator.data-proker.index').'#proker-'.$usulanProgramKerja->id,
            ));
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Tagging PTS berhasil disimpan.']);
    }
}