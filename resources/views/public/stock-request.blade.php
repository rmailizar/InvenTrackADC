<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rekap Stok & Request Barang - InvenTrack</title>
    <meta name="description" content="Lihat rekap stok barang dan ajukan request stok tanpa login">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">

    <script>
        (function() {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

</head>
<body>
    <!-- Hero Background -->
    <div class="background-glow-container">
        <svg viewBox="0 0 1440 400" preserveAspectRatio="none" style="width:100%; height:100vh; opacity:0.5;">
            <path class="glowing-line" d="M0 300 C 300 250, 400 350, 700 200 C 1000 50, 1200 150, 1440 50" 
                  stroke="url(#line_gradient)" stroke-width="4" fill="none" />
            <defs>
                <linearGradient id="line_gradient">
                    <stop offset="0%" stop-color="#a855f7" />
                    <stop offset="100%" stop-color="#10b981" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    
    <div class="public-page">
        {{-- Header --}}
        <header class="public-header">
        <div class="container">
            <div class="header-wrapper d-flex align-items-center justify-content-between">
                
                <div class="d-none d-md-block">
                    <div class="company-logo">
                        <img src="{{ asset('images/logo-perusahaan.png') }}" alt="Logo" class="logo-img">
                    </div>
                </div>

                <div class="brand text-center">
                    <div class="brand-icon mx-auto"><i class="bi bi-box-seam-fill"></i></div>
                    <div>
                        <div class="brand-name">InvenTrack</div>
                        <div class="brand-sub">DAFTAR BARANG & PERMINTAAN BARANG</div>
                    </div>
                </div>

                <div class="header-actions d-flex align-items-center gap-2">
                    <button class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti tema">
                        <i class="bi bi-sun-fill icon-sun"></i>
                        <i class="bi bi-moon-fill icon-moon"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal" style="border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </button>
                </div>

            </div>
        </div>
    </header>

        {{-- Body --}}
        <div class="public-body">
            <div class="container">
                <div class="row g-4">
                    {{-- Left: Stock Recap --}}
                    <div class="col-lg-8">
                        <div class="section-title">
                            <i class="bi bi-clipboard-data-fill"></i> Daftar Barang
                        </div>

                        {{-- Filter --}}
                        <div class="filter-bar mb-3">
                            <form method="GET" action="{{ route('public.stock-request') }}">
                                <div class="row align-items-end g-2">
                                    <div class="col-md-5">
                                        <input type="text" name="search" class="form-control" placeholder="Cari nama barang..." value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <select name="category" class="form-select">
                                            <option value="">Semua Kategori</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i> Cari</button>
                                        <a href="{{ route('public.stock-request') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Table --}}
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-container">
                                    <table class="table" id="stock-recap-table">
                                        <thead>
                                            <tr>
                                                <th style="width:45px;">No</th>
                                                <th>Nama Barang</th>
                                                <th>Kategori</th>
                                                <th>Satuan</th>
                                                <th class="text-center">Stok</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($items as $index => $item)
                                            @php $stock = $item->current_stock; @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td class="fw-600">{{ $item->name }}</td>
                                                <td>{{ $item->category }}</td>
                                                <td>{{ $item->unit }}</td>
                                                <td class="text-center fw-700" style="font-size:15px; {{ $stock === 0 ? 'color:var(--danger);' : ($stock <= $item->min_stock ? 'color:var(--warning-dark);' : 'color:var(--success);') }}">
                                                    {{ number_format($stock) }}
                                                </td>
                                                <td>
                                                    @if($stock === 0)
                                                        <span class="stock-status-badge stock-empty"><i class="bi bi-x-circle-fill"></i> Habis</span>
                                                    @elseif($stock <= $item->min_stock)
                                                        <span class="stock-status-badge stock-low"><i class="bi bi-exclamation-triangle-fill"></i> Rendah</span>
                                                    @else
                                                        <span class="stock-status-badge stock-ok"><i class="bi bi-check-circle-fill"></i> Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr class="no-data-row">
                                                <td colspan="6">
                                                    <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                                    Belum ada data barang
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Request Form --}}
                    <div class="col-lg-4">
                        <div class="section-title">
                            <i class="bi bi-send-fill"></i> Permintaan Barang
                        </div>

                        <div class="request-form-card">
                            <div class="request-form-header">
                                <h5><i class="bi bi-plus-circle me-2"></i>Form Permintaan Barang</h5>
                                <p>Ajukan permintaan penambahan barang tanpa login</p>
                            </div>
                            <div class="request-form-body">
                                <form method="POST" action="{{ route('public.stock-request.store') }}" id="requestForm">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="requester_name" class="form-control" placeholder="Masukkan nama lengkap" value="{{ old('requester_name') }}" required>
                                        @error('requester_name')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">NIP <span class="text-danger">*</span></label>
                                        <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP"
                                            value="{{ old('nip') }}" required>
                                        @error('nip')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Jabatan/Posisi <span class="text-danger">*</span></label>
                                        <input type="text" name="jabatan" class="form-control" placeholder="Masukkan jabatan"
                                            value="{{ old('jabatan') }}" required>
                                        @error('jabatan')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bidang/Bagian <span class="text-danger">*</span></label>
                                        <input type="text" name="bidang" class="form-control" placeholder="Masukkan bidang"
                                            value="{{ old('bidang') }}" required>
                                        @error('bidang')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Pilih Barang <span class="text-danger">*</span></label>
                                        <select name="item_id" class="form-select" required id="itemSelect">
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach($allItems as $it)
                                                <option value="{{ $it->id }}" {{ old('item_id') == $it->id ? 'selected' : '' }}
                                                    data-unit="{{ $it->unit }}" data-stock="{{ $it->current_stock }}">
                                                    {{ $it->name }} ({{ $it->category }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('item_id')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror

                                        {{-- Item info display --}}
                                        <div class="d-none mt-2 p-2 rounded-3" id="itemInfo" style="background:var(--primary-bg);font-size:12px;">
                                            <div class="d-flex justify-content-between">
                                                <span style="color:var(--text-secondary);">Satuan: <strong id="itemUnit" style="color:var(--text-primary);">-</strong></span>
                                                <span style="color:var(--text-secondary);">Stok saat ini: <strong id="itemStock" style="color:var(--primary);">-</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Jumlah Request <span class="text-danger">*</span></label>
                                        <input type="number" name="quantity" class="form-control" placeholder="Masukkan jumlah" min="1" value="{{ old('quantity', 1) }}" required>
                                        @error('quantity')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Kebutuhan</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Alasan request atau keterangan tambahan...">{{ old('notes') }}</textarea>
                                        @error('notes')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-send-fill me-1"></i> Kirim Request
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Info box --}}
                        <div class="card mt-3">
                            <div class="card-body" style="font-size:13px;">
                                <h6 style="font-weight:700;margin-bottom:10px;color:var(--text-primary);">
                                    <i class="bi bi-info-circle-fill text-primary-custom me-1"></i> Informasi
                                </h6>
                                <ul style="margin:0;padding-left:18px;color:var(--text-secondary);line-height:2;">
                                    <li>Request akan ditinjau oleh Admin</li>
                                    <li>Tidak memerlukan login</li>
                                    <li>Stok diperbarui secara real-time</li>
                                    <li>Status: <span class="stock-status-badge stock-ok" style="font-size:10px;">Ada</span>
                                        <span class="stock-status-badge stock-low" style="font-size:10px;">Rendah</span>
                                        <span class="stock-status-badge stock-empty" style="font-size:10px;">Habis</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> {{-- End .public-page --}}

    {{-- Login Modal (placed outside .public-page to avoid z-index stacking context issues) --}}
        <div class="modal fade inventrack-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
                <div class="modal-content" style="position:relative;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 15px; right: 15px; z-index: 1051;"></button>

                    <div class="modal-loading-overlay" id="loginLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    
                    <div class="login-modal-brand">
                        <div class="brand-icon"><i class="bi bi-box-seam-fill"></i></div>
                        <h5>InvenTrack</h5>
                        <p>Sistem Manajemen Inventory</p>
                    </div>

                    <div class="modal-body" style="max-height:none;">
                        <div class="modal-error-alert" id="loginError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="loginErrorMsg"></span>
                        </div>
                        <form id="loginForm" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required autofocus id="loginEmail">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required id="loginPassword">
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="remember" id="loginRemember">
                                    <label class="form-check-label" for="loginRemember" style="font-size:13px;color:var(--text-secondary);">Ingat Saya</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk
                            </button>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-center" style="border-top:none;background:transparent;padding-top:0;">
                        <span style="font-size:12px;color:var(--text-muted);">&copy; {{ date('Y') }} Port Managemen Unit Suralaya</span>
                    </div>
                </div>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('inventrack-theme', newTheme);
        }

        // Item select → show info
        document.getElementById('itemSelect').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const info = document.getElementById('itemInfo');
            if (this.value) {
                document.getElementById('itemUnit').textContent = selected.dataset.unit;
                document.getElementById('itemStock').textContent = selected.dataset.stock;
                info.classList.remove('d-none');
            } else {
                info.classList.add('d-none');
            }
        });

        // SweetAlert2 Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: {!! json_encode(session('success')) !!},
                confirmButtonText: 'OK',
                customClass: {
                    popup: document.documentElement.getAttribute('data-theme') === 'dark' ? 'swal-dark' : '',
                    confirmButton: 'swal-btn-confirm'
                },
                buttonsStyling: false,
            });
        @endif

        @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: {!! json_encode(session('error')) !!}
            });
        @endif

        // Login Modal AJAX
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const errorDiv = document.getElementById('loginError');
            const errorMsg = document.getElementById('loginErrorMsg');
            const loading = document.getElementById('loginLoading');
            const submitBtn = document.getElementById('loginSubmitBtn');

            // Clear previous errors
            errorDiv.style.display = 'none';
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            // Show loading
            loading.classList.add('show');
            submitBtn.disabled = true;

            const formData = new FormData(form);

            fetch('{{ url("/login") }}', {
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
                    // Success — redirect
                    window.location.href = data.redirect || '/dashboard';
                } else {
                    // Show errors
                    let messages = [];
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            messages.push(data.errors[key][0]);
                            const input = form.querySelector(`[name="${key}"]`);
                            if (input) input.classList.add('is-invalid');
                        });
                    }
                    if (data.message) messages.push(data.message);
                    errorMsg.innerHTML = messages.join('<br>');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                loading.classList.remove('show');
                submitBtn.disabled = false;
                errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                errorDiv.style.display = 'block';
            });
        });

        // Reset login form when modal closes
        document.getElementById('loginModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('loginForm').reset();
            document.getElementById('loginError').style.display = 'none';
            document.querySelectorAll('#loginForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });

        // Auto-open login modal if redirected from /login
        @if(session('openLogin'))
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        @endif
    </script>
</body>
</html>
