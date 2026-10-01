<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Http\Controllers\Controller;
use App\Models\ProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatusKegiatanController extends Controller
{
    // PUT /tim-kerja/data-proker/{programKerja}/status
    public function update(Request $request, ProgramKerja $programKerja)
    {
        $this->authorize('updateStatus', $programKerja);

        $data = $request->validate([
            'status_kegiatan' => ['required', Rule::in(ProgramKerja::STATUS_KEGIATAN)],
        ]);

        $programKerja->update($data);

        return back()->with('feedback', ['type' => 'success', 'message' => "Status kegiatan diubah menjadi \"{$data['status_kegiatan']}\"."]);
    }
}
