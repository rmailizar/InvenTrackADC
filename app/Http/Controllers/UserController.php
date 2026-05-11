<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->visibleFor(auth()->user());

        // Manager approval page should only show pending users by default
        if (auth()->check() && auth()->user()->isManager()) {
            $query->where('account_status', 'pending');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if (auth()->user()->isSuperAdmin() && $request->filled('bidang')) {
            $query->where('bidang', $request->bidang);
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Count pending users for badge
        $pendingUsersCount = User::visibleFor(auth()->user())->where('account_status', 'pending')->count();

        return view('users.index', compact('users', 'pendingUsersCount'));
    }

    public function create()
    {
        return redirect()->route('users.index');
    }

    /**
     * Return user data as JSON for modal edit pre-fill
     */
    public function show(User $user)
    {
        $this->authorizeUserDepartment($user);

        return response()->json($user->only(['id', 'name', 'email', 'role', 'bidang', 'no_hp']));
    }

    public function store(Request $request)
    {
        $actor = auth()->user();
        $requestedBidang = $actor->isSuperAdmin()
            ? $request->input('bidang')
            : $actor->bidang;
        $allowedRoles = $this->allowedRolesForBidang($requestedBidang, $actor->isSuperAdmin());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', Rule::in($allowedRoles)],
            'bidang' => $actor->isSuperAdmin() ? 'nullable|required_unless:role,superadmin|in:teknik,umum' : 'nullable|in:teknik,umum',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $validated['bidang'] = $actor->isSuperAdmin()
            ? ($validated['role'] === 'superadmin' ? null : $validated['bidang'])
            : $actor->bidang;

        $this->ensureRoleAllowedForBidang($validated['role'], $validated['bidang']);

        $validated['account_status'] = ($actor->isSuperAdmin() || $actor->isTeknik()) ? 'approved' : 'pending';

        User::create($validated);

        $successMsg = $validated['account_status'] === 'approved'
            ? 'User berhasil ditambahkan dan langsung aktif.'
            : 'User berhasil ditambahkan. Menunggu approval dari Manager.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('users.index')->with('success', $successMsg);
    }

    public function edit(User $user, Request $request)
    {
        $this->authorizeUserDepartment($user);

        // AJAX request returns JSON data for modal pre-fill
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($user->only(['id', 'name', 'email', 'role', 'bidang', 'no_hp']));
        }

        return redirect()->route('users.index');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserDepartment($user);
        $actor = auth()->user();
        $requestedBidang = $actor->isSuperAdmin()
            ? $request->input('bidang')
            : $actor->bidang;
        $allowedRoles = $this->allowedRolesForBidang($requestedBidang, $actor->isSuperAdmin());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => ['required', Rule::in($allowedRoles)],
            'bidang' => $actor->isSuperAdmin() ? 'nullable|required_unless:role,superadmin|in:teknik,umum' : 'nullable|in:teknik,umum',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $validated['bidang'] = $actor->isSuperAdmin()
            ? ($validated['role'] === 'superadmin' ? null : $validated['bidang'])
            : $actor->bidang;

        $this->ensureRoleAllowedForBidang($validated['role'], $validated['bidang']);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $validated['password'] = $request->password;
        }

        $user->update($validated);

        $successMsg = 'User berhasil diupdate.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('users.index')->with('success', $successMsg);
    }

    public function destroy(User $user)
    {
        $this->authorizeUserDepartment($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->transactions()->count() > 0) {
            return back()->with('error', 'Tidak bisa menghapus user yang memiliki transaksi.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Approve user account (Manager only)
     */
    public function approveUser($id)
    {
        $user = User::findOrFail($id);
        $this->authorizeUserDepartment($user);

        if ($user->account_status !== 'pending') {
            return back()->with('error', 'User sudah diproses sebelumnya.');
        }

        $user->update(['account_status' => 'approved']);

        return back()->with('success', "Akun {$user->name} berhasil di-approve.");
    }

    /**
     * Reject user account (Manager only)
     */
    public function rejectUser($id)
    {
        $user = User::findOrFail($id);
        $this->authorizeUserDepartment($user);

        if ($user->account_status !== 'pending') {
            return back()->with('error', 'User sudah diproses sebelumnya.');
        }

        $user->update(['account_status' => 'rejected']);

        return back()->with('success', "Akun {$user->name} berhasil di-reject.");
    }

    private function authorizeUserDepartment(User $user): void
    {
        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        abort_unless($user->bidang === auth()->user()->bidang, 403, 'Anda tidak memiliki akses ke user bidang ini.');
    }

    private function allowedRolesForBidang(?string $bidang, bool $allowSuperadmin = false): array
    {
        if ($allowSuperadmin && $bidang === null) {
            return ['superadmin', 'admin', 'manajer', 'staf'];
        }

        $roles = $bidang === 'teknik'
            ? ['admin', 'manajer']
            : ['admin', 'manajer', 'staf'];

        return $allowSuperadmin ? array_merge(['superadmin'], $roles) : $roles;
    }

    private function ensureRoleAllowedForBidang(string $role, ?string $bidang): void
    {
        if ($bidang === 'teknik' && $role === 'staf') {
            throw ValidationException::withMessages([
                'role' => 'Bidang Teknik hanya boleh memiliki role Admin atau Manajer.',
            ]);
        }
    }
}
