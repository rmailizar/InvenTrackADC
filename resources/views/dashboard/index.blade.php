@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan data inventory Anda')

@section('content')
    <div class="animate-fade-in">
        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card primary">
                    <div class="stats-icon">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="stats-value">{{ number_format($totalItems) }}</div>
                    <div class="stats-label">Total Barang</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card success">
                    <div class="stats-icon">
                        <i class="bi bi-arrow-down-circle-fill"></i>
                    </div>
                    <div class="stats-value">{{ number_format($masukBulanIni) }}</div>
                    <div class="stats-label">Barang Masuk (Bulan Ini)</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card warning">
                    <div class="stats-icon">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                    </div>
                    <div class="stats-value">{{ number_format($keluarBulanIni) }}</div>
                    <div class="stats-label">Barang Keluar (Bulan Ini)</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card danger">
                    <div class="stats-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stats-value">{{ number_format($pendingCount) }}</div>
                    <div class="stats-label">Menunggu Approval</div>
                </div>
            </div>
        </div>

        {{-- ADMIN: Pending Transactions Approval (Per Hari) --}}
        @if(auth()->user()->isAdmin() && $pendingByDate->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-check2-square text-warning-custom me-2"></i>Transaksi Menunggu Approval</span>
                    <span class="badge bg-warning text-dark">{{ $pendingCount }} pending</span>
                </div>
                <div class="card-body p-0">
                    @foreach($pendingByDate as $date => $transactions)
                        <div class="approval-date-group">
                            <div class="d-flex align-items-center justify-content-between px-3 py-3"
                                style="background:var(--body-bg); border-bottom:1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar3 text-primary-custom"></i>
                                    <span class="fw-700"
                                        style="font-size:14px;">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
                                    <span class="badge bg-secondary" style="font-size:10px;">{{ $transactions->count() }}
                                        transaksi</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <form action="{{ route('dashboard.approveByDate') }}" method="POST" style="display:inline;"
                                        id="approveDate-{{ $loop->index }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <button type="button" class="btn btn-success btn-sm"
                                            onclick="swalConfirm('Approve Semua', 'Approve semua transaksi tanggal ini?', 'question', 'Ya, Approve', '#approveDate-{{ $loop->index }}')">
                                            <i class="bi bi-check-all"></i> Approve Semua
                                        </button>
                                    </form>
                                    <form action="{{ route('dashboard.rejectByDate') }}" method="POST" style="display:inline;"
                                        id="rejectDate-{{ $loop->index }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="swalConfirm('Reject Semua', 'Reject semua transaksi tanggal ini?', 'warning', 'Ya, Reject', '#rejectDate-{{ $loop->index }}')">
                                            <i class="bi bi-x-lg"></i> Reject Semua
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="table-container">
                                <table class="table" style="margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th>Barang</th>
                                            <th>Kategori</th>
                                            <th>Jenis</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                            <th>Harga Satuan</th>
                                            <th>User</th>
                                            <th>Keterangan</th>
                                            <th class="text-end" style="white-space:nowrap;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $tx)
                                            <tr>
                                                <td class="fw-600">{{ $tx->item->name ?? 'Barang dihapus' }}</td>
                                                <td>{{ $tx->item->category ?? '-' }}</td>
                                                <td>
                                                    <span class="badge-status badge-{{ $tx->type }}">
                                                        <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"
                                                            style="font-size:10px;"></i>
                                                        {{ strtoupper($tx->type) }}
                                                    </span>
                                                </td>
                                                <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                                <td>{{ $tx->item->unit ?? '-' }}</td>
                                                <td>{{ $tx->price ?? '-' }}</td>
                                                <td>{{ $tx->user->name ?? '-' }}</td>
                                                <td style="font-size:12px; color:var(--text-secondary);">
                                                    {{ Str::limit($tx->description, 40) }}
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                                            title="Edit transaksi" onclick="openTxEditModal({{ $tx->id }})">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <form action="{{ route('transactions.destroy', $tx) }}" method="POST"
                                                            class="d-inline" id="deleteTx-{{ $tx->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                                                title="Hapus transaksi"
                                                                onclick="swalConfirm('Hapus Transaksi', 'Hapus transaksi pending ini?', 'warning', 'Ya, Hapus', '#deleteTx-{{ $tx->id }}')">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Search Bar (Autocomplete) -->
        <div class="card mb-4" id="searchCard">
            <div class="card-body py-3">
                <div class="position-relative" id="searchWrapper">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-search" style="color:var(--text-secondary); font-size:18px;"></i>
                        <input type="text" id="itemSearchInput" class="form-control"
                            placeholder="Cari barang untuk filter grafik..." autocomplete="off"
                            style="background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); padding:10px 14px; border-radius:10px; font-size:14px;">
                        <button type="button" id="clearSearchBtn" class="btn btn-sm btn-outline-secondary"
                            style="display:none; border-radius:8px; white-space:nowrap;">
                            <i class="bi bi-x-lg"></i> Reset
                        </button>
                    </div>
                    <div id="searchSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
                    <div id="activeFilter" style="display:none; margin-top:10px;">
                        <span class="badge"
                            style="background:var(--primary-bg); color:var(--primary); padding:6px 14px; font-size:12px; border-radius:8px;">
                            <i class="bi bi-funnel-fill me-1"></i>
                            Filter aktif: <strong id="activeFilterName"></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row: Monthly + Category -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>
                            <i class="bi bi-bar-chart-fill text-primary-custom me-2"></i>
                            Barang Masuk vs Keluar (12 Bulan)
                        </span>

                        <select onchange="changeMonthlyYear(this.value)" class="form-select form-select-sm"
                            style="width:auto; min-width:120px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="card-body">
                        <div class="chart-container" style="height:320px;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-pie-chart-fill text-primary-custom me-2"></i>Stok per Kategori</span>
                        <select id="categoryYearFilter" class="form-select form-select-sm"
                            style="width:auto; min-width:120px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                            <option value="">Semua Tahun</option>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height:320px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yearly Chart Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>
                            <i class="bi bi-graph-up-arrow text-primary-custom me-2"></i>
                            Barang Masuk vs Keluar (Tahunan)
                        </span>

                        <div class="d-flex align-items-center gap-2">
                            <select onchange="changeYearRange()" id="startYear" class="form-select form-select-sm"
                                style="width:auto; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $startYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>

                            <span class="text-muted" style="font-size:12px;">-</span>

                            <select onchange="changeYearRange()" id="endYear" class="form-select form-select-sm"
                                style="width:auto; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $endYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="chart-container" style="height:320px;">
                            <canvas id="yearlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="row g-3">
            <!-- Low Stock Alert -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-exclamation-triangle-fill text-warning-custom me-2"></i>Stok Menipis</span>
                        @if($lowStockItems->count() > 0)
                            <span class="badge bg-danger">{{ $lowStockItems->count() }}</span>
                        @endif
                    </div>
                    <div class="card-body" style="max-height:350px; overflow-y:auto;">
                        @forelse($lowStockItems as $item)
                            <div class="low-stock-item">
                                <div class="item-info">
                                    <h6>{{ $item->name }}</h6>
                                    <span>{{ $item->category }} · Min: {{ $item->min_stock }} {{ $item->unit }}</span>
                                </div>

                                @if($item->current_stock <= 0)
                                    <div class="text-danger fw-600">Habis</div>
                                @else
                                    <div class="text-warning fw-600" style="color: var(--warning-dark);">Rendah</div>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state" style="padding:30px 10px;">
                                <i class="bi bi-check-circle" style="font-size:40px; color:var(--success);"></i>
                                <h6 class="mt-2" style="font-size:13px;">Semua stok aman</h6>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Top Items Keluar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-fire text-danger-custom me-2"></i>Barang Paling Sering Keluar</span>
                    </div>
                    <div class="card-body">
                        @forelse($topKeluar as $index => $tx)
                            <div
                                class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-700"
                                        style="width:24px;height:24px;border-radius:50%;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:11px;">{{ $index + 1 }}</span>
                                    <div>
                                        <div class="fw-600" style="font-size:13px;">{{ $tx->item->name ?? 'Barang dihapus' }}
                                        </div>
                                        <div style="font-size:11px;color:var(--text-secondary);">{{ $tx->item->category ?? '' }}
                                        </div>
                                    </div>
                                </div>
                                <span class="fw-700 text-danger-custom">{{ number_format($tx->total) }}</span>
                            </div>
                        @empty
                            <div class="empty-state" style="padding:30px 10px;">
                                <i class="bi bi-inbox" style="font-size:40px;"></i>
                                <h6 class="mt-2" style="font-size:13px;">Belum ada data</h6>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Transactions + Sync Button -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-clock-fill text-primary-custom me-2"></i>Transaksi Terbaru</span>
                        @if(auth()->user()->isAdmin())
                            <form action="{{ route('sync.sheets') }}" method="POST" style="display:inline;" id="syncSheetsForm">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="swalConfirm('Sync Data', 'Sync semua data ke Google Sheets?', 'question', 'Ya, Sync', '#syncSheetsForm')"
                                    title="Sync ke Google Sheets">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Sync
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body" style="max-height:350px; overflow-y:auto;">
                        @forelse($recentTransactions as $tx)
                            <div class="d-flex align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div
                                    style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;{{ $tx->type === 'in' ? 'background:var(--success-bg);color:var(--success);' : 'background:var(--danger-bg);color:var(--danger);' }}">
                                    <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-600" style="font-size:13px;">{{ $tx->item->name ?? 'Barang dihapus' }}</div>
                                    <div style="font-size:11px;color:var(--text-secondary);">
                                        {{ $tx->quantity }} {{ $tx->item->unit ?? '' }} · {{ $tx->user->name ?? '' }}
                                    </div>
                                </div>
                                <span class="badge-status badge-{{ $tx->status }}"
                                    style="font-size:10px;">{{ ucfirst($tx->status) }}</span>
                            </div>
                        @empty
                            <div class="empty-state" style="padding:30px 10px;">
                                <i class="bi bi-inbox" style="font-size:40px;"></i>
                                <h6 class="mt-2" style="font-size:13px;">Belum ada transaksi</h6>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            max-height: 300px;
            overflow-y: auto;
            margin-top: -2px;
        }

        .autocomplete-item {
            padding: 10px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.15s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: var(--primary-bg);
        }

        .autocomplete-item .item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .autocomplete-item .item-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-color);
        }

        .autocomplete-item .item-category {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .autocomplete-no-result {
            padding: 16px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
        }
    </style>

    {{-- Transaction Edit Modal --}}
    @if(auth()->user()->isAdmin())
        <div class="modal fade inventrack-modal" id="dashTxModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="position:relative;">
                    <div class="modal-loading-overlay" id="dashTxLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="dashTxModalTitle">
                            <i class="bi bi-pencil-fill"></i> <span>Edit Transaksi</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-error-alert" id="dashTxError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="dashTxErrorMsg"></span>
                        </div>
                        <form id="dashTxForm" novalidate>
                            <input type="hidden" id="dashTxId" value="">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control" required id="dashTxDate">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                    <select name="type" class="form-select" required id="dashTxType">
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="in">📥 Barang Masuk</option>
                                        <option value="out">📤 Barang Keluar</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                                <select name="item_id" class="form-select" required id="dashTxItemSelect">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-category="{{ $item->category }}"
                                            data-unit="{{ $item->unit }}" data-stock="{{ $item->current_stock }}">
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kategori</label>
                                    <input type="text" class="form-control" id="dashTxItemCategory" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Satuan</label>
                                    <input type="text" class="form-control" id="dashTxItemUnit" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Stok Saat Ini</label>
                                    <input type="text" class="form-control" id="dashTxItemStock" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" placeholder="Masukkan jumlah"
                                    required id="dashTxQuantity">
                                <div id="dashTxStockWarning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia!
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Harga Satuan</label>
                                <input type="number" name="price" class="form-control" min="0" step="1"
                                    placeholder="Kosongkan jika tidak ada" id="dashTxPrice">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Keterangan</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Keterangan tambahan (opsional)" id="dashTxDescription"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="dashTxSubmitBtn" onclick="submitDashTxForm()">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        // ===== Theme detection =====
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
        const tickColor = isDark ? '#94a3b8' : '#6c757d';
        const legendColor = isDark ? '#e2e8f0' : '#1a1a2e';

        function updateChartTheme() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

            const newLegendColor = isDark ? '#ffffff' : '#000000';
            const newTickColor = isDark ? '#cbd5f5' : '#000000';
            const newGridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';

            [monthlyChartInstance, yearlyChartInstance, categoryChartInstance].forEach(chart => {

                // ===== LEGEND (SEMUA CHART) =====
                if (chart.options.plugins?.legend?.labels) {
                    chart.options.plugins.legend.labels.color = newLegendColor;
                }

                // ===== KHUSUS BAR / LINE CHART (ADA SCALES) =====
                if (chart.options.scales) {
                    if (chart.options.scales.x?.ticks) {
                        chart.options.scales.x.ticks.color = newTickColor;
                    }

                    if (chart.options.scales.y?.ticks) {
                        chart.options.scales.y.ticks.color = newTickColor;
                    }

                    if (chart.options.scales.y?.grid) {
                        chart.options.scales.y.grid.color = newGridColor;
                    }
                }

                chart.update();
            });
        }

        function changeMonthlyYear(year) {
            fetch(`{{ route('dashboard.monthlyData') }}?year=${year}`)
                .then(res => res.json())
                .then(data => {

                    monthlyChartInstance.data.labels = data.map(d => d.label);
                    monthlyChartInstance.data.datasets[0].data = data.map(d => d.masuk);
                    monthlyChartInstance.data.datasets[1].data = data.map(d => d.keluar);

                    monthlyChartInstance.update('active');
                })
                .catch(err => console.error(err));
        }

        function changeYearRange() {
            const start = document.getElementById('startYear').value;
            const end = document.getElementById('endYear').value;

            const url = new URL(window.location.href);

            url.searchParams.set('start_year', start);
            url.searchParams.set('end_year', end);

            window.location.href = url.toString();
        }

        // ===== Chart color constants =====
        const masukBg = 'rgba(16, 185, 129, 0.8)';
        const masukBorder = '#10b981';
        const keluarBg = 'rgba(239, 68, 68, 0.7)';
        const keluarBorder = '#ef4444';

        // ===== Common chart options factory =====
        function barChartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            color: legendColor,
                            font: { family: 'Inter', size: 12, weight: 500 }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: tickColor, font: { family: 'Inter', size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: tickColor, font: { family: 'Inter', size: 11 } }
                    }
                }
            };
        }

        // ===== Monthly Chart =====
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChartInstance = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_column($monthlyData, 'label')) !!},
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: {!! json_encode(array_column($monthlyData, 'masuk')) !!},
                        backgroundColor: masukBg,
                        borderColor: masukBorder,
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                    {
                        label: 'Barang Keluar',
                        data: {!! json_encode(array_column($monthlyData, 'keluar')) !!},
                        backgroundColor: keluarBg,
                        borderColor: keluarBorder,
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    }
                ]
            },
            options: barChartOptions()
        });

        // ===== Yearly Chart =====
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        const yearlyChartInstance = new Chart(yearlyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($yearlyData, 'label')) !!},
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: {!! json_encode(array_column($yearlyData, 'masuk')) !!},
                        backgroundColor: masukBg,
                        borderColor: masukBorder,
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                    {
                        label: 'Barang Keluar',
                        data: {!! json_encode(array_column($yearlyData, 'keluar')) !!},
                        backgroundColor: keluarBg,
                        borderColor: keluarBorder,
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    }
                ]
            },
            options: barChartOptions()
        });

        // ===== Category Donut Chart =====
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        const catColors = ['#10b981', '#06b6d4', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];
        const categoryChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_column($categoryData, 'category')) !!},
                datasets: [{
                    data: {!! json_encode(array_column($categoryData, 'stock')) !!},
                    backgroundColor: catColors.slice(0, {{ count($categoryData) }}),
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16,
                            color: legendColor,
                            font: { family: 'Inter', size: 11, weight: 500 }
                        }
                    }
                }
            }
        });

        // ===== Autocomplete Search =====
        const searchInput = document.getElementById('itemSearchInput');
        const suggestionsBox = document.getElementById('searchSuggestions');
        const clearBtn = document.getElementById('clearSearchBtn');
        const activeFilter = document.getElementById('activeFilter');
        const activeFilterName = document.getElementById('activeFilterName');
        let debounceTimer = null;
        let selectedItemId = null;
        let activeIndex = -1;

        searchInput.addEventListener('input', function () {
            const q = this.value.trim();
            clearTimeout(debounceTimer);

            if (q.length < 1) {
                suggestionsBox.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('dashboard.searchItems') }}?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(items => {
                        activeIndex = -1;
                        if (items.length === 0) {
                            suggestionsBox.innerHTML = '<div class="autocomplete-no-result"><i class="bi bi-search me-1"></i>Tidak ada barang ditemukan</div>';
                        } else {
                            suggestionsBox.innerHTML = items.map((item, idx) => `
                                                                                                                                                <div class="autocomplete-item" data-id="${item.id}" data-name="${item.name}" data-index="${idx}">
                                                                                                                                                    <div class="item-icon"><i class="bi bi-box-seam"></i></div>
                                                                                                                                                    <div>
                                                                                                                                                        <div class="item-name">${highlightMatch(item.name, q)}</div>
                                                                                                                                                        <div class="item-category">${item.category || ''}</div>
                                                                                                                                                    </div>
                                                                                                                                                </div>
                                                                                                                                            `).join('');

                            // Click event on suggestion items
                            suggestionsBox.querySelectorAll('.autocomplete-item').forEach(el => {
                                el.addEventListener('click', function () {
                                    selectItem(this.dataset.id, this.dataset.name);
                                });
                            });
                        }
                        suggestionsBox.style.display = 'block';
                    })
                    .catch(() => {
                        suggestionsBox.style.display = 'none';
                    });
            }, 250);
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', function (e) {
            const items = suggestionsBox.querySelectorAll('.autocomplete-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                updateActiveItem(items);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                const active = items[activeIndex];
                selectItem(active.dataset.id, active.dataset.name);
            } else if (e.key === 'Escape') {
                suggestionsBox.style.display = 'none';
            }
        });

        function updateActiveItem(items) {
            items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
            if (items[activeIndex]) {
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function highlightMatch(text, query) {
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<mark style="background:var(--primary-bg);color:var(--primary);padding:0 2px;border-radius:3px;">$1</mark>');
        }

        function selectItem(itemId, itemName) {
            selectedItemId = itemId;
            searchInput.value = itemName;
            suggestionsBox.style.display = 'none';
            clearBtn.style.display = 'inline-flex';
            activeFilter.style.display = 'block';
            activeFilterName.textContent = itemName;

            // Fetch filtered chart data
            fetch(`{{ route('dashboard.chartData') }}?item_id=${itemId}`)
                .then(res => res.json())
                .then(data => {
                    // Update monthly chart
                    monthlyChartInstance.data.labels = data.monthlyData.map(d => d.label);
                    monthlyChartInstance.data.datasets[0].data = data.monthlyData.map(d => d.masuk);
                    monthlyChartInstance.data.datasets[1].data = data.monthlyData.map(d => d.keluar);
                    monthlyChartInstance.update('active');

                    // Update yearly chart
                    yearlyChartInstance.data.labels = data.yearlyData.map(d => d.label);
                    yearlyChartInstance.data.datasets[0].data = data.yearlyData.map(d => d.masuk);
                    yearlyChartInstance.data.datasets[1].data = data.yearlyData.map(d => d.keluar);
                    yearlyChartInstance.update('active');
                });
        }

        // Clear / reset search
        clearBtn.addEventListener('click', function () {
            selectedItemId = null;
            searchInput.value = '';
            suggestionsBox.style.display = 'none';
            clearBtn.style.display = 'none';
            activeFilter.style.display = 'none';

            // Reset charts to original data
            fetch(`{{ route('dashboard.chartData') }}`)
                .then(res => res.json())
                .then(data => {
                    monthlyChartInstance.data.labels = data.monthlyData.map(d => d.label);
                    monthlyChartInstance.data.datasets[0].data = data.monthlyData.map(d => d.masuk);
                    monthlyChartInstance.data.datasets[1].data = data.monthlyData.map(d => d.keluar);
                    monthlyChartInstance.update('active');

                    yearlyChartInstance.data.labels = data.yearlyData.map(d => d.label);
                    yearlyChartInstance.data.datasets[0].data = data.yearlyData.map(d => d.masuk);
                    yearlyChartInstance.data.datasets[1].data = data.yearlyData.map(d => d.keluar);
                    yearlyChartInstance.update('active');
                });
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function (e) {
            if (!document.getElementById('searchWrapper').contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });

        // ===== Category Year Filter =====
        document.getElementById('categoryYearFilter').addEventListener('change', function () {
            const year = this.value;
            const url = year
                ? `{{ route('dashboard.categoryByYear') }}?year=${year}`
                : `{{ route('dashboard.categoryByYear') }}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    categoryChartInstance.data.labels = data.map(d => d.category);
                    categoryChartInstance.data.datasets[0].data = data.map(d => d.stock);
                    categoryChartInstance.data.datasets[0].backgroundColor = catColors.slice(0, data.length);
                    categoryChartInstance.update('active');
                });
        });

        // ===== Transaction Edit Modal (Dashboard) =====
        @if(auth()->user()->isAdmin())
            const dashTxModalEl = document.getElementById('dashTxModal');
            const dashTxModal = dashTxModalEl ? new bootstrap.Modal(dashTxModalEl) : null;

            // Item select -> auto-fill
            const dashTxItemSelect = document.getElementById('dashTxItemSelect');
            const dashTxCategoryInput = document.getElementById('dashTxItemCategory');
            const dashTxUnitInput = document.getElementById('dashTxItemUnit');
            const dashTxStockInput = document.getElementById('dashTxItemStock');
            const dashTxQuantityInput = document.getElementById('dashTxQuantity');
            const dashTxTypeSelect = document.getElementById('dashTxType');
            const dashTxStockWarning = document.getElementById('dashTxStockWarning');

            if (dashTxItemSelect) {
                dashTxItemSelect.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    if (this.value) {
                        dashTxCategoryInput.value = selected.dataset.category || '';
                        dashTxUnitInput.value = selected.dataset.unit || '';
                        dashTxStockInput.value = selected.dataset.stock || '0';
                    } else {
                        dashTxCategoryInput.value = '';
                        dashTxUnitInput.value = '';
                        dashTxStockInput.value = '';
                    }
                    checkDashTxStock();
                });

                dashTxQuantityInput.addEventListener('input', checkDashTxStock);
                dashTxTypeSelect.addEventListener('change', checkDashTxStock);
            }

            function checkDashTxStock() {
                if (dashTxTypeSelect.value === 'out' && dashTxItemSelect.value) {
                    const stock = parseInt(dashTxStockInput.value) || 0;
                    const qty = parseInt(dashTxQuantityInput.value) || 0;
                    dashTxStockWarning.style.display = qty > stock ? 'block' : 'none';
                } else {
                    dashTxStockWarning.style.display = 'none';
                }
            }

            function openTxEditModal(id) {
                // Reset form
                document.getElementById('dashTxForm').reset();
                document.getElementById('dashTxError').style.display = 'none';
                document.querySelectorAll('#dashTxForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
                dashTxCategoryInput.value = '';
                dashTxUnitInput.value = '';
                dashTxStockInput.value = '';
                dashTxStockWarning.style.display = 'none';

                document.getElementById('dashTxId').value = id;

                const loading = document.getElementById('dashTxLoading');
                loading.classList.add('show');
                dashTxModal.show();

                fetch(`{{ url('transactions') }}/${id}/edit-data`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        loading.classList.remove('show');
                        document.getElementById('dashTxDate').value = data.date;
                        document.getElementById('dashTxType').value = data.type;
                        document.getElementById('dashTxItemSelect').value = data.item_id;
                        document.getElementById('dashTxQuantity').value = data.quantity;
                        document.getElementById('dashTxPrice').value = data.price || '';
                        document.getElementById('dashTxDescription').value = data.description || '';

                        // Fill item info
                        dashTxCategoryInput.value = data.item?.category || '';
                        dashTxUnitInput.value = data.item?.unit || '';
                        dashTxStockInput.value = data.item?.current_stock || '0';
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        document.getElementById('dashTxErrorMsg').textContent = 'Gagal memuat data transaksi.';
                        document.getElementById('dashTxError').style.display = 'block';
                    });
            }

            function submitDashTxForm() {
                const form = document.getElementById('dashTxForm');
                const errorDiv = document.getElementById('dashTxError');
                const errorMsg = document.getElementById('dashTxErrorMsg');
                const loading = document.getElementById('dashTxLoading');
                const submitBtn = document.getElementById('dashTxSubmitBtn');
                const txId = document.getElementById('dashTxId').value;

                errorDiv.style.display = 'none';
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                loading.classList.add('show');
                submitBtn.disabled = true;

                const formData = new FormData(form);
                formData.append('_method', 'PUT');

                fetch(`{{ url('transactions') }}/${txId}`, {
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
                            dashTxModal.hide();
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
            if (dashTxModalEl) {
                dashTxModalEl.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('dashTxForm').reset();
                    document.getElementById('dashTxError').style.display = 'none';
                    document.querySelectorAll('#dashTxForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    dashTxCategoryInput.value = '';
                    dashTxUnitInput.value = '';
                    dashTxStockInput.value = '';
                    dashTxStockWarning.style.display = 'none';
                });
            }
        @endif
    </script>
@endpush