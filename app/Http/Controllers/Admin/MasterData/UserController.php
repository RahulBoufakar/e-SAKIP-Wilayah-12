<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Models\TimKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET /admin/master-data/user (FR-M3)
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('timKerja', 'roles')
                // ->withoutRole('admin')
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where(function ($subQuery) use ($request) {
                        $subQuery->where('name', 'like', '%' . $request->search . '%')
                                ->orWhere('email', 'like', '%' . $request->search . '%');
                    });
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();

        $timKerjaList = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);

        return view('admin.master-data.user.index', compact('users', 'timKerjaList'));
    }

    // POST /admin/master-data/user (FR-M3/FR-M4)
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $this->validated($request);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->syncRoles([$data['role']]);
        $user->timKerja()->sync($data['role'] === 'tim_kerja' ? [$data['tim_kerja_id']] : []);

        event(new ActivityOccurred(
            subject: $user,
            description: "membuat user baru \"{$user->name}\" dengan role {$data['role']}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'User berhasil ditambahkan.']);
    }

    // PUT /admin/master-data/user/{id} (FR-M4: password opsional saat edit)
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $this->validated($request, $user);

        $roleSebelum = $user->getRoleNames()->first();
        $timSebelum = $user->timKerja->pluck('nama_tim')->join(', ') ?: '-';
        $emailSebelum = $user->email; // AUDIT § A4: dibutuhkan untuk deteksi perubahan kredensial
        $passwordDiganti = ! empty($data['password']); // AUDIT § A4

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->syncRoles([$data['role']]);
        $user->timKerja()->sync(
            $data['role'] === 'tim_kerja' ? [$data['tim_kerja_id']] : []
        );

        // reload relasi setelah sync(), supaya perbandingan "sesudah" akurat (bukan cache lama)
        $user->load('timKerja');
        $timSesudah = $user->timKerja->pluck('nama_tim')->join(', ') ?: '-';
        $emailBerubah = $emailSebelum !== $data['email']; // AUDIT § A4

        $perubahan = [];
        if ($roleSebelum !== $data['role']) {
            $perubahan[] = "role: {$roleSebelum} → {$data['role']}";
        }
        if ($timSebelum !== $timSesudah) {
            $perubahan[] = "tim kerja: {$timSebelum} → {$timSesudah}";
        }
        // AUDIT § A4: email & reset password WAJIB selalu tercatat, meski
        // role/tim tidak berubah — sebelumnya perubahan ini lolos tanpa log.
        if ($emailBerubah) {
            $perubahan[] = "email: {$emailSebelum} → {$data['email']}";
        }
        if ($passwordDiganti) {
            $perubahan[] = 'password direset';
        }

        if (! empty($perubahan)) {
            event(new ActivityOccurred(
                subject: $user,
                description: "mengubah akses/kredensial user \"{$user->name}\" (".implode('; ', $perubahan).')',
                causer: Auth::user(),
                recipients: collect([$user]), // beri tahu user yang bersangkutan
                properties: [
                    'role_sebelum' => $roleSebelum,
                    'role_sesudah' => $data['role'],
                    'tim_sebelum' => $timSebelum,
                    'tim_sesudah' => $timSesudah,
                    'email_berubah' => $emailBerubah,
                    'password_direset' => $passwordDiganti,
                ],
            ));
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'User berhasil diperbarui.']);
    }

    // DELETE /admin/master-data/user/{id} (FR-M3)
    public function destroy(User $user)
    {
       if (Auth::user()->cannot('delete', $user)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'User Admin tidak dapat dihapus']);
        }

        // AUDIT § A4: snapshot sebelum delete — subject akan hilang dari DB setelahnya.
        $namaSebelum = $user->name;
        $emailSebelum = $user->email;
        $roleSebelum = $user->getRoleNames()->first();

        $user->timKerja()->detach();
        $user->syncRoles([]);
        $user->delete();

        event(new ActivityOccurred(
            subject: $user,
            description: "menghapus user \"{$namaSebelum}\" ({$emailSebelum}), role sebelumnya: ".($roleSebelum ?? '-'),
            causer: Auth::user(),
            properties: [
                'nama' => $namaSebelum,
                'email' => $emailSebelum,
                'role_sebelum' => $roleSebelum,
            ],
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'User berhasil dihapus.']);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $isUpdate = $user !== null;
        return $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => 'required|in:admin,tim_kerja,validator',
            'tim_kerja_id' => 'required_if:role,tim_kerja',
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
