<?php

namespace App\Http\Controllers\Auth;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\TahunAnggaran;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $tahunDepan = now()->year + 1;
        return view('auth.login', [
            'tahunList' => TahunAnggaran::where('tahun', '!=', $tahunDepan)->orderByDesc('tahun')->get(['id', 'tahun']),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        
        $request->validate([
            'tahun_anggaran_id' => 'nullable|exists:tahun_anggaran,id',
        ], [
            'tahun_anggaran_id.exists' => 'Tahun anggaran yang dipilih tidak valid.',
        ]);

        $request->authenticate();

        $request->session()->regenerate();

        // Halaman login sementara ini memuat pilihan Tahun Anggaran (lihat
        // ContextController untuk mekanisme yang sama, dipakai lagi di sini
        // supaya user langsung landing di TA yang dipilih).
        if ($request->filled('tahun_anggaran_id')) {
            $request->session()->put('tahun_anggaran_id', $request->integer('tahun_anggaran_id'));
        }

        event(new ActivityOccurred(
            subject: $request->user(),
            description: 'login ke sistem',
            causer: $request->user(),
            // tanpa recipients — ini murni audit log, tidak perlu notifikasi ke siapapun
        ));

        return redirect()->intended(
            route(RouteServiceProvider::homeRouteFor($request->user()))
        );
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user(); // ambil dulu SEBELUM logout, karena setelah logout Auth::user() jadi null

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            event(new ActivityOccurred(
                subject: $user,
                description: 'logout dari sistem',
                causer: $user,
            ));
        }

        return redirect('/');
    }
}
