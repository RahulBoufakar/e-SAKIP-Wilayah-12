<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Controller;
use App\Models\Pts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PtsController extends Controller
{
    use HandlesRestrictedDeletes;
    // GET /admin/master-data/pts
    public function index(Request $request)
    {
        $this->authorize('viewAny', Pts::class);

        $ptsList = Pts::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('nama_pts', 'like', '%'.$request->search.'%')
                        ->orWhere('kode_pts', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('status_pts') || $request->filled('status'), function ($q) use ($request) {
                $val = $request->status_pts ?? $request->status;
                $q->where('status_pts', $val);
            })
            ->when($request->filled('akreditasi_pts') || $request->filled('akreditasi'), function ($q) use ($request) {
                $val = $request->akreditasi_pts ?? $request->akreditasi;
                $q->where('akreditasi_pts', $val);
            })
            ->when($request->filled('sumber_data') || $request->filled('sumber'), function ($q) use ($request) {
                $val = $request->sumber_data ?? $request->sumber;
                $q->where('sumber_data', $val);
            })
            ->when($request->filled('provinsi'), function ($q) use ($request) {
                $q->where('provinsi', $request->provinsi);
            })
            ->orderBy('kode_pts')
            ->paginate(15)
            ->withQueryString();

        $provinsiOptions = Pts::whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->distinct()
            ->orderBy('provinsi')
            ->pluck('provinsi');

        if ($provinsiOptions->isEmpty()) {
            $provinsiOptions = collect(['Maluku', 'Maluku Utara']);
        }

        return view('admin.master-data.pts.index', compact('ptsList', 'provinsiOptions'));
    }

    // POST /admin/master-data/pts
    public function store(Request $request)
    {
        $this->authorize('create', Pts::class);

        $data = $this->validated($request);
        $pts = Pts::create($data);

        // AUDIT § A4: sebelumnya mutasi Master Data ini belum tercatat sama sekali.
        event(new ActivityOccurred(
            subject: $pts,
            description: "menambahkan data PTS \"{$pts->nama_pts}\" ({$pts->kode_pts})",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data PTS berhasil ditambahkan.']);
    }

    // PUT /admin/master-data/pts/{pts}
    public function update(Request $request, Pts $pts)
    {
        $this->authorize('update', $pts);

        $data = $this->validated($request, $pts);

        // TDD Sinkronisasi PTS §8: setiap edit Admin mengubah sumber_data menjadi manual
        // (sinkronisasi berikutnya akan menimpanya kembali — "API selalu menang").
        $sumberLama = $pts->sumber_data;
        $pts->update($data + ['sumber_data' => 'manual']);

        $deskripsi = "memperbarui data PTS \"{$pts->nama_pts}\" ({$pts->kode_pts})";
        if ($sumberLama !== 'manual') {
            $deskripsi .= " (sumber: {$sumberLama} ke manual)";
        }

        event(new ActivityOccurred(
            subject: $pts,
            description: $deskripsi,
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data PTS berhasil diperbarui.']);
    }

    // DELETE /admin/master-data/pts/{pts}
    public function destroy(Pts $pts)
    {
        $this->authorize('delete', $pts);

        // AUDIT § A4: snapshot sebelum delete.
        $namaPts = $pts->nama_pts;
        $kodePts = $pts->kode_pts;

        return $this->deleteOrBlock(
            function () use ($pts, $namaPts, $kodePts) {
                $pts->delete();

                event(new ActivityOccurred(
                    subject: $pts,
                    description: "menghapus data PTS \"{$namaPts}\" ({$kodePts})",
                    causer: Auth::user(),
                ));
            },
            'Data PTS ini masih ditagging pada Program Kerja, tidak dapat dihapus.'
        );
    }

    private function validated(Request $request, ?Pts $pts = null): array
    {
        return $request->validate([
            'kode_pts' => ['required', 'string', 'max:20', Rule::unique('pts', 'kode_pts')->ignore($pts?->id)],
            'nama_pts' => 'required|string|max:255',
            'provinsi' => 'nullable|string|max:100',
            'status_pts' => 'nullable|in:aktif,non_aktif,alih_bentuk,tutup,alih_kelola,pembinaan',
            'akreditasi_pts' => 'nullable|in:unggul,terakreditasi,tidak_terakreditasi',
        ], [
            'kode_pts.required' => 'Kode PTS wajib diisi.',
            'kode_pts.max' => 'Kode PTS maksimal 20 karakter.',
            'kode_pts.unique' => 'Kode PTS sudah digunakan.',
            'nama_pts.required' => 'Nama PTS wajib diisi.',
            'nama_pts.max' => 'Nama PTS maksimal 255 karakter.',
            'provinsi.max' => 'Provinsi maksimal 100 karakter.',
            'status_pts.in' => 'Status PTS tidak valid.',
            'akreditasi_pts.in' => 'Akreditasi PTS tidak valid.',
        ]);
    }
}
