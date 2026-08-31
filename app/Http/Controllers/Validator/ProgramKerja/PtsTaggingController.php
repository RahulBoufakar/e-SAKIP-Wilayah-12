<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Controller;
use App\Models\Pts;
use Illuminate\Http\Request;

class PtsTaggingController extends Controller
{
    // GET /validator/pts-tagging
    public function index(Request $request)
    {
        $ptsList = Pts::withCount('usulanProgramKerja')
            ->with(['usulanProgramKerja' => fn ($q) => $q->with(['iku.timKerja', 'programKerja'])])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('nama_pts', 'like', '%'.$request->search.'%')
                    ->orWhere('kode_pts', 'like', '%'.$request->search.'%');
            })
            ->orderByDesc('usulan_program_kerja_count')
            ->paginate(15)
            ->withQueryString();

        return view('validator.program-kerja.pts-tagging.index', compact('ptsList'));
    }
}