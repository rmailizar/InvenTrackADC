<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

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

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Count pending users for badge
        $pendingUsersCount = User::where('account_status', 'pending')->count();

        return view('users.index', compact('users', 'pendingUsersCount'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,manager,staff',
            'no_hp' => 'nullable|string|max:20',
        ]);

        // Admin creates user with pending status, needs manager approval
        $validated['account_status'] = 'pending';

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan. Menunggu approval dari Manager.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,manager,staff',
            'no_hp' => 'nullable|string|max:20',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $validated['password'] = $request->password;
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
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

        if ($user->account_status !== 'pending') {
            return back()->with('error', 'User sudah diproses sebelumnya.');
        }

        $user->update(['account_status' => 'rejected']);

        return back()->with('success', "Akun {$user->name} berhasil di-reject.");
    }
}
