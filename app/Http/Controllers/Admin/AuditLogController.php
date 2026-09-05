<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    // GET /admin/audit-log
    public function index(Request $request)
    {
        $activities = Activity::with(['causer', 'subject'])
            ->where('log_name', 'audit_trail')
            ->when($request->filled('tim_kerja_id'), function ($q) use ($request) {
                $q->whereHasMorph('causer', [User::class], function ($q2) use ($request) {
                    $q2->whereHas('timKerja', fn ($q3) => $q3->where('tim_kerja.id', $request->tim_kerja_id));
                });
            })
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('causer_type', User::class)->where('causer_id', $request->user_id);
            })
            ->when($request->filled('tanggal_mulai'), fn ($q) => $q->whereDate('created_at', '>=', $request->tanggal_mulai))
            ->when($request->filled('tanggal_selesai'), fn ($q) => $q->whereDate('created_at', '<=', $request->tanggal_selesai))
            ->when($request->filled('search'), fn ($q) => $q->where('description', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $timKerjaOptions = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);
        $userOptions = User::orderBy('name')->get(['id', 'name']);

        return view('admin.audit-log.index', compact('activities', 'timKerjaOptions', 'userOptions'));
    }
}