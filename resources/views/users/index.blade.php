@extends('layouts.app')

@section('title', auth()->user()->isManager() ? 'Approval User' : 'Manajemen User')
@section('subtitle', auth()->user()->isManager() ? 'Setujui atau tolak akun pengguna' : 'Kelola akun pengguna sistem')

@section('content')
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET"
                action="{{ auth()->user()->isManager() ? route('pendingUsers.index') : route('users.index') }}">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cari User</label>
                        <input type="text" name="search" class="form-control" placeholder="Nama atau email..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">Semua Role</option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="manajer" {{ request('role') == 'manajer' ? 'selected' : '' }}>Manajer</option>
                            <option value="staf" {{ request('role') == 'staf' ? 'selected' : '' }}>Staf</option>
                        </select>
                    </div>
                    @if(auth()->user()->isAdmin())
                        <div class="col-md-2">
                            <label class="form-label">Status Akun</label>
                            <select name="account_status" class="form-select">
                                <option value="">Semua</option>
                                <option value="pending" {{ request('account_status') == 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="approved" {{ request('account_status') == 'approved' ? 'selected' : '' }}>Approved
                                </option>
                                <option value="rejected" {{ request('account_status') == 'rejected' ? 'selected' : '' }}>Rejected
                                </option>
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i>
                                Filter</button>
                            <a href="{{ auth()->user()->isManager() ? route('pendingUsers.index') : route('users.index') }}"
                                class="btn btn-outline-secondary flex-fill"><i class="bi bi-x-lg"></i> Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-primary" onclick="openUserModal()">
                    <i class="bi bi-person-plus-fill"></i> Tambah User
                </button>
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
                                <th>Terakhir Login</th>
                                <th style="width:140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $index => $user)
                                <tr>
                                    <td>{{ $users->firstItem() + $index }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div
                                                style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:11px;flex-shrink:0;">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <span class="fw-600">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $roleColors = ['admin' => 'primary', 'manajer' => 'warning', 'staf' => 'info'];
                                        @endphp
                                        <span class="badge bg-{{ $roleColors[$user->role] ?? 'secondary' }}"
                                            style="font-size:11px; padding:4px 10px; border-radius:20px;">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>{{ $user->no_hp ?? '-' }}</td>
                                    <td>
                                        <span
                                            class="badge-status badge-{{ $user->account_status }}">{{ ucfirst($user->account_status) }}</span>
                                    </td>
                                    <td style="font-size:12px; color:var(--text-secondary);">
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </td>
                                    <td style="font-size:12px;">
                                        {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            {{-- Manager: Approve/Reject user account --}}
                                            @if(auth()->user()->isManager() && $user->account_status === 'pending')
                                                <form action="{{ route('users.approveAccount', $user->id) }}" method="POST"
                                                    style="display:inline;" id="approveUser-{{ $user->id }}">
                                                    @csrf
                                                    <button type="button" class="btn-action approve" title="Approve"
                                                        onclick="swalConfirm('Approve User', 'Approve akun user ini?', 'question', 'Ya, Approve', '#approveUser-{{ $user->id }}')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('users.rejectAccount', $user->id) }}" method="POST"
                                                    style="display:inline;" id="rejectUser-{{ $user->id }}">
                                                    @csrf
                                                    <button type="button" class="btn-action reject" title="Reject"
                                                        onclick="swalConfirm('Reject User', 'Reject akun user ini?', 'warning', 'Ya, Reject', '#rejectUser-{{ $user->id }}')">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Admin: Edit/Delete --}}
                                            @if(auth()->user()->isAdmin())
                                                <button type="button" class="btn-action edit" title="Edit"
                                                    onclick="openUserModal({{ $user->id }})">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                @if($user->id !== auth()->id())
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                        id="deleteUser-{{ $user->id }}">
                                                        @csrf @method('DELETE')
                                                        <button type="button" class="btn-action delete" title="Hapus"
                                                            onclick="swalConfirm('Hapus User', 'Yakin hapus user ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteUser-{{ $user->id }}')">
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
                                        <i class="bi bi-people"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
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

    {{-- User Modal (Create/Edit) --}}
    @if(auth()->user()->isAdmin())
        <div class="modal fade inventrack-modal" id="userModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="position:relative;">
                    <div class="modal-loading-overlay" id="userLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalTitle">
                            <i class="bi bi-person-plus-fill"></i> <span>Tambah User</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-error-alert" id="userError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="userErrorMsg"></span>
                        </div>
                        <form id="userForm" novalidate>
                            <input type="hidden" id="userId" value="">
                            <input type="hidden" id="userMethod" value="POST">

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Masukkan nama lengkap" required
                                    id="userName">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required
                                        id="userEmail">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No HP</label>
                                    <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx"
                                        id="userNoHp">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required id="userRole">
                                    <option value="">-- Pilih Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="manajer">Manajer</option>
                                    <option value="staf">Staf</option>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" id="userPasswordLabel">Password <span
                                            class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter"
                                        id="userPassword">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" id="userPasswordConfirmLabel">Konfirmasi Password <span
                                            class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        placeholder="Ulangi password" id="userPasswordConfirm">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="userSubmitBtn" onclick="submitUserForm()">
                            <i class="bi bi-check-lg"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@if(auth()->user()->isAdmin())
    @push('scripts')
        <script>
            const userModalEl = document.getElementById('userModal');
            const userModal = new bootstrap.Modal(userModalEl);

            function openUserModal(id = null) {
                // Reset
                document.getElementById('userForm').reset();
                document.getElementById('userError').style.display = 'none';
                document.querySelectorAll('#userForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));

                if (id) {
                    // Edit mode
                    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit User</span>';
                    document.getElementById('userId').value = id;
                    document.getElementById('userMethod').value = 'PUT';
                    document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';
                    document.getElementById('userPasswordLabel').innerHTML = 'Password Baru';
                    document.getElementById('userPasswordConfirmLabel').innerHTML = 'Konfirmasi Password';
                    document.getElementById('userPassword').placeholder = 'Kosongkan jika tidak ubah';
                    document.getElementById('userPassword').removeAttribute('required');
                    document.getElementById('userPasswordConfirm').removeAttribute('required');

                    const loading = document.getElementById('userLoading');
                    loading.classList.add('show');
                    userModal.show();

                    fetch(`{{ url('users') }}/${id}/edit-data`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(res => res.json())
                        .then(data => {
                            loading.classList.remove('show');
                            document.getElementById('userName').value = data.name;
                            document.getElementById('userEmail').value = data.email;
                            document.getElementById('userNoHp').value = data.no_hp || '';
                            document.getElementById('userRole').value = data.role;
                        })
                        .catch(() => {
                            loading.classList.remove('show');
                            document.getElementById('userErrorMsg').textContent = 'Gagal memuat data user.';
                            document.getElementById('userError').style.display = 'block';
                        });
                } else {
                    // Create mode
                    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus-fill"></i> <span>Tambah User</span>';
                    document.getElementById('userId').value = '';
                    document.getElementById('userMethod').value = 'POST';
                    document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
                    document.getElementById('userPasswordLabel').innerHTML = 'Password <span class="text-danger">*</span>';
                    document.getElementById('userPasswordConfirmLabel').innerHTML = 'Konfirmasi Password <span class="text-danger">*</span>';
                    document.getElementById('userPassword').placeholder = 'Minimal 6 karakter';
                    document.getElementById('userPassword').setAttribute('required', 'required');
                    document.getElementById('userPasswordConfirm').setAttribute('required', 'required');
                    userModal.show();
                }
            }

            function submitUserForm() {
                const form = document.getElementById('userForm');
                const errorDiv = document.getElementById('userError');
                const errorMsg = document.getElementById('userErrorMsg');
                const loading = document.getElementById('userLoading');
                const submitBtn = document.getElementById('userSubmitBtn');
                const userId = document.getElementById('userId').value;
                const method = document.getElementById('userMethod').value;

                errorDiv.style.display = 'none';
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                loading.classList.add('show');
                submitBtn.disabled = true;

                const formData = new FormData(form);
                const url = userId ? `{{ url('users') }}/${userId}` : '{{ route("users.store") }}';

                if (method === 'PUT') {
                    formData.append('_method', 'PUT');
                }

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(res => res.json().then(data => ({ status: res.status, data })))
                    .then(({ status, data }) => {
                        loading.classList.remove('show');
                        submitBtn.disabled = false;

                        if (data.success) {
                            userModal.hide();
                            Toast.fire({ icon: 'success', title: data.message });
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            let messages = [];
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    messages.push(data.errors[key][0]);
                                    const input = form.querySelector(`[name="${key}"]`);
                                    if (input) input.classList.add('is-invalid');
                                });
                            }
                            if (data.message && !data.errors) messages.push(data.message);
                            errorMsg.innerHTML = messages.join('<br>');
                            errorDiv.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        submitBtn.disabled = false;
                        errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                        errorDiv.style.display = 'block';
                    });
            }

            // Reset on close
            userModalEl.addEventListener('hidden.bs.modal', function () {
                document.getElementById('userForm').reset();
                document.getElementById('userError').style.display = 'none';
                document.querySelectorAll('#userForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            });
        </script>
    @endpush
@endif