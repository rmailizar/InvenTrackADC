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
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <script>
        (function() {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        .public-page { min-height: 100vh; background: var(--body-bg); }
        .public-header {
            background: linear-gradient(135deg, #064e3b 0%, #091413 100%);
            padding: 32px 0;
            color: white;
            position: sticky; top: 0; z-index: 100;
        }
        .public-header .brand { display: flex; align-items: center; gap: 14px; }
        .public-header .brand-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius); display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white; box-shadow: 0 4px 15px rgba(16,185,129,0.35);
        }
        .public-header .brand-name { font-size: 22px; font-weight: 700; }
        .public-header .brand-sub { font-size: 12px; opacity: 0.6; letter-spacing: 0.5px; }
        .public-body { padding: 28px 0 60px; }
        .section-title {
            font-size: 18px; font-weight: 700; color: var(--text-primary);
            margin-bottom: 16px; display: flex; align-items: center; gap: 10px;
        }
        .section-title i { color: var(--primary); font-size: 20px; }
        .stock-status-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .stock-ok { background: var(--success-bg); color: var(--success); }
        .stock-low { background: var(--warning-bg); color: var(--warning-dark); }
        .stock-empty { background: var(--danger-bg); color: var(--danger); }
        .request-form-card {
            border: 2px solid var(--primary);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .request-form-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 20px 24px; color: white;
        }
        .request-form-header h5 { margin: 0; font-weight: 700; font-size: 16px; }
        .request-form-header p { margin: 4px 0 0; font-size: 12px; opacity: 0.8; }
        .request-form-body { padding: 24px; background: var(--card-bg); }

        /* Toast custom for public page */
        .public-toast {
            position: fixed; top: 20px; right: 20px; z-index: 1060;
            padding: 14px 22px; border-radius: var(--radius);
            font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px;
            animation: slideInRight 0.4s ease; box-shadow: var(--shadow-lg);
            max-width: 420px;
        }
        .public-toast.success { background: var(--success); color: white; }
        .public-toast.error { background: var(--danger); color: white; }
    </style>
</head>
<body>
    <div class="public-page">
        {{-- Header --}}
        <header class="public-header">
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="brand">
                        <div class="brand-icon"><i class="bi bi-box-seam-fill"></i></div>
                        <div>
                            <div class="brand-name">InvenTrack</div>
                            <div class="brand-sub">REKAP STOK & REQUEST BARANG</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti tema">
                            <i class="bi bi-sun-fill icon-sun"></i>
                            <i class="bi bi-moon-fill icon-moon"></i>
                        </button>
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary" style="border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </div>
                </div>
            </div>
        </header>

        {{-- Toast --}}
        @if(session('success'))
        <div class="public-toast success" id="publicToast">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="public-toast error" id="publicToast">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        {{-- Body --}}
        <div class="public-body">
            <div class="container">
                <div class="row g-4">
                    {{-- Left: Stock Recap --}}
                    <div class="col-lg-8">
                        <div class="section-title">
                            <i class="bi bi-clipboard-data-fill"></i> Rekap Stok Barang
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
                                                        <span class="stock-status-badge stock-ok"><i class="bi bi-check-circle-fill"></i> Aman</span>
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
                            <i class="bi bi-send-fill"></i> Request Tambah Stok
                        </div>

                        <div class="request-form-card">
                            <div class="request-form-header">
                                <h5><i class="bi bi-plus-circle me-2"></i>Form Request Stok</h5>
                                <p>Ajukan permintaan penambahan stok tanpa login</p>
                            </div>
                            <div class="request-form-body">
                                <form method="POST" action="{{ route('public.stock-request.store') }}" id="requestForm">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Nama Anda <span class="text-danger">*</span></label>
                                        <input type="text" name="requester_name" class="form-control" placeholder="Masukkan nama lengkap" value="{{ old('requester_name') }}" required>
                                        @error('requester_name')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
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
                                        <label class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
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
                                    <li>Status: <span class="stock-status-badge stock-ok" style="font-size:10px;">Aman</span>
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

        {{-- Footer --}}
        <footer style="background:var(--card-bg);border-top:1px solid var(--border-color);padding:20px 0;text-align:center;">
            <div class="container">
                <p style="margin:0;font-size:12px;color:var(--text-muted);">
                    &copy; {{ date('Y') }} InvenTrack. Sistem Manajemen Inventory.
                </p>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        // Auto-dismiss toast
        const toast = document.getElementById('publicToast');
        if (toast) {
            setTimeout(() => {
                toast.style.animation = 'slideInRight 0.4s reverse forwards';
                setTimeout(() => toast.remove(), 400);
            }, 5000);
        }
    </script>
</body>
</html>
