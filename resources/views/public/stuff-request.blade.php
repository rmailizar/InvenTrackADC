<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rekap Stok & Permintaan Barang - NextLog</title>
    <meta name="description" content="Lihat rekap stok barang dan ajukan stuff request tanpa login">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preload" as="image" href="{{ asset('images/logo-web.png') }}" fetchpriority="high">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-web-top.png') }}">
    @if($activeBidang === 'teknik')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endif
    <script>
        (function() {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

</head>
<body class="public-layout">
    <!-- Animated Nexus Background -->
    <div class="background-glow-container">
        <div class="nexus-bg"></div>
        <div class="nexus-grid"></div>
    </div>
    
    <div class="public-page">
        {{-- Header --}}
        <header class="public-header">
        <div class="container">
            <div class="header-wrapper d-flex align-items-center justify-content-between">
                
                <div class="d-none d-md-block">
                    <div class="company-logo">
                        <img src="{{ asset('images/logo-perusahaan.png') }}" alt="Logo" class="logo-img" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="brand text-center">
                    <div class="brand-logo-wrapper mx-auto">
                        <img src="{{ asset('images/logo-web.png') }}" alt="InvenTrack Logo" class="app-logo" decoding="async" fetchpriority="high">
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
                @if($activeBidang === 'teknik' && $publicDashboard)
                    <div class="row g-3 mb-4 dashboard-summary-cards">
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card primary">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stats-icon">
                                        <i class="bi bi-box-seam-fill"></i>
                                    </div>
                                    <div class="stats-copy">
                                        <div class="stats-value" style="font-size:22px;">{{ number_format($publicDashboard['totalItems']) }}</div>
                                        <div class="stats-label">Total Barang</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card success">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stats-icon">
                                        <i class="bi bi-arrow-down-circle-fill"></i>
                                    </div>
                                    <div class="stats-copy">
                                        <div class="stats-value" style="font-size:22px;">{{ number_format($publicDashboard['masukBulanIni']) }}</div>
                                        <div class="stats-label">Goods Receipt Bulan Ini</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card danger">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stats-icon">
                                        <i class="bi bi-arrow-up-circle-fill"></i>
                                    </div>
                                    <div class="stats-copy">
                                        <div class="stats-value" style="font-size:22px;">{{ number_format($publicDashboard['keluarBulanIni']) }}</div>
                                        <div class="stats-label">Goods Issue Bulan Ini</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card warning">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stats-icon">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <div class="stats-copy">
                                        <div class="stats-value" style="font-size:22px;">{{ number_format($publicDashboard['lowStockCount']) }}</div>
                                        <div class="stats-label">Total Stok Rendah</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <span>
                                        <i class="bi bi-graph-up text-primary-custom me-2"></i>
                                        Goods Receipt vs Goods Issue
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <select id="publicMonthlyPeriodFilter" class="form-select form-select-sm"
                                            style="width:auto; min-width:140px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                            <option value="thisMonth" {{ $publicDashboard['selectedMonthlyPeriod'] === 'thisMonth' ? 'selected' : '' }}>Bulan Ini</option>
                                            <option value="6months" {{ $publicDashboard['selectedMonthlyPeriod'] === '6months' ? 'selected' : '' }}>6 Bulan Terakhir</option>
                                            <option value="ytd" {{ $publicDashboard['selectedMonthlyPeriod'] === 'ytd' ? 'selected' : '' }}>12 Bulan</option>
                                        </select>
                                        <select id="publicMonthlyYearFilter" class="form-select form-select-sm"
                                            style="width:auto; min-width:110px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                            @foreach($publicDashboard['availableYears'] as $year)
                                                <option value="{{ $year }}" {{ $publicDashboard['selectedYear'] == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:320px;">
                                        <canvas id="publicMonthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-pie-chart-fill text-primary-custom me-2"></i>Stok per Ship Unloader</span>
                                    <select id="publicShipYearFilter" class="form-select form-select-sm"
                                        style="width:auto; min-width:120px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                        <option value="">Semua Tahun</option>
                                        @foreach($publicDashboard['availableYears'] as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:320px;">
                                        <canvas id="publicShipChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 align-items-start">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <span><i class="bi bi-fire text-danger-custom me-2"></i>Goods Issue Terbanyak</span>
                                </div>
                                <div class="card-body py-2">
                                    @forelse($publicDashboard['topKeluar'] as $index => $tx)
                                        <div
                                            class="d-flex align-items-center justify-content-between gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="d-flex align-items-center gap-3 min-width-0">
                                                <span class="fw-700"
                                                    style="width:24px;height:24px;border-radius:50%;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;">{{ $index + 1 }}</span>
                                                <div class="min-width-0">
                                                    <div class="fw-600 text-truncate" style="font-size:13px;">{{ $tx->item->name ?? 'Barang dihapus' }}</div>
                                                    <div class="text-truncate" style="font-size:11px;color:var(--text-secondary);">
                                                        @if($tx->item?->no_normalisasi)
                                                            {{ $tx->item->no_normalisasi }} -
                                                        @endif
                                                        {{ $tx->item->category ?? '' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="fw-700" style="color:#CA5995;flex-shrink:0;">{{ number_format($tx->total) }}</span>
                                        </div>
                                    @empty
                                        <div class="empty-state" style="padding:24px 10px;">
                                            <i class="bi bi-inbox" style="font-size:36px;"></i>
                                            <h6 class="mt-2" style="font-size:13px;">Belum ada data</h6>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <span><i class="bi bi-clock-fill text-primary-custom me-2"></i>Transaksi Terbaru</span>
                                </div>
                                <div class="card-body py-2">
                                    @forelse($publicDashboard['recentTransactions'] as $tx)
                                        <div class="d-flex align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div
                                                style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;{{ $tx->type === 'in' ? 'background:var(--success-bg);color:var(--success);' : 'background:var(--danger-bg);color:var(--danger);' }}">
                                                <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"></i>
                                            </div>
                                            <div class="flex-grow-1 min-width-0">
                                                <div class="fw-600 text-truncate" style="font-size:13px;">{{ $tx->item->name ?? 'Barang dihapus' }}</div>
                                                <div class="text-truncate" style="font-size:11px;color:var(--text-secondary);">
                                                    {{ $tx->type_label }} - {{ $tx->quantity }} {{ $tx->item->unit ?? '' }} - {{ $tx->user->name ?? 'System' }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="empty-state" style="padding:24px 10px;">
                                            <i class="bi bi-inbox" style="font-size:36px;"></i>
                                            <h6 class="mt-2" style="font-size:13px;">Belum ada transaksi</h6>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row g-4">
                    {{-- Left: Stock Recap --}}
                    <div class="col-lg-8">
                        <div class="section-title">
                            <i class="bi bi-clipboard-data-fill"></i> Daftar Barang
                        </div>

                        <div class="report-tabs mb-3">
                            <a href="{{ route('public.stuff-request', array_merge(request()->except(['bidang', 'page']), ['bidang' => 'umum'])) }}"
                                class="report-tab {{ $activeBidang === 'umum' ? 'active' : '' }}">
                                <i class="bi bi-building"></i>
                                Barang Bidang Umum
                            </a>
                            <a href="{{ route('public.stuff-request', array_merge(request()->except(['bidang', 'page']), ['bidang' => 'teknik'])) }}"
                                class="report-tab {{ $activeBidang === 'teknik' ? 'active' : '' }}">
                                <i class="bi bi-tools"></i>
                                Barang Bidang Teknik
                            </a>
                        </div>

                        {{-- Filter --}}
                        <div class="filter-bar mb-3">
                            <form method="GET" action="{{ route('public.stuff-request') }}">
                                <input type="hidden" name="bidang" value="{{ $activeBidang }}">
                                <div class="row align-items-end g-2">
                                    <div class="col-md-5">
                                        <input type="text" name="search" class="form-control"
                                            placeholder="{{ $activeBidang === 'teknik' ? 'Cari no normalisasi, nama, komponen, lokasi, satuan...' : 'Cari nama barang...' }}"
                                            value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <select name="category" class="form-select">
                                            <option value="">Semua {{ $activeBidang === 'teknik' ? 'Komponen' : 'Kategori' }}</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i> Cari</button>
                                        <a href="{{ route('public.stuff-request', ['bidang' => $activeBidang]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
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
                                                @if($activeBidang === 'teknik')
                                                    <th>No Normalisasi</th>
                                                @endif
                                                <th>Nama Barang</th>
                                                <th>{{ $activeBidang === 'teknik' ? 'Komponen' : 'Kategori' }}</th>
                                                @if($activeBidang === 'teknik')
                                                    <th>Lokasi</th>
                                                @endif
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
                                                @if($activeBidang === 'teknik')
                                                    <td class="fw-600">{{ $item->no_normalisasi ?? '-' }}</td>
                                                @endif
                                                <td class="fw-600">{{ $item->name }}</td>
                                                <td>{{ $activeBidang === 'teknik' ? ($item->component ?? '-') : $item->category }}</td>
                                                @if($activeBidang === 'teknik')
                                                    <td>{{ $item->lokasi ?? '-' }}</td>
                                                @endif
                                                <td>{{ $item->unit }}</td>
                                                <td class="text-center fw-700" style="font-size:15px; {{ $stock <= 0 ? 'color:var(--danger);' : ($stock <= $item->min_stock ? 'color:var(--warning-dark);' : 'color:var(--success);') }}">
                                                    {{ number_format($stock) }}
                                                </td>
                                                <td>
                                                    @if($stock <= 0)
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
                                                <td colspan="{{ $activeBidang === 'teknik' ? 8 : 6 }}">
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
                                <form method="POST" action="{{ route('public.stuff-request.store') }}" id="requestForm">
                                    @csrf
                                    <input type="hidden" name="bidang" value="{{ $activeBidang }}">

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
                                        <label class="form-label">Bidang Tujuan</label>
                                        <input type="text" class="form-control" value="Bidang {{ ucfirst($activeBidang) }}" readonly>
                                        @error('bidang')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @php
                                        $lineRows = old('lines', [['item_id' => '', 'quantity' => 1]]);
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label d-flex justify-content-between align-items-center">
                                            <span>Daftar Barang <span class="text-danger">*</span></span>
                                        </label>
                                        <p class="small text-muted mb-2" style="font-size:12px;">Pilih satu atau lebih barang beserta jumlahnya.</p>
                                        <div id="requestLines" class="d-flex flex-column gap-2">
                                            @foreach($lineRows as $i => $line)
                                                <div class="request-line-row border rounded-3 p-2" style="border-color:var(--border-color, #dee2e6) !important;background:var(--card-bg-subtle, transparent);">
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-12 col-md-7">
                                                            <label class="form-label small mb-1 text-muted">Barang</label>
                                                            <select name="lines[{{ $i }}][item_id]" class="form-select form-select-sm" data-field="item" required>
                                                                <option value="">-- Pilih barang --</option>
                                                                @foreach($allItems as $it)
                                                                    <option value="{{ $it->id }}"
                                                                        data-stock="{{ $it->current_stock }}"
                                                                        data-unit="{{ $it->unit }}"
                                                                        @selected((string) old('lines.'.$i.'.item_id', $line['item_id'] ?? '') === (string) $it->id)>
                                                                        {{ $it->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="small text-muted mt-1" data-field="stock-info"></div>
                                                        </div>
                                                        <div class="col-6 col-md-3">
                                                            <label class="form-label small mb-1 text-muted">Jumlah</label>
                                                            <input type="number" name="lines[{{ $i }}][quantity]" data-field="qty" class="form-control form-control-sm" min="1" placeholder="Qty" value="{{ old('lines.'.$i.'.quantity', $line['quantity'] ?? 1) }}" required>
                                                            <div class="text-danger mt-1 d-none" data-field="stock-error" style="font-size:11px;"></div>
                                                        </div>
                                                        <div class="col-6 col-md-2 text-md-end pb-md-1">
                                                            <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-line" title="Hapus baris">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('lines')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                        @foreach ($errors->keys() as $key)
                                            @if (str_starts_with($key, 'lines.') && $errors->first($key))
                                                <div class="text-danger mt-1" style="font-size:12px;">{{ $errors->first($key) }}</div>
                                            @endif
                                        @endforeach
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" id="addLineBtn">
                                            <i class="bi bi-plus-lg"></i> Tambah barang
                                        </button>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Kebutuhan</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Alasan request atau keterangan tambahan...">{{ old('notes') }}</textarea>
                                        @error('notes')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100" id="requestSubmitBtn">
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

    <!-- Public footer -->
    <footer class="public-footer">
        <div class="footer-content">
            <div class="copyright">
                &copy; 2026 Port Management Unit Suralaya
            </div>
        </div>
    </footer>
    <!-- End Public footer -->

    {{-- Login Modal (placed outside .public-page to avoid z-index stacking context issues) --}}
        <div class="modal fade inventrack-modal login-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
                <div class="modal-content" style="position:relative;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 15px; right: 15px; z-index: 1051;"></button>

                    <div class="modal-loading-overlay" id="loginLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    
                    <div class="login-modal-brand">
                        <div class="brand-icon">
                            <img src="{{ asset('images/logo-web.png') }}" alt="InvenTrack Logo" class="login-modal-logo-img" loading="lazy" decoding="async">
                        </div>
                        <div class="logo-container">
                            <div class="next-logistic">
                                NEXTLOGISTIC
                            </div>
                        </div>
                    </div>

                    <div class="modal-body" style="max-height:none;">
                        <div class="modal-error-alert" id="loginError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="loginErrorMsg"></span>
                        </div>
                        <form id="loginForm" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus id="loginUsername" autocomplete="username">
                            </div>
                            <div class="mb-3 pb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required id="loginPassword" style="border-right: none;">
                                    <span class="input-group-text" id="togglePassword" style="cursor: pointer; border-left: none;">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-30 mx-auto d-block" id="loginSubmitBtn">
                                <i class="bi bi-box-arrow-in-right text-center"></i> Masuk
                            </button>
                            <button type="button" class="btn btn-link w-100 mt-2 p-0" id="showForgotPasswordBtn">
                                Lupa password?
                            </button>
                        </form>

                        <form id="forgotPasswordForm" class="d-none" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required id="resetEmail" autocomplete="email">
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="forgotPasswordSubmitBtn">
                                <i class="bi bi-send"></i> Kirim Link Reset
                            </button>
                            <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="backToLoginBtn">
                                <i class="bi bi-arrow-left"></i> Kembali
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
    @if($activeBidang === 'teknik' && $publicDashboard)
        <script>
            (function() {
                if (typeof Chart === 'undefined') return;

                const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
                const chartTextColor = () => isDark() ? '#cbd5e1' : '#475569';
                const chartMutedColor = () => isDark() ? '#64748b' : '#94a3b8';
                const chartGridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                const chartSurfaceColor = () => isDark() ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
                const chartCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                const catColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b'];

                function lineGradient(ctx, type) {
                    const gradient = ctx.createLinearGradient(0, 0, 0, 320);
                    const prefix = type === 'gi' ? '--chart-gi' : '--chart-gr';
                    gradient.addColorStop(0, chartCssVar(`${prefix}-fill-start`));
                    gradient.addColorStop(0.65, chartCssVar(`${prefix}-fill-mid`));
                    gradient.addColorStop(1, chartCssVar(`${prefix}-fill-end`));
                    return gradient;
                }

                function hexToRgba(hex, alpha) {
                    const value = hex.replace('#', '');
                    const r = parseInt(value.substring(0, 2), 16);
                    const g = parseInt(value.substring(2, 4), 16);
                    const b = parseInt(value.substring(4, 6), 16);
                    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                }

                function donutGradient(ctx, color) {
                    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, hexToRgba(color, chartCssVar('--chart-donut-alpha-start')));
                    gradient.addColorStop(1, hexToRgba(color, chartCssVar('--chart-donut-alpha-end')));
                    return gradient;
                }

                function tooltipOptions() {
                    return {
                        mode: 'index',
                        intersect: false,
                        usePointStyle: true,
                        backgroundColor: chartSurfaceColor(),
                        titleColor: isDark() ? '#f8fafc' : '#0f172a',
                        bodyColor: chartTextColor(),
                        borderColor: isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6
                    };
                }

                function lineOptions() {
                    return {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 15,
                                    color: chartTextColor(),
                                    font: { family: 'Inter', size: 12, weight: 500 }
                                }
                            },
                            tooltip: tooltipOptions()
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { color: chartMutedColor(), padding: 10, font: { family: 'Inter', size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: chartGridColor(), drawBorder: false },
                                ticks: { color: chartMutedColor(), padding: 10, font: { family: 'Inter', size: 11 } }
                            }
                        },
                        interaction: { mode: 'nearest', axis: 'x', intersect: false }
                    };
                }

                let publicMonthlyChart = null;
                let publicShipChart = null;
                const monthlyCtx = document.getElementById('publicMonthlyChart')?.getContext('2d');
                if (monthlyCtx) {
                    publicMonthlyChart = new Chart(monthlyCtx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode(array_column($publicDashboard['monthlyData'], 'label')) !!},
                            datasets: [
                                {
                                    label: 'Goods Receipt',
                                    data: {!! json_encode(array_column($publicDashboard['monthlyData'], 'masuk')) !!},
                                    fill: true,
                                    backgroundColor: lineGradient(monthlyCtx, 'gr'),
                                    borderColor: '#3b82f6',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointBackgroundColor: chartCssVar('--chart-point-bg'),
                                    pointBorderColor: '#3b82f6',
                                    pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                                    pointHoverBorderColor: '#3b82f6',
                                    pointBorderWidth: 2,
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                },
                                {
                                    label: 'Goods Issue',
                                    data: {!! json_encode(array_column($publicDashboard['monthlyData'], 'keluar')) !!},
                                    fill: true,
                                    backgroundColor: lineGradient(monthlyCtx, 'gi'),
                                    borderColor: '#10b981',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointBackgroundColor: chartCssVar('--chart-point-bg'),
                                    pointBorderColor: '#10b981',
                                    pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                                    pointHoverBorderColor: '#10b981',
                                    pointBorderWidth: 2,
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                }
                            ]
                        },
                        options: lineOptions()
                    });
                }

                const shipCtx = document.getElementById('publicShipChart')?.getContext('2d');
                if (shipCtx) {
                    publicShipChart = new Chart(shipCtx, {
                        type: 'doughnut',
                        data: {
                            labels: {!! json_encode(array_column($publicDashboard['categoryData'], 'category')) !!},
                            datasets: [{
                                data: {!! json_encode(array_column($publicDashboard['categoryData'], 'stock')) !!},
                                backgroundColor: catColors.map(color => donutGradient(shipCtx, color)),
                                borderColor: catColors,
                                borderWidth: 2,
                                hoverOffset: 6,
                                spacing: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        padding: 15,
                                        color: chartMutedColor(),
                                        font: { family: 'Inter', size: 11, weight: 500 }
                                    }
                                },
                                tooltip: tooltipOptions()
                            }
                        }
                    });
                }

                window.refreshPublicDashboardCharts = function() {
                    if (publicMonthlyChart) {
                        publicMonthlyChart.options.plugins.legend.labels.color = chartTextColor();
                        publicMonthlyChart.options.plugins.tooltip.backgroundColor = chartSurfaceColor();
                        publicMonthlyChart.options.plugins.tooltip.titleColor = isDark() ? '#f8fafc' : '#0f172a';
                        publicMonthlyChart.options.plugins.tooltip.bodyColor = chartTextColor();
                        publicMonthlyChart.options.plugins.tooltip.borderColor = isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)';
                        publicMonthlyChart.options.scales.x.ticks.color = chartMutedColor();
                        publicMonthlyChart.options.scales.y.ticks.color = chartMutedColor();
                        publicMonthlyChart.options.scales.y.grid.color = chartGridColor();
                        publicMonthlyChart.data.datasets[0].backgroundColor = lineGradient(monthlyCtx, 'gr');
                        publicMonthlyChart.data.datasets[1].backgroundColor = lineGradient(monthlyCtx, 'gi');
                        publicMonthlyChart.data.datasets.forEach(dataset => {
                            dataset.pointBackgroundColor = chartCssVar('--chart-point-bg');
                            dataset.pointHoverBackgroundColor = chartCssVar('--chart-point-hover-bg');
                        });
                        publicMonthlyChart.update();
                    }

                    if (publicShipChart) {
                        publicShipChart.options.plugins.legend.labels.color = chartMutedColor();
                        publicShipChart.options.plugins.tooltip.backgroundColor = chartSurfaceColor();
                        publicShipChart.options.plugins.tooltip.titleColor = isDark() ? '#f8fafc' : '#0f172a';
                        publicShipChart.options.plugins.tooltip.bodyColor = chartTextColor();
                        publicShipChart.options.plugins.tooltip.borderColor = isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)';
                        publicShipChart.data.datasets[0].backgroundColor = catColors
                            .slice(0, publicShipChart.data.labels.length)
                            .map(color => donutGradient(shipCtx, color));
                        publicShipChart.update();
                    }
                };

                function changePublicMonthlyChart() {
                    if (!publicMonthlyChart) return;

                    const year = document.getElementById('publicMonthlyYearFilter')?.value || @json($publicDashboard['selectedYear']);
                    const period = document.getElementById('publicMonthlyPeriodFilter')?.value || 'thisMonth';
                    const url = `{{ route('public.teknik.monthlyData') }}?year=${encodeURIComponent(year)}&period=${encodeURIComponent(period)}`;

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            publicMonthlyChart.data.labels = data.map(d => d.label);
                            publicMonthlyChart.data.datasets[0].data = data.map(d => d.masuk);
                            publicMonthlyChart.data.datasets[1].data = data.map(d => d.keluar);
                            publicMonthlyChart.update('active');
                        })
                        .catch(err => console.error(err));
                }

                function changePublicShipChart() {
                    if (!publicShipChart) return;

                    const year = document.getElementById('publicShipYearFilter')?.value || '';
                    const url = year
                        ? `{{ route('public.teknik.shipUnloaderData') }}?year=${encodeURIComponent(year)}`
                        : `{{ route('public.teknik.shipUnloaderData') }}`;

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            publicShipChart.data.labels = data.map(d => d.category);
                            publicShipChart.data.datasets[0].data = data.map(d => d.stock);
                            publicShipChart.data.datasets[0].backgroundColor = catColors
                                .slice(0, data.length)
                                .map(color => donutGradient(shipCtx, color));
                            publicShipChart.data.datasets[0].borderColor = catColors.slice(0, data.length);
                            publicShipChart.update('active');
                        })
                        .catch(err => console.error(err));
                }

                document.getElementById('publicMonthlyPeriodFilter')?.addEventListener('change', changePublicMonthlyChart);
                document.getElementById('publicMonthlyYearFilter')?.addEventListener('change', changePublicMonthlyChart);
                document.getElementById('publicShipYearFilter')?.addEventListener('change', changePublicShipChart);
            })();
        </script>
    @endif
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('inventrack-theme', newTheme);

            if (typeof window.refreshPublicDashboardCharts === 'function') {
                window.refreshPublicDashboardCharts();
            }
        }

        // Multi-line barang: tambah / hapus baris
        (function() {
            const container = document.getElementById('requestLines');
            const addBtn = document.getElementById('addLineBtn');
            const form = document.getElementById('requestForm');
            const submitBtn = document.getElementById('requestSubmitBtn');
            if (!container || !addBtn) return;

            function reindexLineNames() {
                container.querySelectorAll('.request-line-row').forEach((row, idx) => {
                    const sel = row.querySelector('[data-field="item"]');
                    const qty = row.querySelector('[data-field="qty"]');
                    if (sel) sel.name = 'lines[' + idx + '][item_id]';
                    if (qty) qty.name = 'lines[' + idx + '][quantity]';
                });
            }

            function getSelectedOption(row) {
                const select = row.querySelector('[data-field="item"]');
                return select && select.value ? select.options[select.selectedIndex] : null;
            }

            function refreshStockValidation() {
                const totals = {};
                const rows = Array.from(container.querySelectorAll('.request-line-row'));

                rows.forEach((row) => {
                    const select = row.querySelector('[data-field="item"]');
                    const qty = row.querySelector('[data-field="qty"]');
                    if (!select || !qty || !select.value) return;

                    totals[select.value] = (totals[select.value] || 0) + (parseInt(qty.value || '0', 10) || 0);
                });

                let hasError = false;

                rows.forEach((row) => {
                    const option = getSelectedOption(row);
                    const qty = row.querySelector('[data-field="qty"]');
                    const info = row.querySelector('[data-field="stock-info"]');
                    const error = row.querySelector('[data-field="stock-error"]');

                    if (!qty || !info || !error) return;

                    qty.classList.remove('is-invalid');
                    error.classList.add('d-none');
                    error.textContent = '';

                    if (!option) {
                        qty.removeAttribute('max');
                        info.textContent = '';
                        return;
                    }

                    const stock = parseInt(option.dataset.stock || '0', 10) || 0;
                    const unit = option.dataset.unit || '';
                    const total = totals[option.value] || 0;

                    qty.max = stock;
                    info.textContent = `Stok tersedia: ${new Intl.NumberFormat('id-ID').format(stock)} ${unit}`;

                    if (stock <= 0) {
                        hasError = true;
                        qty.classList.add('is-invalid');
                        error.textContent = 'Habis.';
                        error.classList.remove('d-none');
                    } else if (total > stock) {
                        hasError = true;
                        qty.classList.add('is-invalid');
                        error.textContent = `Total permintaan barang ini ${new Intl.NumberFormat('id-ID').format(total)} ${unit}, melebihi stok ${new Intl.NumberFormat('id-ID').format(stock)} ${unit}.`;
                        error.classList.remove('d-none');
                    }
                });

                if (submitBtn) {
                    submitBtn.disabled = hasError;
                }

                return !hasError;
            }

            function bindLine(row) {
                const select = row.querySelector('[data-field="item"]');
                const qty = row.querySelector('[data-field="qty"]');
                const remove = row.querySelector('.btn-remove-line');

                if (select) select.addEventListener('change', refreshStockValidation);
                if (qty) qty.addEventListener('input', refreshStockValidation);
                if (remove) bindRemove(remove);
            }

            function bindRemove(btn) {
                btn.addEventListener('click', function() {
                    if (container.querySelectorAll('.request-line-row').length <= 1) return;
                    btn.closest('.request-line-row').remove();
                    reindexLineNames();
                    refreshStockValidation();
                });
            }

            container.querySelectorAll('.request-line-row').forEach(bindLine);

            addBtn.addEventListener('click', function() {
                const first = container.querySelector('.request-line-row');
                if (!first) return;
                const row = first.cloneNode(true);
                const sel = row.querySelector('[data-field="item"]');
                const qty = row.querySelector('[data-field="qty"]');
                if (sel) sel.value = '';
                if (qty) qty.value = 1;
                container.appendChild(row);
                bindLine(row);
                reindexLineNames();
                refreshStockValidation();
            });

            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!refreshStockValidation()) {
                        event.preventDefault();
                        Toast.fire({
                            icon: 'error',
                            title: 'Jumlah permintaan melebihi stok tersedia.'
                        });
                    }
                });
            }

            refreshStockValidation();
        })();

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
        document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const loginModal = document.getElementById('loginModal');
    const errorDiv = document.getElementById('loginError');
    const errorMsg = document.getElementById('loginErrorMsg');
    const loading = document.getElementById('loginLoading');
    const submitBtn = document.getElementById('loginSubmitBtn');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const showForgotPasswordBtn = document.getElementById('showForgotPasswordBtn');
    const backToLoginBtn = document.getElementById('backToLoginBtn');
    const forgotPasswordSubmitBtn = document.getElementById('forgotPasswordSubmitBtn');
    const invalidLoginMessage = @json(\App\Http\Controllers\LoginController::INVALID_LOGIN_MESSAGE);

    if (!loginForm) return;

    function clearLoginModalState() {
        errorDiv.style.display = 'none';
        errorMsg.innerHTML = '';
        document.querySelectorAll('#loginModal .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    function showLoginForm() {
        clearLoginModalState();
        forgotPasswordForm.classList.add('d-none');
        loginForm.classList.remove('d-none');
        if (loginModal && loginModal.classList.contains('show')) {
            document.getElementById('loginUsername').focus();
        }
    }

    function showForgotPasswordForm() {
        clearLoginModalState();
        loginForm.classList.add('d-none');
        forgotPasswordForm.classList.remove('d-none');
        if (loginModal && loginModal.classList.contains('show')) {
            document.getElementById('resetEmail').focus();
        }
    }

    showForgotPasswordBtn.addEventListener('click', showForgotPasswordForm);
    backToLoginBtn.addEventListener('click', showLoginForm);

    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearLoginModalState();

        loading.classList.add('show');
        submitBtn.disabled = true;

        try {
            const formData = new FormData(loginForm);
            const csrfToken = formData.get('_token') || document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('{{ url("/login") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : {};

            loading.classList.remove('show');
            submitBtn.disabled = false;

            if (response.ok && data.success) {
                window.location.href = data.redirect || '/dashboard';
                return;
            }

            let messages = [];

            if (response.status === 419) {
                messages.push(invalidLoginMessage);
            } else if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    messages.push(data.errors[key][0]);

                    const input = loginForm.querySelector(`[name="${key}"]`);
                    if (input) input.classList.add('is-invalid');
                });
            }

            if (data.message && response.status !== 419) {
                const message = String(data.message);
                messages.push(message.toLowerCase().includes('csrf') ? invalidLoginMessage : message);
            }

            errorMsg.innerHTML = messages.length
                ? messages.join('<br>')
                : invalidLoginMessage;

            errorDiv.style.display = 'block';

        } catch (error) {
            loading.classList.remove('show');
            submitBtn.disabled = false;

            errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.style.display = 'block';
        }
    });

    forgotPasswordForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearLoginModalState();
        loading.classList.add('show');
        forgotPasswordSubmitBtn.disabled = true;

        try {
            const formData = new FormData(forgotPasswordForm);
            const csrfToken = formData.get('_token') || document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('{{ route("password.email") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : {};

            loading.classList.remove('show');
            forgotPasswordSubmitBtn.disabled = false;

            if (response.ok && data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message || 'Link reset password sudah dikirim.'
                });
                forgotPasswordForm.reset();
                showLoginForm();
                return;
            }

            let messages = [];
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    messages.push(data.errors[key][0]);
                    const input = forgotPasswordForm.querySelector(`[name="${key}"]`);
                    if (input) input.classList.add('is-invalid');
                });
            }

            if (data.message) messages.push(data.message);

            errorMsg.innerHTML = messages.length ? messages.join('<br>') : 'Email tidak dapat diproses.';
            errorDiv.style.display = 'block';
        } catch (error) {
            loading.classList.remove('show');
            forgotPasswordSubmitBtn.disabled = false;
            errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.style.display = 'block';
        }
    });

    if (loginModal) {
        loginModal.addEventListener('hidden.bs.modal', function () {
            loginForm.reset();
            forgotPasswordForm.reset();
            showLoginForm();
        });
    }
});

        // Reset login form when modal closes
        document.getElementById('loginModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('loginForm').reset();
            document.getElementById('forgotPasswordForm').reset();
            document.getElementById('forgotPasswordForm').classList.add('d-none');
            document.getElementById('loginForm').classList.remove('d-none');
            document.getElementById('loginError').style.display = 'none';
            document.querySelectorAll('#loginModal .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });
        
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('loginPassword');
            const eyeIcon = document.getElementById('eyeIcon');
            
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        });

        // Auto-open login modal if redirected from /login
        @if(session('openLogin'))
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        @endif
    </script>
</body>
</html>
