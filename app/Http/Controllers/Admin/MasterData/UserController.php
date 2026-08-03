<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\TimKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET /admin/master-data/user (FR-M3)
    public function index(Request $request)
    {
        $users = User::with('timKerja')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $timKerjaList = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);

        return view('admin.master-data.user.index', compact('users', 'timKerjaList'));
    }

    // POST /admin/master-data/user (FR-M3/FR-M4)
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // cast 'hashed' -> auto di-hash saat disimpan
            'role' => $data['role'],
        ]);

        $user->timKerja()->sync($data['role'] === 'tim_kerja' ? $data['tim_kerja_id'] : []);

        return back()->with('feedback', ['type' => 'success', 'message' => 'User berhasil ditambahkan.']);
    }

    // PUT /admin/master-data/user/{id} (FR-M4: password opsional saat edit)
    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];
        if (! empty($data['password'])) {
            $user->password = $data['password']; // kosong = tidak diubah
        }
        $user->save();

        $user->timKerja()->sync($data['role'] === 'tim_kerja' ? $data['tim_kerja_id'] : []);

        return back()->with('feedback', ['type' => 'success', 'message' => 'User berhasil diperbarui.']);
    }

    // DELETE /admin/master-data/user/{id} (FR-M3)
    public function destroy(User $user)
    {
        $user->timKerja()->detach();
        $user->delete();

        return back()->with('feedback', ['type' => 'success', 'message' => 'User berhasil dihapus.']);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $isUpdate = $user !== null;
        return $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => 'required|in:administrator,tim_kerja,validator',
            // ponytail-flag: tim_kerja_id kini array (many-to-many via user_tim_kerja,
            // lihat jawaban Open Question #2) — bukan dropdown tunggal seperti di Desain Sistem §5.4.
            'tim_kerja_id' => 'required_if:role,tim_kerja|array',
            'tim_kerja_id.*' => 'exists:tim_kerja,id',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 150 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'tim_kerja_id.required_if' => 'Tim Kerja wajib dipilih untuk role Tim Kerja.',
            'tim_kerja_id.*.exists' => 'Tim Kerja yang dipilih tidak valid.',
        ]);
    }
}
