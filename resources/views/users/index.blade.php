@extends('layouts.app')

@section('title', auth()->user()->isManager() ? 'Approval User' : 'Manajemen User')
@section('subtitle', auth()->user()->isManager() ? 'Setujui atau tolak akun pengguna' : 'Kelola akun pengguna sistem')

@section('content')
<div class="animate-fade-in">
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="{{ auth()->user()->isManager() ? route('pendingUsers.index') : route('users.index') }}">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label">Cari User</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama atau email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">Semua Role</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ request('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="col-md-2">
                    <label class="form-label">Status Akun</label>
                    <select name="account_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('account_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('account_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('account_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                @endif
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ auth()->user()->isManager() ? route('pendingUsers.index') : route('users.index') }}" class="btn btn-outline-secondary flex-fill"><i class="bi bi-x-lg"></i> Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if(auth()->user()->isAdmin())
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill"></i> Tambah User
        </a>
    </div>
    @endif

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="users-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>No HP</th>
                            <th>Status Akun</th>
                            <th>Terdaftar</th>
                            <th style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $users->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:11px;flex-shrink:0;">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <span class="fw-600">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    $roleColors = ['admin' => 'primary', 'manager' => 'warning', 'staff' => 'success'];
                                @endphp
                                <span class="badge bg-{{ $roleColors[$user->role] ?? 'secondary' }}" style="font-size:11px; padding:4px 10px; border-radius:20px;">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td>{{ $user->no_hp ?? '-' }}</td>
                            <td>
                                <span class="badge-status badge-{{ $user->account_status }}">{{ ucfirst($user->account_status) }}</span>
                            </td>
                            <td style="font-size:12px; color:var(--text-secondary);">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="action-buttons">
                                    {{-- Manager: Approve/Reject user account --}}
                                    @if(auth()->user()->isManager() && $user->account_status === 'pending')
                                        <form action="{{ route('users.approveAccount', $user->id) }}" method="POST" style="display:inline;" id="approveUser-{{ $user->id }}">
                                            @csrf
                                            <button type="button" class="btn-action approve" title="Approve" onclick="swalConfirm('Approve User', 'Approve akun user ini?', 'question', 'Ya, Approve', '#approveUser-{{ $user->id }}')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('users.rejectAccount', $user->id) }}" method="POST" style="display:inline;" id="rejectUser-{{ $user->id }}">
                                            @csrf
                                            <button type="button" class="btn-action reject" title="Reject" onclick="swalConfirm('Reject User', 'Reject akun user ini?', 'warning', 'Ya, Reject', '#rejectUser-{{ $user->id }}')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Admin: Edit/Delete --}}
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('users.edit', $user) }}" class="btn-action edit" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        @if($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" id="deleteUser-{{ $user->id }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn-action delete" title="Hapus" onclick="swalConfirm('Hapus User', 'Yakin hapus user ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteUser-{{ $user->id }}')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="no-data-row">
                            <td colspan="8">
                                <i class="bi bi-people" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                {{ auth()->user()->isManager() ? 'Tidak ada user menunggu approval' : 'Belum ada data user' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($users->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $users->links('pagination.custom') }}
    </div>
    @endif
</div>
@endsection
